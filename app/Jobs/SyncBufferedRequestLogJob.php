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

class SyncBufferedRequestLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $bufferKey) {}

    public function handle(): void
    {
        $payload = Cache::pull($this->bufferKey);
        if (! is_array($payload)) {
            return;
        }

        try {
            RequestLog::query()->create([
                'tenant_id' => (int) ($payload['tenant_id'] ?? 0),
                'api_key_id' => $payload['api_key_id'] ?? null,
                'provider' => (string) ($payload['provider'] ?? ''),
                'model' => (string) ($payload['model'] ?? ''),
                'status' => (string) ($payload['status'] ?? 'failed'),
                'is_stream' => (bool) ($payload['is_stream'] ?? false),
                'latency_ms' => (int) ($payload['latency_ms'] ?? 0),
                'prompt_tokens' => (int) ($payload['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($payload['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($payload['total_tokens'] ?? 0),
                'request_payload' => is_array($payload['request_payload'] ?? null) ? $payload['request_payload'] : [],
                'response_payload' => is_array($payload['response_payload'] ?? null) ? $payload['response_payload'] : [],
                'error_message' => $payload['error_message'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Buffered request log sync failed.', [
                'buffer_key' => $this->bufferKey,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
        }
    }
}
