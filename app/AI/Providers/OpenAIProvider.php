<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use App\AI\Exceptions\ProviderRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'openai';
    }

    public function chat(array $payload, string $apiKey): array
    {
        $providerKey = $this->key();
        $requestBody = [
            'model' => (string) ($payload['model'] ?? ''),
            'messages' => (array) ($payload['messages'] ?? []),
            'stream' => false,
        ];

        if (isset($payload['temperature']) && is_numeric($payload['temperature'])) {
            $requestBody['temperature'] = (float) $payload['temperature'];
        }

        if (isset($payload['max_tokens']) && is_numeric($payload['max_tokens']) && (int) $payload['max_tokens'] > 0) {
            $requestBody['max_tokens'] = (int) $payload['max_tokens'];
        }

        try {
            $response = Http::retry(
                config('ai.request_retries', 2),
                config('ai.retry_delay_ms', 200),
                throw: false
            )
                ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
                ->timeout(config('ai.request_timeout_seconds', 12))
                ->withToken($apiKey)
                ->acceptJson()
                ->post(rtrim((string) config("ai.providers.{$providerKey}.base_url"), '/').'/chat/completions', $requestBody);
        } catch (ConnectionException $exception) {
            $this->providerLogger($providerKey)->warning('Provider connection/timeout error.', [
                'provider' => $providerKey,
                'model' => (string) ($requestBody['model'] ?? ''),
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw $exception;
        }

        if (! $response->successful()) {
            $decoded = (array) $response->json();
            $upstreamMessage = trim((string) data_get($decoded, 'error.message', ''));
            if ($upstreamMessage === '') {
                $upstreamMessage = trim((string) $response->body());
            }
            $message = $upstreamMessage !== ''
                ? ucfirst($providerKey)." request failed with status {$response->status()}: {$upstreamMessage}"
                : ucfirst($providerKey)." request failed with status {$response->status()}.";
            $this->providerLogger($providerKey)->warning('Provider request failed.', [
                'provider' => $providerKey,
                'model' => (string) ($requestBody['model'] ?? ''),
                'status' => $response->status(),
                'error' => $message,
            ]);
            throw new ProviderRequestException($providerKey, $response->status(), $message);
        }

        $json = (array) $response->json();
        $content = (string) data_get($json, 'choices.0.message.content', '');

        return [
            'provider' => $this->key(),
            'model' => (string) data_get($json, 'model', $payload['model']),
            'content' => $content,
            'usage' => [
                'prompt_tokens' => (int) data_get($json, 'usage.prompt_tokens', 0),
                'completion_tokens' => (int) data_get($json, 'usage.completion_tokens', 0),
                'total_tokens' => (int) data_get($json, 'usage.total_tokens', 0),
            ],
            'raw' => $json,
        ];
    }

    protected function providerLogger(string $providerKey)
    {
        $channel = 'ai_provider_'.strtolower(trim($providerKey));
        if (config("logging.channels.{$channel}") !== null) {
            return Log::channel($channel);
        }

        return Log::channel(config('logging.default', 'stack'));
    }
}
