<?php

namespace App\Services\Solvers\Academic;

use App\Services\Solvers\Academic\Exceptions\ExpressionParsingException;

class LinearExpressionParser
{
    /**
     * @param list<string> $variables
     * @return list<float>
     */
    public function parseObjective(string $expression, array $variables): array
    {
        return $this->parseLinearCombination($expression, $variables);
    }

    /**
     * @param list<string> $variables
     * @return array{coefficients: list<float>, operator: string, rhs: float}
     */
    public function parseConstraint(string $expression, array $variables): array
    {
        if (! preg_match('/(<=|>=|=)/', $expression, $operatorMatch)) {
            throw new ExpressionParsingException('Constraint must include <=, >= or = operator.');
        }

        $operator = (string) $operatorMatch[1];
        [$lhs, $rhs] = explode($operator, $expression, 2);

        if (! is_numeric(trim($rhs))) {
            throw new ExpressionParsingException('Constraint right-hand side must be numeric.');
        }

        return [
            'coefficients' => $this->parseLinearCombination(trim($lhs), $variables),
            'operator' => $operator,
            'rhs' => (float) trim($rhs),
        ];
    }

    /**
     * @param list<string> $variables
     * @return list<float>
     */
    private function parseLinearCombination(string $expression, array $variables): array
    {
        $normalized = str_replace(' ', '', $expression);

        if ($normalized === '') {
            throw new ExpressionParsingException('Linear expression cannot be empty.');
        }

        preg_match_all('/[+\-]?[^+\-]+/', $normalized, $matches);
        $terms = $matches[0] ?? [];

        $coeffs = array_fill(0, count($variables), 0.0);

        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }

            if (! preg_match('/^([+\-]?\d*\.?\d*)\*?([a-zA-Z]\w*)$/', $term, $parts)) {
                throw new ExpressionParsingException('Unsupported term: ' . $term);
            }

            $coefRaw = $parts[1];
            $varName = $parts[2];
            $index = array_search($varName, $variables, true);

            if ($index === false) {
                throw new ExpressionParsingException('Unknown variable in expression: ' . $varName);
            }

            if ($coefRaw === '' || $coefRaw === '+') {
                $coef = 1.0;
            } elseif ($coefRaw === '-') {
                $coef = -1.0;
            } else {
                $coef = (float) $coefRaw;
            }

            $coeffs[$index] += $coef;
        }

        return $coeffs;
    }
}
