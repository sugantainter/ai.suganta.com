<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'ai_provider_openai' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/openai.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_gemini' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/gemini.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_anthropic' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/anthropic.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_grok' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/grok.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_deepseek' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/deepseek.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_openrouter' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/openrouter.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_mistral' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/mistral.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_cohere' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/cohere.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'ai_provider_perplexity' => [
            'driver' => 'daily',
            'path' => storage_path('logs/providers/perplexity.log'),
            'level' => env('AI_PROVIDER_LOG_LEVEL', 'warning'),
            'days' => env('AI_PROVIDER_LOG_DAYS', 30),
            'replace_placeholders' => true,
        ],

    ],

];
