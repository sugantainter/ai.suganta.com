<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
    'fallback_providers' => array_filter(array_map('trim', explode(',', (string) env('AI_FALLBACK_PROVIDERS', 'openai,gemini,anthropic')))),
    'request_timeout_seconds' => (int) env('AI_REQUEST_TIMEOUT_SECONDS', 30),
    'request_retries' => (int) env('AI_REQUEST_RETRIES', 2),
    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 200),
    'stream_chunk_flush_ms' => (int) env('AI_STREAM_CHUNK_FLUSH_MS', 50),
    'database_connection' => env('AI_DB_CONNECTION', 'ai_mysql'),
    'usage_connection' => env('AI_USAGE_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'history_connection' => env('AI_HISTORY_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'providers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
        ],
        'gemini' => [
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'api_key' => env('GEMINI_API_KEY'),
        ],
        'anthropic' => [
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        ],
    ],
];
