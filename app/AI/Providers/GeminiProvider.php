<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use App\AI\Exceptions\ProviderRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'gemini';
    }

    public function chat(array $payload, string $apiKey): array
    {
        $parts = [];
        foreach ($payload['messages'] as $message) {
            $parts[] = ['text' => (string) ($message['content'] ?? '')];
        }

        $response = Http::retry(
            config('ai.request_retries', 2),
            config('ai.retry_delay_ms', 200),
            throw: false
        )
            ->connectTimeout(max(2, min((int) config('ai.request_timeout_seconds', 12), 8)))
            ->timeout(config('ai.request_timeout_seconds', 12))
            ->acceptJson()
            ->post(
                rtrim((string) config('ai.providers.gemini.base_url'), '/').'/models/'.$payload['model'].':generateContent?key='.$apiKey,
                [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'temperature' => $payload['temperature'] ?? 0.7,
                        'maxOutputTokens' => $payload['max_tokens'] ?? null,
                    ],
                ]
            );

        if (! $response->successful()) {
            $upstreamMessage = trim((string) data_get((array) $response->json(), 'error.message', ''));
            $message = $upstreamMessage !== ''
                ? "Gemini request failed with status {$response->status()}: {$upstreamMessage}"
                : "Gemini request failed with status {$response->status()}.";
            throw new ProviderRequestException('gemini', $response->status(), $message);
        }

        $json = (array) $response->json();
        $content = (string) data_get($json, 'candidates.0.content.parts.0.text', '');

        return [
            'provider' => $this->key(),
            'model' => $payload['model'],
            'content' => $content,
            'usage' => [
                'prompt_tokens' => (int) data_get($json, 'usageMetadata.promptTokenCount', 0),
                'completion_tokens' => (int) data_get($json, 'usageMetadata.candidatesTokenCount', 0),
                'total_tokens' => (int) data_get($json, 'usageMetadata.totalTokenCount', 0),
            ],
            'raw' => $json,
        ];
    }
}
