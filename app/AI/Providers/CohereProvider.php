<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CohereProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'cohere';
    }

    public function chat(array $payload, string $apiKey): array
    {
        $providerKey = $this->key();

        $response = Http::retry(
            config('ai.request_retries', 2),
            config('ai.retry_delay_ms', 200),
            throw: false
        )
            ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
            ->timeout(config('ai.request_timeout_seconds', 12))
            ->withToken($apiKey)
            ->acceptJson()
            ->post(rtrim((string) config("ai.providers.{$providerKey}.base_url"), '/').'/chat', [
                'model' => $payload['model'],
                'message' => (string) data_get(collect($payload['messages'])->reverse()->firstWhere('role', 'user'), 'content', ''),
                'chat_history' => collect($payload['messages'])
                    ->filter(fn ($message) => ($message['role'] ?? '') !== 'user')
                    ->map(fn ($message) => [
                        'role' => (($message['role'] ?? 'assistant') === 'assistant') ? 'CHATBOT' : 'SYSTEM',
                        'message' => (string) ($message['content'] ?? ''),
                    ])
                    ->values()
                    ->all(),
                'temperature' => $payload['temperature'] ?? 0.7,
                'max_tokens' => $payload['max_tokens'] ?? null,
            ]);

        if (! $response->successful()) {
            throw new ConnectionException("Cohere request failed with status {$response->status()}.");
        }

        $json = (array) $response->json();

        return [
            'provider' => $providerKey,
            'model' => (string) ($payload['model'] ?? 'command-r-plus'),
            'content' => (string) data_get($json, 'text', ''),
            'usage' => [
                'prompt_tokens' => (int) data_get($json, 'meta.billed_units.input_tokens', 0),
                'completion_tokens' => (int) data_get($json, 'meta.billed_units.output_tokens', 0),
                'total_tokens' => (int) data_get($json, 'meta.billed_units.input_tokens', 0) + (int) data_get($json, 'meta.billed_units.output_tokens', 0),
            ],
            'raw' => $json,
        ];
    }
}
