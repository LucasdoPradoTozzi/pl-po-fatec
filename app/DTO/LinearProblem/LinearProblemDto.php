<?php

namespace App\DTO\LinearProblem;

use App\Support\LinearProblemSchemaValidator;

final readonly class LinearProblemDto
{
    /**
     * @param list<string> $warnings
     * @param list<VariableDto> $variables
     * @param list<ConstraintDto> $constraints
     * @param list<string> $nonNegativity
     */
    public function __construct(
        public string $title,
        public string $problemSummary,
        public bool $isValidLp,
        public bool $academicModeAllowed,
        public string $complexityReason,
        public array $warnings,
        public ObjectiveDto $objective,
        public array $variables,
        public array $constraints,
        public array $nonNegativity,
        public DetectedCharacteristicsDto $detectedCharacteristics,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $validated = LinearProblemSchemaValidator::validate($data);

        return new self(
            title: $validated['title'],
            problemSummary: $validated['problem_summary'],
            isValidLp: $validated['is_valid_lp'],
            academicModeAllowed: $validated['academic_mode_allowed'],
            complexityReason: $validated['complexity_reason'],
            warnings: $validated['warnings'] ?? [],
            objective: ObjectiveDto::fromArray($validated['objective']),
            variables: array_map(static fn(array $item): VariableDto => VariableDto::fromArray($item), $validated['variables']),
            constraints: array_map(static fn(array $item): ConstraintDto => ConstraintDto::fromArray($item), $validated['constraints']),
            nonNegativity: $validated['non_negativity'],
            detectedCharacteristics: DetectedCharacteristicsDto::fromArray($validated['detected_characteristics']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'problem_summary' => $this->problemSummary,
            'is_valid_lp' => $this->isValidLp,
            'academic_mode_allowed' => $this->academicModeAllowed,
            'complexity_reason' => $this->complexityReason,
            'warnings' => $this->warnings,
            'objective' => $this->objective->toArray(),
            'variables' => array_map(static fn(VariableDto $item): array => $item->toArray(), $this->variables),
            'constraints' => array_map(static fn(ConstraintDto $item): array => $item->toArray(), $this->constraints),
            'non_negativity' => $this->nonNegativity,
            'detected_characteristics' => $this->detectedCharacteristics->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return [
            'title' => '',
            'problem_summary' => '',
            'is_valid_lp' => true,
            'academic_mode_allowed' => true,
            'complexity_reason' => '',
            'warnings' => [],
            'objective' => [
                'type' => 'maximize',
                'expression' => '',
            ],
            'variables' => [
                [
                    'name' => 'x1',
                    'meaning' => '',
                ],
            ],
            'constraints' => [
                [
                    'id' => 1,
                    'expression' => '',
                ],
            ],
            'non_negativity' => ['x1 >= 0'],
            'detected_characteristics' => [
                'integer_programming' => false,
                'non_linear' => false,
                'has_conditional_logic' => false,
                'has_multiple_objectives' => false,
            ],
        ];
    }
}
