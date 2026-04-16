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
        $requestedModel = trim((string) ($payload['model'] ?? ''));
        $candidateModels = $this->resolveModelCandidates($requestedModel);
        $lastException = null;

        foreach ($candidateModels as $candidateModel) {
            $attemptPayload = $payload;
            $attemptPayload['model'] = $candidateModel;

            try {
                return parent::chat($attemptPayload, $apiKey);
            } catch (ProviderRequestException $exception) {
                $lastException = $exception;
                if (! $this->isModelNotFoundError($exception)) {
                    throw $exception;
                }

                $this->providerLogger($this->key())->warning('Grok model candidate unavailable, trying next candidate.', [
                    'requested_model' => $requestedModel,
                    'candidate_model' => $candidateModel,
                    'provider_status' => $exception->statusCode,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($lastException instanceof ProviderRequestException) {
            throw $lastException;
        }

        throw new ProviderRequestException($this->key(), 400, 'Grok request failed because no valid model candidate is configured.');
    }

    /**
     * @return array<int,string>
     */
    private function resolveModelCandidates(string $requestedModel): array
    {
        $aliases = (array) config('ai.providers.grok.model_aliases', []);
        $mappedAlias = trim((string) ($aliases[$requestedModel] ?? ''));
        $fallbackCandidates = array_values(array_filter(array_map(
            static fn ($candidate): string => trim((string) $candidate),
            (array) config('ai.providers.grok.model_fallback_candidates', [])
        ), static fn (string $candidate): bool => $candidate !== ''));

        return collect([$mappedAlias, $requestedModel, ...$fallbackCandidates])
            ->map(static fn ($model): string => trim((string) $model))
            ->filter(static fn (string $model): bool => $model !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function isModelNotFoundError(ProviderRequestException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return $exception->statusCode === 400
            && (str_contains($message, 'model not found') || str_contains($message, 'invalid argument'));
    }
}
