<?php

namespace App\Services\Solvers\Industrial;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OrToolsClient
{
    /**
     * @param array<string, mixed> $problem
     * @return array<string, mixed>
     */
    public function solve(array $problem): array
    {
        $response = Http::timeout((int) config('services.ortools.timeout', 60))
            ->acceptJson()
            ->post((string) config('services.ortools.endpoint'), [
                'problem' => $problem,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Industrial solver request failed. HTTP ' . $response->status());
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Industrial solver returned invalid payload.');
        }

        return $payload;
    }
}
