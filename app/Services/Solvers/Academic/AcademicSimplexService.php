<?php

namespace App\Services\Solvers\Academic;

use App\Services\Solvers\Academic\Exceptions\ExpressionParsingException;
use Illuminate\Support\Arr;
use RuntimeException;

class AcademicSimplexService
{
    public function __construct(private readonly LinearExpressionParser $parser) {}

    /**
     * @param array<string, mixed> $problem
     * @return array<string, mixed>
     */
    public function solve(array $problem): array
    {
        $variables = array_values(array_map(
            static fn(array $item): string => (string) ($item['name'] ?? ''),
            (array) ($problem['variables'] ?? [])
        ));

        $constraints = (array) ($problem['constraints'] ?? []);

        if ($variables === [] || $constraints === []) {
            throw new RuntimeException('Problem must include variables and constraints.');
        }

        $c = $this->parser->parseObjective((string) Arr::get($problem, 'objective.expression', ''), $variables);

        $A = [];
        $b = [];

        foreach ($constraints as $constraint) {
            $parsed = $this->parser->parseConstraint((string) ($constraint['expression'] ?? ''), $variables);

            if ($parsed['operator'] !== '<=') {
                throw new ExpressionParsingException('Academic simplex currently supports only <= constraints.');
            }

            $A[] = $parsed['coefficients'];
            $b[] = $parsed['rhs'];
        }

        return $this->runSimplex(
            A: $A,
            b: $b,
            c: $c,
            variables: $variables,
            objectiveType: (string) Arr::get($problem, 'objective.type', 'maximize'),
            objectiveExpression: (string) Arr::get($problem, 'objective.expression', ''),
        );
    }

    /**
     * @param list<list<float>> $A
     * @param list<float> $b
     * @param list<float> $c
     * @param list<string> $variables
     * @return array<string, mixed>
     */
    private function runSimplex(
        array $A,
        array $b,
        array $c,
        array $variables,
        string $objectiveType,
        string $objectiveExpression,
    ): array {
        $m = count($A);
        $n = count($c);
        $slackNames = [];

        for ($i = 0; $i < $m; $i++) {
            $slackNames[] = 's' . ($i + 1);
        }

        $allVariableNames = array_merge($variables, $slackNames);

        $tableau = [];

        for ($i = 0; $i < $m; $i++) {
            $row = array_fill(0, $n + $m + 1, 0.0);
            for ($j = 0; $j < $n; $j++) {
                $row[$j] = $A[$i][$j];
            }
            $row[$n + $i] = 1.0;
            $row[$n + $m] = $b[$i];
            $tableau[] = $row;
        }

        $objectiveRow = array_fill(0, $n + $m + 1, 0.0);
        for ($j = 0; $j < $n; $j++) {
            $objectiveRow[$j] = -$c[$j];
        }
        $tableau[] = $objectiveRow;
        $initialTableau = $this->roundMatrix($tableau);

        $basis = [];
        for ($i = 0; $i < $m; $i++) {
            $basis[$i] = $n + $i;
        }

        $steps = [];
        $stepNumber = 1;

        while (true) {
            $pivotColumn = $this->selectPivotColumn($tableau[$m], $n + $m);

            if ($pivotColumn === null) {
                break;
            }

            $pivotRow = $this->selectPivotRow($tableau, $m, $pivotColumn, $n + $m);

            if ($pivotRow === null) {
                throw new RuntimeException('Problema ilimitado no modo academico.');
            }

            $before = $tableau;
            $ops = [];

            $pivotValue = $tableau[$pivotRow][$pivotColumn];
            $ops[] = 'L' . ($pivotRow + 1) . ' = L' . ($pivotRow + 1) . ' / ' . round($pivotValue, 6);

            for ($j = 0; $j <= $n + $m; $j++) {
                $tableau[$pivotRow][$j] /= $pivotValue;
            }

            for ($i = 0; $i <= $m; $i++) {
                if ($i === $pivotRow) {
                    continue;
                }

                $factor = $tableau[$i][$pivotColumn];

                if (abs($factor) < 1e-10) {
                    continue;
                }

                $ops[] = 'L' . ($i + 1) . ' = L' . ($i + 1) . ' - ' . round($factor, 6) . '*L' . ($pivotRow + 1);

                for ($j = 0; $j <= $n + $m; $j++) {
                    $tableau[$i][$j] -= $factor * $tableau[$pivotRow][$j];
                }
            }

            $leavingIndex = $basis[$pivotRow];
            $basis[$pivotRow] = $pivotColumn;

            $steps[] = [
                'step' => $stepNumber,
                'pivot_column' => $pivotColumn + 1,
                'pivot_row' => $pivotRow + 1,
                'entering_variable' => $allVariableNames[$pivotColumn],
                'leaving_variable' => $allVariableNames[$leavingIndex] ?? 'unknown',
                'tableau_before' => $this->roundMatrix($before),
                'operations' => $ops,
                'tableau_after' => $this->roundMatrix($tableau),
                'explanation' => 'Normalize a linha pivô e elimine a coluna pivô nas demais linhas.',
            ];

            $stepNumber++;

            if ($stepNumber > 30) {
                throw new RuntimeException('Numero maximo de iteracoes excedido no modo academico.');
            }
        }

        $values = array_fill(0, $n + $m, 0.0);

        for ($i = 0; $i < $m; $i++) {
            $values[$basis[$i]] = $tableau[$i][$n + $m];
        }

        $decisionValues = [];
        for ($i = 0; $i < $n; $i++) {
            $decisionValues[$variables[$i]] = round($values[$i], 6);
        }

        $rowLabels = [];
        for ($i = 0; $i < $m; $i++) {
            $rowLabels[] = 'L' . ($i + 1);
        }
        $rowLabels[] = 'Z';

        return [
            'status' => 'optimal',
            'steps' => $steps,
            'initial_tableau' => $initialTableau,
            'column_labels' => array_merge($allVariableNames, ['b']),
            'row_labels' => $rowLabels,
            'objective_type' => $objectiveType,
            'objective_expression' => $objectiveExpression,
            'objective_value' => round($tableau[$m][$n + $m], 6),
            'variables' => $decisionValues,
            'tableau' => $this->roundMatrix($tableau),
            'variable_names' => $allVariableNames,
        ];
    }

    /**
     * @param list<float> $objectiveRow
     */
    private function selectPivotColumn(array $objectiveRow, int $lastColumnIndex): ?int
    {
        $min = 0.0;
        $pivotColumn = null;

        for ($j = 0; $j < $lastColumnIndex; $j++) {
            if ($objectiveRow[$j] < $min) {
                $min = $objectiveRow[$j];
                $pivotColumn = $j;
            }
        }

        return $pivotColumn;
    }

    /**
     * @param list<list<float>> $tableau
     */
    private function selectPivotRow(array $tableau, int $constraintRows, int $pivotColumn, int $rhsColumn): ?int
    {
        $bestRatio = null;
        $bestRow = null;

        for ($i = 0; $i < $constraintRows; $i++) {
            $coefficient = $tableau[$i][$pivotColumn];

            if ($coefficient <= 0) {
                continue;
            }

            $ratio = $tableau[$i][$rhsColumn] / $coefficient;

            if ($bestRatio === null || $ratio < $bestRatio) {
                $bestRatio = $ratio;
                $bestRow = $i;
            }
        }

        return $bestRow;
    }

    /**
     * @param list<list<float>> $matrix
     * @return list<list<float>>
     */
    private function roundMatrix(array $matrix): array
    {
        return array_map(
            static fn(array $row): array => array_map(static fn(float $v): float => round($v, 6), $row),
            $matrix,
        );
    }
}
