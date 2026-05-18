<?php

namespace App\DTO\LinearProblem;

final readonly class ObjectiveDto
{
    public function __construct(
        public string $type,
        public string $expression,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? 'maximize'),
            expression: (string) ($data['expression'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'expression' => $this->expression,
        ];
    }
}
