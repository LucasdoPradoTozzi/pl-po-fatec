<?php

use App\Services\Solvers\Academic\AcademicSimplexService;
use App\Services\Solvers\Academic\AcademicEligibilityService;
use App\Services\Solvers\Academic\LinearExpressionParser;

test('academic simplex returns step by step solution for small LP', function () {
    $service = new AcademicSimplexService(new LinearExpressionParser());

    $problem = [
        'objective' => [
            'type' => 'maximize',
            'expression' => '3x1 + 5x2',
        ],
        'variables' => [
            ['name' => 'x1', 'meaning' => 'producao 1'],
            ['name' => 'x2', 'meaning' => 'producao 2'],
        ],
        'constraints' => [
            ['id' => 1, 'expression' => '2x1 + x2 <= 18'],
            ['id' => 2, 'expression' => '2x1 + 3x2 <= 42'],
            ['id' => 3, 'expression' => '3x1 + x2 <= 24'],
        ],
    ];

    $result = $service->solve($problem);

    expect($result['status'])->toBe('optimal');
    expect($result['steps'])->not->toBeEmpty();
    expect($result['objective_value'])->toBeGreaterThan(0);
    expect($result['variables'])->toHaveKeys(['x1', 'x2']);
    expect($result)->toHaveKeys(['initial_tableau', 'column_labels', 'row_labels', 'objective_type', 'objective_expression']);
    expect($result['column_labels'])->toContain('x1', 'x2', 'b');
    expect(end($result['row_labels']))->toBe('Z');
});

test('academic mode stays enabled with empty placeholder constraints', function () {
    $eligibility = new AcademicEligibilityService();

    $problem = [
        'objective' => [
            'type' => 'maximize',
            'expression' => '',
        ],
        'variables' => [
            ['name' => 'x1', 'meaning' => ''],
        ],
        'constraints' => [
            ['id' => 1, 'expression' => ''],
        ],
        'detected_characteristics' => [
            'integer_programming' => false,
            'non_linear' => false,
        ],
    ];

    $result = $eligibility->evaluate($problem);

    expect($result['allowed'])->toBeTrue();
    expect($result['reason'])->toBe('');
});
