<?php

use App\Services\AI\ModelGenerationParser;

uses(\Tests\TestCase::class);

test('model parser fills missing warnings with default array', function () {
    $parser = new ModelGenerationParser();

    $response = [
        'choices' => [
            [
                'message' => [
                    'content' => json_encode([
                        'title' => 'Teste',
                        'problem_summary' => 'Resumo',
                        'is_valid_lp' => true,
                        'academic_mode_allowed' => true,
                        'complexity_reason' => '',
                        'objective' => [
                            'type' => 'maximize',
                            'expression' => '3x1 + 2x2',
                        ],
                        'variables' => [
                            ['name' => 'x1', 'meaning' => 'v1'],
                            ['name' => 'x2', 'meaning' => 'v2'],
                        ],
                        'constraints' => [
                            ['id' => 1, 'expression' => 'x1 + x2 <= 10'],
                        ],
                        'non_negativity' => ['x1 >= 0', 'x2 >= 0'],
                        'detected_characteristics' => [
                            'integer_programming' => false,
                            'non_linear' => false,
                            'has_conditional_logic' => false,
                            'has_multiple_objectives' => false,
                        ],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ],
    ];

    $parsed = $parser->parse($response);

    expect($parsed['warnings'])->toBeArray();
    expect($parsed['warnings'])->toBe([]);
});
