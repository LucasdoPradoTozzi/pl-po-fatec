<?php

namespace App\DTO\LinearProblem;

final readonly class ConstraintDto
{
    public function __construct(
        public int $id,
        public string $expression,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            expression: (string) ($data['expression'] ?? ''),
        );
    }

    /**
     * @return array{id: int, expression: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'expression' => $this->expression,
        ];
    }
}
