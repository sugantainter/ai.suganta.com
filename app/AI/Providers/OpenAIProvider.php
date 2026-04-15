<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'openai';
    }

    public function chat(array $payload, string $apiKey): array
    {
        $response = Http::retry(
            config('ai.request_retries', 2),
            config('ai.retry_delay_ms', 200),
            throw: false
        )->timeout(config('ai.request_timeout_seconds', 30))
            ->withToken($apiKey)
            ->acceptJson()
            ->post(rtrim((string) config('ai.providers.openai.base_url'), '/').'/chat/completions', [
                'model' => $payload['model'],
                'messages' => $payload['messages'],
                'temperature' => $payload['temperature'] ?? 0.7,
                'max_tokens' => $payload['max_tokens'] ?? null,
                'stream' => false,
            ]);

        if (! $response->successful()) {
            throw new ConnectionException("OpenAI request failed with status {$response->status()}.");
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
}
