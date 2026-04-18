<?php

use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\CohereProvider;
use App\AI\Providers\DeepSeekProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GrokProvider;
use App\AI\Providers\MistralProvider;
use App\AI\Providers\OpenAIProvider;
use App\AI\Providers\OpenRouterProvider;
use App\AI\Providers\PerplexityProvider;

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_model' => env('AI_DEFAULT_MODEL', 'gemini-2.5-flash-lite'),
    'fallback_providers' => array_filter(array_map('trim', explode(',', (string) env('AI_FALLBACK_PROVIDERS', 'openai,gemini,anthropic,grok,deepseek,openrouter,mistral,cohere,perplexity')))),
    'request_timeout_seconds' => (int) env('AI_REQUEST_TIMEOUT_SECONDS', 12),
    'chat_stream_timeout_seconds' => (int) env('AI_CHAT_STREAM_TIMEOUT_SECONDS', 300),
    'request_retries' => (int) env('AI_REQUEST_RETRIES', 1),
    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 200),
    'max_request_duration_seconds' => (int) env('AI_MAX_REQUEST_DURATION_SECONDS', 20),
    'max_in_flight_requests_global' => (int) env('AI_MAX_IN_FLIGHT_REQUESTS_GLOBAL', 1200),
    'max_in_flight_requests_per_tenant' => (int) env('AI_MAX_IN_FLIGHT_REQUESTS_PER_TENANT', 12),
    'provider_keys_cache_ttl_seconds' => (int) env('AI_PROVIDER_KEYS_CACHE_TTL_SECONDS', 30),
    'models_cache_ttl_seconds' => (int) env('AI_MODELS_CACHE_TTL_SECONDS', 120),
    'buffer_request_logs_to_redis' => (bool) env('AI_BUFFER_REQUEST_LOGS_TO_REDIS', true),
    'request_log_buffer_ttl_seconds' => (int) env('AI_REQUEST_LOG_BUFFER_TTL_SECONDS', 600),
    'request_log_batch_size' => (int) env('AI_REQUEST_LOG_BATCH_SIZE', 100),
    'request_log_redis_queue_key' => env('AI_REQUEST_LOG_REDIS_QUEUE_KEY', 'ai:reqlog:queue'),
    'token_optimization' => [
        'enabled' => (bool) env('AI_TOKEN_OPTIMIZATION_ENABLED', true),
        // Hard ceiling on completion (max_tokens) per response_style; enforced server-side.
        'style_output_token_ceiling' => [
            'concise' => (int) env('AI_STYLE_OUTPUT_MAX_TOKENS_CONCISE', 2000),
            'balanced' => (int) env('AI_STYLE_OUTPUT_MAX_TOKENS_BALANCED', 10000),
            'detailed' => (int) env('AI_STYLE_OUTPUT_MAX_TOKENS_DETAILED', 20000),
        ],
        // When the client omits max_tokens, defaults stay within the style ceiling.
        'style_default_output_tokens' => [
            'concise' => (int) env('AI_STYLE_DEFAULT_OUTPUT_TOKENS_CONCISE', 1024),
            'balanced' => (int) env('AI_STYLE_DEFAULT_OUTPUT_TOKENS_BALANCED', 4096),
            'detailed' => (int) env('AI_STYLE_DEFAULT_OUTPUT_TOKENS_DETAILED', 8192),
        ],
        // Balanced + user cues like "in detail" may use the detailed ceiling (still capped by model max_output_tokens).
        'auto_escalate_balanced_to_detailed_ceiling' => (bool) env('AI_TOKEN_OPTIMIZATION_AUTO_ESCALATE_DETAILED', true),
        // Largest max_tokens clients may send (must be >= all style ceilings you allow).
        'request_max_tokens_upper_bound' => (int) env('AI_REQUEST_MAX_TOKENS_UPPER', 20000),
        'concise_system_instruction' => env(
            'AI_TOKEN_OPTIMIZATION_CONCISE_SYSTEM_INSTRUCTION',
            'Respond with minimal tokens while still fully answering the user request. Do not omit required key details.'
        ),
    ],
    'circuit_breaker_failure_threshold' => (int) env('AI_CIRCUIT_BREAKER_FAILURE_THRESHOLD', 3),
    'circuit_breaker_cooldown_seconds' => (int) env('AI_CIRCUIT_BREAKER_COOLDOWN_SECONDS', 45),
    'circuit_breaker_failure_window_seconds' => (int) env('AI_CIRCUIT_BREAKER_FAILURE_WINDOW_SECONDS', 60),
    'stream_chunk_flush_ms' => (int) env('AI_STREAM_CHUNK_FLUSH_MS', 50),
    'default_user_token_limit' => (int) env('AI_DEFAULT_USER_TOKEN_LIMIT', 10000),
    'uploads' => [
        'disk' => env('AI_UPLOADS_DISK', 'local'),
        'directory' => env('AI_UPLOADS_DIRECTORY', 'ai_uploads'),
    ],
    'database_connection' => env('AI_DB_CONNECTION', 'ai_mysql'),
    'usage_connection' => env('AI_USAGE_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'history_connection' => env('AI_HISTORY_DB_CONNECTION', env('AI_DB_CONNECTION', 'ai_mysql')),
    'adapters' => [
        'openai' => OpenAIProvider::class,
        'gemini' => GeminiProvider::class,
        'anthropic' => AnthropicProvider::class,
        'grok' => GrokProvider::class,
        'deepseek' => DeepSeekProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'mistral' => MistralProvider::class,
        'cohere' => CohereProvider::class,
        'perplexity' => PerplexityProvider::class,
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
            'model_aliases' => [
                'grok-2-mini' => env('GROK_MODEL_ALIAS_GROK_2_MINI', ''),
                'grok-2-latest' => env('GROK_MODEL_ALIAS_GROK_2_LATEST', ''),
            ],
            'model_fallback_candidates' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('GROK_MODEL_FALLBACK_CANDIDATES', ''))
            ))),
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
