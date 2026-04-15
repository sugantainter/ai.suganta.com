<?php

namespace App\Jobs;

use App\AI\Exceptions\TokenLimitExceededException;
use App\AI\Exceptions\TooManyConcurrentRequestsException;
use App\Services\UnifiedChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAsyncChatJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $jobId,
        public readonly int $tenantId,
        public readonly ?int $apiKeyId,
        public readonly array $payload
    ) {}

    public function handle(UnifiedChatService $chatService): void
    {
        $cacheKey = $this->statusCacheKey();
        Cache::put($cacheKey, [
            'job_id' => $this->jobId,
            'status' => 'processing',
            'updated_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));

        try {
            $result = $chatService->handle($this->tenantId, $this->apiKeyId, $this->payload);
            Cache::put($cacheKey, [
                'job_id' => $this->jobId,
                'status' => 'completed',
                'result' => $result,
                'updated_at' => now()->toIso8601String(),
            ], now()->addMinutes(30));
        } catch (TokenLimitExceededException|TooManyConcurrentRequestsException|ConnectionException $exception) {
            Cache::put($cacheKey, [
                'job_id' => $this->jobId,
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'code' => 'chat_request_failed',
                'updated_at' => now()->toIso8601String(),
            ], now()->addMinutes(30));
        } catch (\Throwable $exception) {
            Log::error('Async chat job failed.', [
                'job_id' => $this->jobId,
                'tenant_id' => $this->tenantId,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            Cache::put($cacheKey, [
                'job_id' => $this->jobId,
                'status' => 'failed',
                'error' => 'Unable to process chat request at this time.',
                'code' => 'chat_request_failed',
                'updated_at' => now()->toIso8601String(),
            ], now()->addMinutes(30));
        }
    }

    private function statusCacheKey(): string
    {
        return "ai:async-chat:job:{$this->jobId}";
    }
}
