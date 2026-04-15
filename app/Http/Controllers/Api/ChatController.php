<?php

namespace App\Http\Controllers\Api;

use App\AI\Exceptions\TokenLimitExceededException;
use App\AI\Exceptions\TooManyConcurrentRequestsException;
use App\AI\Exceptions\ProviderRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Jobs\ProcessAsyncChatJob;
use App\Services\UnifiedChatService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private readonly UnifiedChatService $chatService) {}

    public function chat(ChatRequest $request): JsonResponse|StreamedResponse
    {
        $payload = $request->validated();
        $payload['model'] = $payload['model'] ?? config('ai.default_model');
        $payload['stream'] = (bool) ($payload['stream'] ?? false);

        $tenantId = (int) $request->attributes->get('tenant_id');
        $apiKeyId = $request->attributes->get('api_key_id');
        try {
            $result = $this->chatService->handle($tenantId, $apiKeyId, $payload);
        } catch (TokenLimitExceededException $exception) {
            Log::warning('Chat request blocked by token limit.', [
                'tenant_id' => $tenantId,
                'api_key_id' => $apiKeyId,
                'model' => $payload['model'] ?? null,
                'provider' => $payload['provider'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'token_limit_exceeded',
            ], 429);
        } catch (TooManyConcurrentRequestsException $exception) {
            Log::warning('Chat request throttled by concurrency guard.', [
                'tenant_id' => $tenantId,
                'api_key_id' => $apiKeyId,
                'model' => $payload['model'] ?? null,
                'provider' => $payload['provider'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'chat_concurrency_limited',
            ], 429);
        } catch (ProviderRequestException $exception) {
            Log::warning('Chat provider returned request error.', [
                'tenant_id' => $tenantId,
                'api_key_id' => $apiKeyId,
                'model' => $payload['model'] ?? null,
                'provider' => $payload['provider'] ?? null,
                'provider_name' => $exception->provider,
                'provider_status' => $exception->statusCode,
                'error' => $exception->getMessage(),
            ]);
            return response()->json([
                'message' => $exception->clientMessage(),
                'code' => $exception->errorCode(),
                'provider' => $exception->provider,
                'provider_status' => $exception->statusCode,
            ], in_array($exception->statusCode, [400, 401, 402, 403, 404, 429], true) ? $exception->statusCode : 422);
        } catch (ConnectionException $exception) {
            Log::warning('Chat provider connection/timeout failure.', [
                'tenant_id' => $tenantId,
                'api_key_id' => $apiKeyId,
                'model' => $payload['model'] ?? null,
                'provider' => $payload['provider'] ?? null,
                'stream' => (bool) ($payload['stream'] ?? false),
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            $async = $this->enqueueAsyncChat($tenantId, $apiKeyId, $payload);
            return response()->json([
                'message' => 'Provider is busy. Request moved to async processing.',
                'code' => 'chat_async_queued',
                'job_id' => $async['job_id'],
                'status' => $async['status'],
            ], 202);
        } catch (\Throwable $exception) {
            Log::error('Chat request failed.', [
                'tenant_id' => $tenantId,
                'api_key_id' => $apiKeyId,
                'model' => $payload['model'] ?? null,
                'provider' => $payload['provider'] ?? null,
                'stream' => (bool) ($payload['stream'] ?? false),
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return response()->json([
                'message' => 'Unable to process chat request at this time.',
                'code' => 'chat_request_failed',
            ], 502);
        }

        if (! $payload['stream']) {
            return response()->json([
                'conversation_id' => $result['conversation_id'] ?? null,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'message' => $result['content'],
                'usage' => $result['usage'],
            ]);
        }

        return response()->stream(function () use ($result): void {
            echo 'data: '.json_encode([
                'type' => 'message',
                'conversation_id' => $result['conversation_id'] ?? null,
                'content' => $result['content'],
            ], JSON_UNESCAPED_UNICODE)."\n\n";
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function chatAsync(ChatRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['model'] = $payload['model'] ?? config('ai.default_model');
        $payload['stream'] = false;

        $tenantId = (int) $request->attributes->get('tenant_id');
        $apiKeyId = $request->attributes->get('api_key_id');
        return response()->json($this->enqueueAsyncChat($tenantId, $apiKeyId, $payload), 202);
    }

    public function chatJobStatus(string $jobId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $jobStatus = Cache::get($this->chatJobCacheKey($jobId));
        if (! is_array($jobStatus)) {
            return response()->json([
                'message' => 'Job not found or expired.',
            ], 404);
        }

        if ((int) ($jobStatus['tenant_id'] ?? 0) !== $tenantId) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $status = (string) ($jobStatus['status'] ?? 'queued');
        $pollAfterMs = $status === 'queued' ? 1400 : 900;

        $jobStatus['poll_after_ms'] = $pollAfterMs;
        return response()->json($jobStatus);
    }

    public function history(int $conversationId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $limit = (int) $request->integer('limit', 100);
        $data = $this->chatService->getConversationHistory($tenantId, $conversationId, $limit);

        if ($data['conversation'] === null) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        return response()->json($data);
    }

    public function deleteHistory(int $conversationId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $deleted = $this->chatService->deleteConversation($tenantId, $conversationId);
        if (! $deleted) {
            return response()->json([
                'message' => 'Conversation not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Conversation deleted successfully.',
            'conversation_id' => $conversationId,
            'deleted' => true,
        ]);
    }

    public function histories(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $limit = (int) $request->integer('limit', 20);
        $page = (int) $request->integer('page', 1);

        return response()->json($this->chatService->listConversationHistories($tenantId, $limit, $page));
    }

    public function searchHistories(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $query = (string) $request->query('q', '');
        $limit = (int) $request->integer('limit', 20);
        $page = (int) $request->integer('page', 1);

        return response()->json($this->chatService->searchConversationHistories($tenantId, $query, $limit, $page));
    }

    public function share(int $conversationId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $result = $this->chatService->createConversationShareLink($tenantId, $conversationId);
        if ($result['conversation'] === null) {
            return response()->json([
                'message' => 'Conversation not found.',
            ], 404);
        }

        $token = (string) ($result['share_token'] ?? '');
        $baseUrl = rtrim((string) config('app.url', $request->getSchemeAndHttpHost()), '/');

        return response()->json([
            'conversation' => $result['conversation'],
            'share_token' => $token,
            'share_url' => "{$baseUrl}/share/{$token}",
            'share_enabled' => (bool) ($result['share_enabled'] ?? false),
        ]);
    }

    public function sharedHistory(string $shareToken, Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 200);
        $data = $this->chatService->getSharedConversationHistory($shareToken, $limit);
        if ($data['conversation'] === null) {
            return response()->json([
                'message' => 'Shared conversation not found.',
            ], 404);
        }

        return response()->json($data);
    }

    public function usage(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        return response()->json($this->chatService->getUsageSummary($tenantId));
    }

    public function assets(int $conversationId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $limit = (int) $request->integer('limit', 50);

        return response()->json([
            'conversation_id' => $conversationId,
            'assets' => $this->chatService->listConversationAssets($tenantId, $conversationId, $limit),
        ]);
    }

    public function downloadAsset(int $conversationId, int $assetId, Request $request): StreamedResponse|JsonResponse
    {
        if ($request->query('signature') !== null && ! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired signed URL.',
            ], 403);
        }

        $tenantId = (int) $request->attributes->get('tenant_id');
        $asset = $this->chatService->getConversationAsset($tenantId, $conversationId, $assetId);

        if (! $asset) {
            return response()->json([
                'message' => 'Asset not found.',
            ], 404);
        }

        $disk = (string) $asset->storage_disk;
        $path = (string) $asset->storage_path;
        if (! Storage::disk($disk)->exists($path)) {
            return response()->json([
                'message' => 'Asset file is missing from storage.',
            ], 404);
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            return response()->json([
                'message' => 'Unable to open asset file.',
            ], 500);
        }

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, (string) $asset->name, [
            'Content-Type' => (string) ($asset->mime_type ?: 'application/octet-stream'),
        ]);
    }

    public function signedAssetUrl(int $conversationId, int $assetId, Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $asset = $this->chatService->getConversationAsset($tenantId, $conversationId, $assetId);
        if (! $asset) {
            return response()->json([
                'message' => 'Asset not found.',
            ], 404);
        }

        $expiresMinutes = max(1, min((int) $request->integer('expires_minutes', 10), 60));
        $signedUrl = URL::temporarySignedRoute(
            'api.v1.chat.assets.download',
            now()->addMinutes($expiresMinutes),
            [
                'conversationId' => $conversationId,
                'assetId' => $assetId,
            ]
        );

        return response()->json([
            'asset_id' => $assetId,
            'conversation_id' => $conversationId,
            'expires_in_minutes' => $expiresMinutes,
            'signed_url' => $signedUrl,
        ]);
    }

    public function models(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        return response()->json([
            'models' => $this->chatService->listAvailableModelsForTenant($tenantId),
        ]);
    }

    private function chatJobCacheKey(string $jobId): string
    {
        return "ai:async-chat:job:{$jobId}";
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{job_id: string, status: string, tenant_id: int}
     */
    private function enqueueAsyncChat(int $tenantId, ?int $apiKeyId, array $payload): array
    {
        $jobId = (string) Str::uuid();
        $cacheKey = $this->chatJobCacheKey($jobId);

        Cache::put($cacheKey, [
            'job_id' => $jobId,
            'tenant_id' => $tenantId,
            'status' => 'queued',
            'queued_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));

        ProcessAsyncChatJob::dispatch($jobId, $tenantId, $apiKeyId, $payload)
            ->onQueue('ai-chat');

        return [
            'job_id' => $jobId,
            'status' => 'queued',
            'tenant_id' => $tenantId,
        ];
    }

}
