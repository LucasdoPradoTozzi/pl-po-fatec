<?php

namespace App\Actions\Operations;

use App\Services\AI\GitHubAiClient;
use App\Services\AI\ModelGenerationParser;

class GenerateLinearModelFromDescription
{
    public function __construct(
        private readonly GitHubAiClient $client,
        private readonly ModelGenerationParser $parser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $description): array
    {
        $raw = $this->client->generateStructuredModel($description);

        return $this->parser->parse($raw);
    }
}
