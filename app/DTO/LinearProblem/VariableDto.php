<?php

namespace App\DTO\LinearProblem;

final readonly class VariableDto
{
    public function __construct(
        public string $name,
        public string $meaning,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            meaning: (string) ($data['meaning'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'meaning' => $this->meaning,
        ];
    }
}
