<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
    'fallback_providers' => array_filter(array_map('trim', explode(',', (string) env('AI_FALLBACK_PROVIDERS', 'openai,gemini,anthropic,grok,deepseek,openrouter,mistral,cohere,perplexity')))),
    'request_timeout_seconds' => (int) env('AI_REQUEST_TIMEOUT_SECONDS', 30),
    'request_retries' => (int) env('AI_REQUEST_RETRIES', 2),
    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 200),
    'stream_chunk_flush_ms' => (int) env('AI_STREAM_CHUNK_FLUSH_MS', 50),
    'default_user_token_limit' => (int) env('AI_DEFAULT_USER_TOKEN_LIMIT', 10000),
    'database_connection' => env('AI_DB_CONNECTION', 'ai_mysql'),
    'usage_connection' => env('AI_USAGE_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'history_connection' => env('AI_HISTORY_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'adapters' => [
        'openai' => \App\AI\Providers\OpenAIProvider::class,
        'gemini' => \App\AI\Providers\GeminiProvider::class,
        'anthropic' => \App\AI\Providers\AnthropicProvider::class,
        'grok' => \App\AI\Providers\GrokProvider::class,
        'deepseek' => \App\AI\Providers\DeepSeekProvider::class,
        'openrouter' => \App\AI\Providers\OpenRouterProvider::class,
        'mistral' => \App\AI\Providers\MistralProvider::class,
        'cohere' => \App\AI\Providers\CohereProvider::class,
        'perplexity' => \App\AI\Providers\PerplexityProvider::class,
    ],
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
        'grok' => [
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'api_key' => env('GROK_API_KEY'),
        ],
        'deepseek' => [
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'api_key' => env('DEEPSEEK_API_KEY'),
        ],
        'openrouter' => [
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'api_key' => env('OPENROUTER_API_KEY'),
        ],
        'mistral' => [
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            'api_key' => env('MISTRAL_API_KEY'),
        ],
        'cohere' => [
            'base_url' => env('COHERE_BASE_URL', 'https://api.cohere.ai/v1'),
            'api_key' => env('COHERE_API_KEY'),
        ],
        'perplexity' => [
            'base_url' => env('PERPLEXITY_BASE_URL', 'https://api.perplexity.ai'),
            'api_key' => env('PERPLEXITY_API_KEY'),
        ],
    ],
];
