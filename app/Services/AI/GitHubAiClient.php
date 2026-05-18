<?php

namespace App\Services\AI;

use App\Services\AI\Exceptions\LlmResponseException;
use Illuminate\Support\Facades\Http;

class GitHubAiClient
{
    /**
     * @return array<string, mixed>
     */
    public function generateStructuredModel(string $description): array
    {
        $endpoint = $this->normalizeEndpoint((string) config('llm.endpoint'));
        $apiKey = (string) config('llm.api_key');

        if ($endpoint === '' || $apiKey === '') {
            throw new LlmResponseException('LLM credentials are missing. Configure GITHUBAI_API_KEY and GITHUBAI_ENDPOINT.');
        }

        $maxAttempts = (bool) config('llm.retry.enabled', true)
            ? (int) config('llm.retry.max_attempts', 3)
            : 1;

        $response = Http::withToken($apiKey)
            ->timeout((int) config('llm.timeout', 60))
            ->retry(
                $maxAttempts,
                (int) config('llm.retry.base_delay_ms', 100),
                throw: false,
            )
            ->acceptJson()
            ->post($endpoint, [
                'model' => config('llm.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $description,
                    ],
                ],
                'temperature' => (float) config('llm.temperature', 0.1),
                'max_tokens' => (int) config('llm.max_tokens', 2000),
            ]);

        if ($response->failed()) {
            $providerMessage = (string) data_get($response->json(), 'error.message', '');
            $message = 'Failed to fetch model from LLM provider. HTTP ' . $response->status();

            if ($providerMessage !== '') {
                $message .= ' - ' . $providerMessage;
            }

            throw new LlmResponseException($message);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new LlmResponseException('LLM returned an invalid payload format.');
        }

        return $payload;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an operations research assistant.
Return only valid JSON matching exactly this schema:
{
  "title": "",
  "problem_summary": "",
  "is_valid_lp": true,
  "academic_mode_allowed": true,
  "complexity_reason": "",
  "warnings": [],
  "objective": {
    "type": "maximize",
    "expression": ""
  },
  "variables": [
    {
      "name": "x1",
      "meaning": ""
    }
  ],
  "constraints": [
    {
      "id": 1,
      "expression": ""
    }
  ],
  "non_negativity": [
    ""
  ],
  "detected_characteristics": {
    "integer_programming": false,
    "non_linear": false,
    "has_conditional_logic": false,
    "has_multiple_objectives": false
  }
}
Rules:
- No markdown.
- No text outside JSON.
- objective.type must be maximize or minimize.
- Use linear expressions with named variables.
PROMPT;
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        $trimmed = rtrim($endpoint, '/');

        if ($trimmed === '') {
            return $trimmed;
        }

        if (str_ends_with($trimmed, '/chat/completions') || str_ends_with($trimmed, '/responses')) {
            return $trimmed;
        }

        return $trimmed . '/chat/completions';
    }
}
