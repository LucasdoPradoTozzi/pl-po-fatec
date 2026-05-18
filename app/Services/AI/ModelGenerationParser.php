<?php

namespace App\Services\AI;

use App\DTO\LinearProblem\LinearProblemDto;
use App\Services\AI\Exceptions\LlmResponseException;
use Illuminate\Support\Arr;
use Throwable;

class ModelGenerationParser
{
    /**
     * @param array<string, mixed> $llmResponse
     * @return array<string, mixed>
     */
    public function parse(array $llmResponse): array
    {
        $content = $this->extractContent($llmResponse);
        $json = $this->extractJson($content);

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new LlmResponseException('Could not decode LLM JSON output.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new LlmResponseException('LLM output JSON is not an object.');
        }

        return LinearProblemDto::fromArray($this->normalizeToSchema($decoded))->toArray();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractContent(array $payload): string
    {
        $messageContent = Arr::get($payload, 'choices.0.message.content');

        if (is_string($messageContent) && trim($messageContent) !== '') {
            return $messageContent;
        }

        $outputText = Arr::get($payload, 'output.0.content.0.text');

        if (is_string($outputText) && trim($outputText) !== '') {
            return $outputText;
        }

        throw new LlmResponseException('LLM output did not include readable content.');
    }

    private function extractJson(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            return $trimmed;
        }

        if (preg_match('/```json\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new LlmResponseException('Could not locate JSON object in LLM content.');
        }

        return substr($trimmed, $start, $end - $start + 1);
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeToSchema(array $decoded): array
    {
        $base = LinearProblemDto::empty();

        $base['title'] = (string) Arr::get($decoded, 'title', $base['title']);
        $base['problem_summary'] = (string) Arr::get($decoded, 'problem_summary', $base['problem_summary']);
        $base['is_valid_lp'] = (bool) Arr::get($decoded, 'is_valid_lp', $base['is_valid_lp']);
        $base['academic_mode_allowed'] = (bool) Arr::get($decoded, 'academic_mode_allowed', $base['academic_mode_allowed']);
        $base['complexity_reason'] = (string) Arr::get($decoded, 'complexity_reason', $base['complexity_reason']);

        $warnings = Arr::get($decoded, 'warnings', $base['warnings']);
        $base['warnings'] = is_array($warnings) ? array_values($warnings) : [];

        $objective = Arr::get($decoded, 'objective', []);
        if (is_array($objective)) {
            $base['objective']['type'] = (string) Arr::get($objective, 'type', $base['objective']['type']);
            $base['objective']['expression'] = (string) Arr::get($objective, 'expression', $base['objective']['expression']);
        }

        $variables = Arr::get($decoded, 'variables', $base['variables']);
        if (is_array($variables) && $variables !== []) {
            $base['variables'] = array_values(array_map(
                static fn($item): array => [
                    'name' => (string) Arr::get((array) $item, 'name', ''),
                    'meaning' => (string) Arr::get((array) $item, 'meaning', ''),
                ],
                $variables,
            ));
        }

        $constraints = Arr::get($decoded, 'constraints', $base['constraints']);
        if (is_array($constraints) && $constraints !== []) {
            $base['constraints'] = array_values(array_map(
                static fn($item, $index): array => [
                    'id' => (int) Arr::get((array) $item, 'id', $index + 1),
                    'expression' => (string) Arr::get((array) $item, 'expression', ''),
                ],
                $constraints,
                array_keys($constraints),
            ));
        }

        $nonNegativity = Arr::get($decoded, 'non_negativity', $base['non_negativity']);
        if (is_array($nonNegativity) && $nonNegativity !== []) {
            $base['non_negativity'] = array_values(array_map(static fn($item): string => (string) $item, $nonNegativity));
        }

        $detected = Arr::get($decoded, 'detected_characteristics', []);
        if (is_array($detected)) {
            $base['detected_characteristics']['integer_programming'] = (bool) Arr::get($detected, 'integer_programming', $base['detected_characteristics']['integer_programming']);
            $base['detected_characteristics']['non_linear'] = (bool) Arr::get($detected, 'non_linear', $base['detected_characteristics']['non_linear']);
            $base['detected_characteristics']['has_conditional_logic'] = (bool) Arr::get($detected, 'has_conditional_logic', $base['detected_characteristics']['has_conditional_logic']);
            $base['detected_characteristics']['has_multiple_objectives'] = (bool) Arr::get($detected, 'has_multiple_objectives', $base['detected_characteristics']['has_multiple_objectives']);
        }

        return $base;
    }
}
