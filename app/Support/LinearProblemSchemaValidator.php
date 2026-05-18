<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class LinearProblemSchemaValidator
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validate(array $payload): array
    {
        return Validator::make($payload, self::rules())->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'problem_summary' => ['required', 'string'],
            'is_valid_lp' => ['required', 'boolean'],
            'academic_mode_allowed' => ['required', 'boolean'],
            'complexity_reason' => ['nullable', 'string'],
            'warnings' => ['sometimes', 'array'],
            'warnings.*' => ['string'],

            'objective' => ['required', 'array'],
            'objective.type' => ['required', 'in:maximize,minimize'],
            'objective.expression' => ['required', 'string'],

            'variables' => ['required', 'array', 'min:1'],
            'variables.*.name' => ['required', 'string', 'max:50'],
            'variables.*.meaning' => ['nullable', 'string'],

            'constraints' => ['required', 'array', 'min:1'],
            'constraints.*.id' => ['required', 'integer', 'min:1'],
            'constraints.*.expression' => ['required', 'string'],

            'non_negativity' => ['required', 'array', 'min:1'],
            'non_negativity.*' => ['required', 'string'],

            'detected_characteristics' => ['required', 'array'],
            'detected_characteristics.integer_programming' => ['required', 'boolean'],
            'detected_characteristics.non_linear' => ['required', 'boolean'],
            'detected_characteristics.has_conditional_logic' => ['required', 'boolean'],
            'detected_characteristics.has_multiple_objectives' => ['required', 'boolean'],
        ];
    }
}
