<?php

namespace App\AI\Providers;

use App\AI\Exceptions\ProviderRequestException;
class GrokProvider extends OpenAIProvider
{
    public function key(): string
    {
        return 'grok';
    }

    public function chat(array $payload, string $apiKey): array
    {
        try {
            return parent::chat($payload, $apiKey);
        } catch (ProviderRequestException $exception) {
            $requestedModel = trim((string) ($payload['model'] ?? ''));
            $shouldRetryWithLatest = $exception->statusCode === 400
                && $requestedModel === 'grok-2-mini';

            if (! $shouldRetryWithLatest) {
                throw $exception;
            }

            $this->providerLogger($this->key())->warning('Grok model fallback triggered.', [
                'requested_model' => $requestedModel,
                'fallback_model' => 'grok-2-latest',
                'provider_status' => $exception->statusCode,
                'error' => $exception->getMessage(),
            ]);

            $fallbackPayload = $payload;
            $fallbackPayload['model'] = 'grok-2-latest';

            return parent::chat($fallbackPayload, $apiKey);
        }
    }
}
