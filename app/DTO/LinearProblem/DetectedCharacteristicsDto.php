<?php

namespace App\DTO\LinearProblem;

final readonly class DetectedCharacteristicsDto
{
    public function __construct(
        public bool $integerProgramming,
        public bool $nonLinear,
        public bool $hasConditionalLogic,
        public bool $hasMultipleObjectives,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            integerProgramming: (bool) ($data['integer_programming'] ?? false),
            nonLinear: (bool) ($data['non_linear'] ?? false),
            hasConditionalLogic: (bool) ($data['has_conditional_logic'] ?? false),
            hasMultipleObjectives: (bool) ($data['has_multiple_objectives'] ?? false),
        );
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'integer_programming' => $this->integerProgramming,
            'non_linear' => $this->nonLinear,
            'has_conditional_logic' => $this->hasConditionalLogic,
            'has_multiple_objectives' => $this->hasMultipleObjectives,
        ];
    }
}
