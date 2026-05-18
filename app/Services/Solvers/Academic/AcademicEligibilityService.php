<?php

namespace App\Services\Solvers\Academic;

class AcademicEligibilityService
{
    /**
     * @param array<string, mixed> $problem
     * @return array{allowed: bool, reason: string}
     */
    public function evaluate(array $problem): array
    {
        $variables = (array) ($problem['variables'] ?? []);
        $constraints = (array) ($problem['constraints'] ?? []);
        $objectiveType = (string) data_get($problem, 'objective.type', 'maximize');

        if (count($variables) > 4) {
            return ['allowed' => false, 'reason' => 'Modo academico suporta no maximo 4 variaveis.'];
        }

        if (count($constraints) > 6) {
            return ['allowed' => false, 'reason' => 'Modo academico suporta no maximo 6 restricoes.'];
        }

        if ($objectiveType !== 'maximize') {
            return ['allowed' => false, 'reason' => 'Modo academico suporta apenas objetivo de maximizacao.'];
        }

        if ((bool) data_get($problem, 'detected_characteristics.integer_programming')) {
            return ['allowed' => false, 'reason' => 'Modo academico nao suporta programacao inteira.'];
        }

        if ((bool) data_get($problem, 'detected_characteristics.non_linear')) {
            return ['allowed' => false, 'reason' => 'Modo academico nao suporta nao linearidade.'];
        }

        foreach ($constraints as $constraint) {
            $expression = (string) ($constraint['expression'] ?? '');

            if (trim($expression) === '') {
                continue;
            }

            if (! str_contains($expression, '<=')) {
                return ['allowed' => false, 'reason' => 'Modo academico exige restricoes no formato <=.'];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }
}
