<?php

namespace App\Jobs;

use App\Models\RequestLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SyncBufferedRequestLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ?string $bufferKey = null) {}

    public function handle(): void
    {
        $rows = [];
        $now = now();

        if (is_string($this->bufferKey) && $this->bufferKey !== '') {
            $payload = Cache::pull($this->bufferKey);
            if (is_array($payload)) {
                $row = $this->normalizeRow($payload, $now);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        $batchSize = max(1, min((int) config('ai.request_log_batch_size', 100), 1000));
        $queueKey = (string) config('ai.request_log_redis_queue_key', 'ai:reqlog:queue');

        try {
            for ($i = 0; $i < $batchSize; $i++) {
                $encoded = Redis::lpop($queueKey);
                if (! is_string($encoded) || $encoded === '') {
                    break;
                }
                $decoded = json_decode($encoded, true);
                if (! is_array($decoded)) {
                    continue;
                }
                $row = $this->normalizeRow($decoded, $now);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Buffered request log sync failed.', [
                'buffer_key' => $this->bufferKey,
                'queue_key' => $queueKey,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return;
        }

        if ($rows === []) {
            return;
        }

        try {
            RequestLog::query()->insert($rows);
        } catch (\Throwable $exception) {
            Log::warning('Buffered request log bulk insert failed.', [
                'row_count' => count($rows),
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>|null
     */
    private function normalizeRow(array $payload, $now): ?array
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return null;
        }

        $requestPayloadJson = json_encode(
            is_array($payload['request_payload'] ?? null) ? $payload['request_payload'] : [],
            JSON_UNESCAPED_UNICODE
        );
        $responsePayloadJson = json_encode(
            is_array($payload['response_payload'] ?? null) ? $payload['response_payload'] : [],
            JSON_UNESCAPED_UNICODE
        );

        return [
            'tenant_id' => $tenantId,
            'api_key_id' => $payload['api_key_id'] ?? null,
            'provider' => (string) ($payload['provider'] ?? ''),
            'model' => (string) ($payload['model'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'failed'),
            'is_stream' => (bool) ($payload['is_stream'] ?? false),
            'latency_ms' => (int) ($payload['latency_ms'] ?? 0),
            'prompt_tokens' => (int) ($payload['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($payload['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($payload['total_tokens'] ?? 0),
            'request_payload' => is_string($requestPayloadJson) ? $requestPayloadJson : '[]',
            'response_payload' => is_string($responsePayloadJson) ? $responsePayloadJson : '[]',
            'error_message' => $payload['error_message'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
