<?php

namespace App\AI\Providers;

use App\AI\Contracts\ChatProviderInterface;
use App\AI\Exceptions\ProviderRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements ChatProviderInterface
{
    public function key(): string
    {
        return 'openai';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildChatCompletionRequestBody(array $payload, bool $stream): array
    {
        $requestBody = [
            'model' => (string) ($payload['model'] ?? ''),
            'messages' => (array) ($payload['messages'] ?? []),
            'stream' => $stream,
        ];

        if ($stream && (bool) config('ai.openai_stream_include_usage', true)) {
            $requestBody['stream_options'] = ['include_usage' => true];
        }

        if (isset($payload['temperature']) && is_numeric($payload['temperature'])) {
            $requestBody['temperature'] = (float) $payload['temperature'];
        }

        if (isset($payload['max_tokens']) && is_numeric($payload['max_tokens']) && (int) $payload['max_tokens'] > 0) {
            $requestBody['max_tokens'] = (int) $payload['max_tokens'];
        }

        return $requestBody;
    }

    public function chat(array $payload, string $apiKey): array
    {
        $providerKey = $this->key();
        $requestBody = $this->buildChatCompletionRequestBody($payload, false);

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

    /**
     * @param  callable(string): void  $onDelta
     * @return array<string, mixed>
     */
    public function chatStream(array $payload, string $apiKey, callable $onDelta): array
    {
        $providerKey = $this->key();
        $requestBody = $this->buildChatCompletionRequestBody($payload, true);
        $url = rtrim((string) config("ai.providers.{$providerKey}.base_url"), '/').'/chat/completions';

        $client = new Client([
            'timeout' => (float) config('ai.chat_stream_timeout_seconds', 300),
            'connect_timeout' => (float) max(2, min((int) config('ai.request_timeout_seconds', 12), 8)),
        ]);

        try {
            $guzzleResponse = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ],
                'json' => $requestBody,
                'stream' => true,
                'http_errors' => false,
            ]);
        } catch (GuzzleConnectException $exception) {
            $this->providerLogger($providerKey)->warning('Provider stream connection error.', [
                'provider' => $providerKey,
                'model' => (string) ($requestBody['model'] ?? ''),
                'error' => $exception->getMessage(),
            ]);
            throw new ConnectionException($exception->getMessage(), 0, $exception);
        } catch (TransferException $exception) {
            $this->providerLogger($providerKey)->warning('Provider stream transfer error.', [
                'provider' => $providerKey,
                'model' => (string) ($requestBody['model'] ?? ''),
                'error' => $exception->getMessage(),
            ]);
            throw new ConnectionException($exception->getMessage(), 0, $exception);
        }

        $status = $guzzleResponse->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $errorBody = trim((string) $guzzleResponse->getBody());
            $decoded = json_decode($errorBody, true);
            $upstreamMessage = is_array($decoded)
                ? trim((string) data_get($decoded, 'error.message', ''))
                : '';
            if ($upstreamMessage === '') {
                $upstreamMessage = $errorBody;
            }
            $message = $upstreamMessage !== ''
                ? ucfirst($providerKey)." request failed with status {$status}: {$upstreamMessage}"
                : ucfirst($providerKey)." request failed with status {$status}.";
            $this->providerLogger($providerKey)->warning('Provider stream request failed.', [
                'provider' => $providerKey,
                'model' => (string) ($requestBody['model'] ?? ''),
                'status' => $status,
                'error' => $message,
            ]);
            throw new ProviderRequestException($providerKey, $status, $message);
        }

        $streamModel = (string) ($payload['model'] ?? '');
        $fullContent = '';
        $usage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];

        $body = $guzzleResponse->getBody();
        $buffer = '';
        while (! $body->eof()) {
            $buffer .= $body->read(8192);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);
                if ($line === '' || $line === 'data: [DONE]') {
                    continue;
                }
                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }
                $jsonPayload = substr($line, 6);
                $chunk = json_decode($jsonPayload, true);
                if (! is_array($chunk)) {
                    continue;
                }
                if (isset($chunk['error'])) {
                    $err = (array) $chunk['error'];
                    $upstreamMessage = trim((string) ($err['message'] ?? json_encode($err)));
                    $message = $upstreamMessage !== ''
                        ? ucfirst($providerKey)." stream error: {$upstreamMessage}"
                        : ucfirst($providerKey).' stream error.';
                    throw new ProviderRequestException($providerKey, 422, $message);
                }
                $chunkModel = (string) data_get($chunk, 'model', '');
                if ($chunkModel !== '') {
                    $streamModel = $chunkModel;
                }
                $piece = (string) data_get($chunk, 'choices.0.delta.content', '');
                if ($piece !== '') {
                    $fullContent .= $piece;
                    $onDelta($piece);
                }
                $usageChunk = data_get($chunk, 'usage');
                if (is_array($usageChunk)) {
                    $usage = [
                        'prompt_tokens' => (int) ($usageChunk['prompt_tokens'] ?? $usage['prompt_tokens']),
                        'completion_tokens' => (int) ($usageChunk['completion_tokens'] ?? $usage['completion_tokens']),
                        'total_tokens' => (int) ($usageChunk['total_tokens'] ?? $usage['total_tokens']),
                    ];
                }
            }
        }

        if ($usage['total_tokens'] === 0 && $fullContent !== '') {
            $usage['completion_tokens'] = (int) round(strlen($fullContent) / 4);
            $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
        }

        return [
            'provider' => $this->key(),
            'model' => $streamModel,
            'content' => $fullContent,
            'usage' => $usage,
            'raw' => [],
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
