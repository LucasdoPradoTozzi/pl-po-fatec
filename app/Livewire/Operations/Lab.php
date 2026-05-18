<?php

namespace App\Livewire\Operations;

use App\Actions\Operations\GenerateLinearModelFromDescription;
use App\DTO\LinearProblem\LinearProblemDto;
use App\Services\AI\Exceptions\LlmResponseException;
use App\Services\Solvers\Academic\AcademicEligibilityService;
use App\Services\Solvers\Academic\AcademicSimplexService;
use App\Services\Solvers\Industrial\OrToolsClient;
use App\Support\LinearProblemSchemaValidator;
use App\Support\ProblemState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;

class Lab extends Component
{
    public string $description = '';

    /**
     * @var array<string, mixed>
     */
    public array $problem = [];

    /**
     * @var array<string, mixed>
     */
    public array $state = [];

    public bool $loadingAi = false;

    public bool $loadingSolve = false;

    public int $currentStepIndex = 0;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    protected bool $suspendInvalidation = false;

    public function mount(): void
    {
        $this->problem = LinearProblemDto::empty();
        $this->state = ProblemState::initial();
    }

    public function updated(string $name): void
    {
        if ($this->suspendInvalidation) {
            return;
        }

        if (str_starts_with($name, 'problem.') || str_starts_with($name, 'description')) {
            $this->invalidateComputedState();
            $this->state['userEdited'] = true;
            $this->refreshAcademicAvailability();
        }
    }

    public function addVariable(): void
    {
        $next = count($this->problem['variables']) + 1;
        $this->problem['variables'][] = ['name' => 'x' . $next, 'meaning' => ''];
        $this->problem['non_negativity'][] = 'x' . $next . ' >= 0';
    }

    public function removeVariable(int $index): void
    {
        if (count($this->problem['variables']) <= 1) {
            return;
        }

        unset($this->problem['variables'][$index]);
        $this->problem['variables'] = array_values($this->problem['variables']);
    }

    public function addConstraint(): void
    {
        $nextId = count($this->problem['constraints']) + 1;
        $this->problem['constraints'][] = ['id' => $nextId, 'expression' => ''];
    }

    public function removeConstraint(int $index): void
    {
        if (count($this->problem['constraints']) <= 1) {
            return;
        }

        unset($this->problem['constraints'][$index]);
        $this->problem['constraints'] = array_values($this->problem['constraints']);
        $this->renumberConstraints();
    }

    public function addNonNegativity(): void
    {
        $this->problem['non_negativity'][] = '';
    }

    public function removeNonNegativity(int $index): void
    {
        if (count($this->problem['non_negativity']) <= 1) {
            return;
        }

        unset($this->problem['non_negativity'][$index]);
        $this->problem['non_negativity'] = array_values($this->problem['non_negativity']);
    }

    public function generateModel(GenerateLinearModelFromDescription $action): void
    {
        $this->validate(['description' => ['required', 'string', 'min:10']]);

        $this->loadingAi = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $generated = $action->execute($this->description);
            $this->suspendInvalidation = true;
            $this->problem = $generated;
            $this->renumberConstraints();
            $this->refreshAcademicAvailability();
            $this->state['aiGenerated'] = true;
            $this->state['validationState'] = 'valid';
            $this->successMessage = 'Modelo gerado com sucesso pela IA. Revise os campos antes de resolver.';
        } catch (LlmResponseException | ValidationException | RuntimeException $exception) {
            $this->state['validationState'] = 'invalid';
            $this->errorMessage = $exception->getMessage();
        } finally {
            $this->loadingAi = false;
            $this->suspendInvalidation = false;
        }
    }

    public function openSolveModal(): void
    {
        // Legacy method kept for compatibility with older templates.
    }

    public function solveAcademic(AcademicEligibilityService $eligibilityService, AcademicSimplexService $simplex): void
    {
        if (! $this->validateProblemBeforeSolve()) {
            return;
        }

        $eligibility = $eligibilityService->evaluate($this->problem);

        if (! $eligibility['allowed']) {
            $this->problem['academic_mode_allowed'] = false;
            $this->problem['complexity_reason'] = $eligibility['reason'];
            $this->errorMessage = $eligibility['reason'];
            return;
        }

        $this->loadingSolve = true;
        $this->errorMessage = null;

        try {
            $result = Cache::remember($this->cacheKey('academic'), Carbon::now()->addMinutes(30), function () use ($simplex) {
                return $simplex->solve($this->problem);
            });

            $this->state['academicResult'] = $result;
            $this->state['activeModal'] = 'academic';
            $this->currentStepIndex = 0;
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        } finally {
            $this->loadingSolve = false;
        }
    }

    public function solveIndustrial(OrToolsClient $solver): void
    {
        if (! $this->validateProblemBeforeSolve()) {
            return;
        }

        $this->loadingSolve = true;
        $this->errorMessage = null;

        try {
            $result = Cache::remember($this->cacheKey('industrial'), Carbon::now()->addMinutes(30), function () use ($solver) {
                return $solver->solve($this->problem);
            });

            $this->state['industrialResult'] = $result;
            $this->state['activeModal'] = 'industrial';
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        } finally {
            $this->loadingSolve = false;
        }
    }

    public function nextStep(): void
    {
        $steps = $this->state['academicResult']['steps'] ?? [];
        $totalSteps = count($steps);
        // Permite avançar até um índice além do último passo para exibir a resposta final
        if ($this->currentStepIndex < $totalSteps) {
            $this->currentStepIndex++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStepIndex > 0) {
            $this->currentStepIndex--;
        }
    }

    public function closeModal(): void
    {
        $this->state['activeModal'] = null;
    }

    public function getCurrentStepProperty(): ?array
    {
        $steps = $this->state['academicResult']['steps'] ?? [];

        if (! isset($steps[$this->currentStepIndex])) {
            return null;
        }

        return $steps[$this->currentStepIndex];
    }

    public function getIsEditingLockedProperty(): bool
    {
        return in_array($this->state['activeModal'], ['academic', 'industrial'], true);
    }

    public function render()
    {
        return view('livewire.operations.lab');
    }

    private function invalidateComputedState(): void
    {
        if (! is_null($this->state['academicResult']) || ! is_null($this->state['industrialResult'])) {
            Cache::forget($this->cacheKey('academic'));
            Cache::forget($this->cacheKey('industrial'));
        }

        $this->state = ProblemState::invalidateComputed($this->state);
        $this->currentStepIndex = 0;
        $this->successMessage = null;
    }

    private function refreshAcademicAvailability(): void
    {
        $eligibility = (new AcademicEligibilityService())->evaluate($this->problem);
        $this->problem['academic_mode_allowed'] = $eligibility['allowed'];
        $this->problem['complexity_reason'] = $eligibility['reason'];
    }

    private function renumberConstraints(): void
    {
        foreach ($this->problem['constraints'] as $index => &$constraint) {
            $constraint['id'] = $index + 1;
        }
        unset($constraint);
    }

    private function cacheKey(string $solver): string
    {
        $userId = (string) (Auth::id() ?? 'guest');

        return sprintf(
            'operations:%s:%s:%s',
            $solver,
            $userId,
            sha1(json_encode($this->problem) ?: ''),
        );
    }

    private function validateProblemBeforeSolve(): bool
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            LinearProblemSchemaValidator::validate($this->problem);
            $this->state['validationState'] = 'valid';
            $this->refreshAcademicAvailability();

            return true;
        } catch (ValidationException $exception) {
            $this->state['validationState'] = 'invalid';
            $this->errorMessage = 'Preencha o modelo completo antes de resolver.';

            return false;
        }
    }
}
