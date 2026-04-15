<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use App\AI\Exceptions\ProviderRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'anthropic';
    }

    public function chat(array $payload, string $apiKey): array
    {
        $messages = [];
        foreach ($payload['messages'] as $message) {
            if (($message['role'] ?? 'user') === 'system') {
                continue;
            }
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::retry(
            config('ai.request_retries', 2),
            config('ai.retry_delay_ms', 200),
            throw: false
        )
            ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
            ->timeout(config('ai.request_timeout_seconds', 12))
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => (string) config('ai.providers.anthropic.version'),
            ])
            ->post(rtrim((string) config('ai.providers.anthropic.base_url'), '/').'/messages', [
                'model' => $payload['model'],
                'system' => collect($payload['messages'])->firstWhere('role', 'system')['content'] ?? '',
                'messages' => $messages,
                'temperature' => $payload['temperature'] ?? 0.7,
                'max_tokens' => $payload['max_tokens'] ?? 1024,
            ]);

        if (! $response->successful()) {
            $upstreamMessage = trim((string) data_get((array) $response->json(), 'error.message', ''));
            $message = $upstreamMessage !== ''
                ? "Anthropic request failed with status {$response->status()}: {$upstreamMessage}"
                : "Anthropic request failed with status {$response->status()}.";
            throw new ProviderRequestException('anthropic', $response->status(), $message);
        }

        $json = (array) $response->json();
        $content = (string) data_get($json, 'content.0.text', '');

        return [
            'provider' => $this->key(),
            'model' => (string) data_get($json, 'model', $payload['model']),
            'content' => $content,
            'usage' => [
                'prompt_tokens' => (int) data_get($json, 'usage.input_tokens', 0),
                'completion_tokens' => (int) data_get($json, 'usage.output_tokens', 0),
                'total_tokens' => (int) data_get($json, 'usage.input_tokens', 0) + (int) data_get($json, 'usage.output_tokens', 0),
            ],
            'raw' => $json,
        ];
    }
}
