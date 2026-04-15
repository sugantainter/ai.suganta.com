<?php

namespace App\Http\Controllers\Api;

use App\AI\Exceptions\TokenLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Services\UnifiedChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private readonly UnifiedChatService $chatService) {}

    public function chat(ChatRequest $request): JsonResponse|StreamedResponse
    {
        $payload = $request->validated();
        $payload['provider'] = $payload['provider'] ?? config('ai.default_provider');
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

    public function usage(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        return response()->json($this->chatService->getUsageSummary($tenantId));
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'models' => $this->chatService->listModels(),
        ]);
    }

}
