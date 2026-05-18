<?php

return [
    'api_key' => env('GITHUBAI_API_KEY', ''),
    'endpoint' => env('GITHUBAI_ENDPOINT', 'https://models.github.ai/inference/chat/completions'),
    'timeout' => (int) env('GITHUBAI_TIMEOUT', 600),

    // Keep one model for this project unless you decide to expand.
    'model' => env('LLM_PRIMARY_MODEL', 'openai/gpt-4.1-mini'),
    'temperature' => (float) env('LLM_TEMPERATURE', 0.1),
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 2000),

    'retry' => [
        'enabled' => filter_var(env('LLM_RETRY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'max_attempts' => (int) env('LLM_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => (int) env('LLM_RETRY_BASE_DELAY', 60),
    ],

    'circuit_breaker' => [
        'enabled' => filter_var(env('LLM_CIRCUIT_BREAKER_ENABLED', true), FILTER_VALIDATE_BOOL),
        'threshold' => (int) env('LLM_CIRCUIT_BREAKER_THRESHOLD', 5),
        'cooldown' => (int) env('LLM_CIRCUIT_BREAKER_COOLDOWN', 300),
    ],

    'quota' => [
        'check_enabled' => filter_var(env('LLM_QUOTA_CHECK_ENABLED', true), FILTER_VALIDATE_BOOL),
        'buffer_percentage' => (int) env('LLM_QUOTA_BUFFER_PERCENTAGE', 20),
        'warning_threshold' => (int) env('LLM_QUOTA_WARNING_THRESHOLD', 80),
    ],

    'load_balancing' => env('LLM_LOAD_BALANCING', 'priority_fallback'),
    'prefer_mini_models' => filter_var(env('LLM_PREFER_MINI_MODELS', false), FILTER_VALIDATE_BOOL),
    'enable_fallback' => filter_var(env('LLM_ENABLE_FALLBACK', true), FILTER_VALIDATE_BOOL),
];
