<?php

namespace App\Services;

use App\AI\Exceptions\TokenLimitExceededException;
use App\AI\ProviderRegistry;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiModel;
use App\Models\ProviderCredential;
use App\Models\RequestLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class UnifiedChatService
{
    public function __construct(private readonly ProviderRegistry $providers) {}

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function handle(int $tenantId, ?int $apiKeyId, array $payload): array
    {
        $usageState = $this->getOrCreateUsageState($tenantId);
        $this->assertUserWithinTokenLimit($usageState);
        $payload = $this->applyTokenBudgetToPayload($payload, $usageState);
        $payload = $this->ensureProviderFromModel($payload);

        $conversation = $this->resolveConversation($tenantId, $payload);
        $preferred = (string) ($payload['provider'] ?? config('ai.default_provider'));
        $fallbacks = $payload['fallback_providers'] ?? config('ai.fallback_providers', []);
        $providerOrder = collect(array_merge([$preferred], $fallbacks))
            ->map(fn ($provider) => (string) $provider)
            ->unique()
            ->values();

        $lastException = null;
        foreach ($providerOrder as $providerKey) {
            $providerApiKey = $this->resolveProviderApiKey($tenantId, $providerKey);
            if (! $providerApiKey) {
                continue;
            }

            $start = microtime(true);
            try {
                $response = $this->providers->get($providerKey)->chat($payload, $providerApiKey);
                $latencyMs = (int) round((microtime(true) - $start) * 1000);
                $usage = (array) ($response['usage'] ?? []);

                $this->storeUsage($tenantId, (int) ($usage['total_tokens'] ?? 0));
                $this->logRequest($tenantId, $apiKeyId, $providerKey, $payload, $response, $latencyMs, null);
                $this->persistConversationHistory($conversation, $payload, $response, $providerKey);
                $response['conversation_id'] = $conversation->id;

                return $response;
            } catch (\Throwable $exception) {
                $lastException = $exception;
                $latencyMs = (int) round((microtime(true) - $start) * 1000);
                $this->logRequest($tenantId, $apiKeyId, $providerKey, $payload, [], $latencyMs, $exception->getMessage());
                $conversation->forceFill([
                    'status' => 'error',
                    'last_error_code' => 'provider_error',
                    'last_error_message' => $exception->getMessage(),
                    'last_used_at' => now(),
                ])->save();
            }
        }

        throw new ConnectionException($lastException?->getMessage() ?? 'No provider succeeded.');
    }

    /**
     * @return array<string,mixed>
     */
    public function getConversationHistory(int $tenantId, int $conversationId, int $limit = 100): array
    {
        $connection = DB::connection((string) config('ai.history_connection'));
        $conversation = $connection->table('ai_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $conversation) {
            return [
                'conversation' => null,
                'messages' => [],
            ];
        }

        $messages = $connection->table('ai_messages')
            ->where('ai_conversation_id', $conversationId)
            ->orderBy('id', 'desc')
            ->limit(max(1, $limit))
            ->get()
            ->reverse()
            ->values();

        return [
            'conversation' => (array) $conversation,
            'messages' => $messages->map(fn ($message) => (array) $message)->values()->all(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function listConversationHistories(int $tenantId, int $limit = 20, int $page = 1): array
    {
        $connection = DB::connection((string) config('ai.history_connection'));
        $limit = max(1, min($limit, 100));
        $page = max(1, $page);

        $latestAssistantSub = $connection->table('ai_messages')
            ->selectRaw('MAX(id) as id, ai_conversation_id')
            ->where('role', 'assistant')
            ->groupBy('ai_conversation_id');

        $query = $connection->table('ai_conversations')
            ->leftJoinSub($latestAssistantSub, 'last_message_ids', function ($join): void {
                $join->on('last_message_ids.ai_conversation_id', '=', 'ai_conversations.id');
            })
            ->leftJoin('ai_messages as last_message', 'last_message.id', '=', 'last_message_ids.id')
            ->where('ai_conversations.user_id', $tenantId)
            ->select([
                'ai_conversations.*',
                'last_message.content as last_assistant_message',
            ])
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $limit)->get();

        $conversations = $rows->map(function ($conversation): array {
            return [
                'id' => (int) $conversation->id,
                'subject' => (string) ($conversation->subject ?? ''),
                'status' => (string) ($conversation->status ?? 'active'),
                'model' => (string) ($conversation->model ?? ''),
                'purpose' => (string) ($conversation->purpose ?? ''),
                'total_prompt_tokens' => (int) ($conversation->total_prompt_tokens ?? 0),
                'total_completion_tokens' => (int) ($conversation->total_completion_tokens ?? 0),
                'total_tokens' => (int) ($conversation->total_tokens ?? 0),
                'last_used_at' => (string) ($conversation->last_used_at ?? ''),
                'created_at' => (string) ($conversation->created_at ?? ''),
                'updated_at' => (string) ($conversation->updated_at ?? ''),
                'last_assistant_message' => (string) ($conversation->last_assistant_message ?? ''),
            ];
        })->values()->all();

        return [
            'tenant_id' => $tenantId,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'conversations' => $conversations,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function searchConversationHistories(int $tenantId, string $query, int $limit = 20, int $page = 1): array
    {
        $connection = DB::connection((string) config('ai.history_connection'));
        $limit = max(1, min($limit, 100));
        $page = max(1, $page);
        $term = trim($query);

        if ($term === '') {
            return $this->listConversationHistories($tenantId, $limit, $page);
        }

        $latestAssistantSub = $connection->table('ai_messages')
            ->selectRaw('MAX(id) as id, ai_conversation_id')
            ->where('role', 'assistant')
            ->groupBy('ai_conversation_id');

        $queryBuilder = $connection->table('ai_conversations')
            ->leftJoinSub($latestAssistantSub, 'last_message_ids', function ($join): void {
                $join->on('last_message_ids.ai_conversation_id', '=', 'ai_conversations.id');
            })
            ->leftJoin('ai_messages as last_message', 'last_message.id', '=', 'last_message_ids.id')
            ->where('ai_conversations.user_id', $tenantId)
            ->where(function ($search) use ($term): void {
                $likeTerm = '%'.$term.'%';
                $search->where('ai_conversations.subject', 'like', $likeTerm)
                    ->orWhere('last_message.content', 'like', $likeTerm);
            })
            ->select([
                'ai_conversations.*',
                'last_message.content as last_assistant_message',
            ])
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        $total = (clone $queryBuilder)->count();
        $rows = $queryBuilder->forPage($page, $limit)->get();

        $conversations = $rows->map(function ($conversation): array {
            return [
                'id' => (int) $conversation->id,
                'subject' => (string) ($conversation->subject ?? ''),
                'status' => (string) ($conversation->status ?? 'active'),
                'model' => (string) ($conversation->model ?? ''),
                'purpose' => (string) ($conversation->purpose ?? ''),
                'total_prompt_tokens' => (int) ($conversation->total_prompt_tokens ?? 0),
                'total_completion_tokens' => (int) ($conversation->total_completion_tokens ?? 0),
                'total_tokens' => (int) ($conversation->total_tokens ?? 0),
                'last_used_at' => (string) ($conversation->last_used_at ?? ''),
                'created_at' => (string) ($conversation->created_at ?? ''),
                'updated_at' => (string) ($conversation->updated_at ?? ''),
                'last_assistant_message' => (string) ($conversation->last_assistant_message ?? ''),
            ];
        })->values()->all();

        return [
            'tenant_id' => $tenantId,
            'query' => $term,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'conversations' => $conversations,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getUsageSummary(int $tenantId): array
    {
        $totalUsage = $this->getOrCreateUsageState($tenantId);
        $requests = RequestLog::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return [
            'tenant_id' => $tenantId,
            'total_tokens' => (int) ($totalUsage->total_tokens ?? 0),
            'token_limit' => (int) ($totalUsage->token_limit ?? config('ai.default_user_token_limit', 10000)),
            'remaining_tokens' => max(
                0,
                (int) ($totalUsage->token_limit ?? config('ai.default_user_token_limit', 10000)) - (int) ($totalUsage->total_tokens ?? 0)
            ),
            'recent_requests' => $requests->map(fn (RequestLog $log) => [
                'id' => $log->id,
                'provider' => $log->provider,
                'model' => $log->model,
                'status' => $log->status,
                'latency_ms' => $log->latency_ms,
                'prompt_tokens' => $log->prompt_tokens,
                'completion_tokens' => $log->completion_tokens,
                'total_tokens' => $log->total_tokens,
                'created_at' => optional($log->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listModels(): array
    {
        $models = AiModel::query()
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('display_name')
            ->get();

        return $models->map(fn (AiModel $model) => [
            'id' => $model->id,
            'provider' => $model->provider,
            'model' => $model->model_key,
            'display_name' => $model->display_name,
            'description' => $model->description,
            'max_output_tokens' => $model->max_output_tokens,
            'supports_streaming' => $model->supports_streaming,
            'supports_vision' => $model->supports_vision,
            'supports_reasoning' => $model->supports_reasoning,
            'supports_web_search' => $model->supports_web_search,
            'supports_tools' => $model->supports_tools,
            'is_default' => $model->is_default,
            'metadata' => $model->metadata ?? [],
        ])->values()->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listProviderCredentials(int $tenantId): array
    {
        $providers = array_keys((array) config('ai.providers', []));
        $credentials = ProviderCredential::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('provider');

        return collect($providers)->map(function (string $provider) use ($credentials): array {
            $credential = $credentials->get($provider);

            return [
                'provider' => $provider,
                'has_custom_key' => $credential !== null,
                'is_active' => (bool) ($credential?->is_active ?? false),
                'last_used_at' => optional($credential?->last_used_at)?->toIso8601String(),
            ];
        })->values()->all();
    }

    /**
     * @return array<string,mixed>
     */
    public function storeProviderCredential(int $tenantId, string $provider, string $apiKey, bool $isActive = true): array
    {
        $credential = ProviderCredential::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'provider' => $provider,
            ],
            [
                'encrypted_api_key' => $apiKey,
                'is_active' => $isActive,
                'meta' => ['source' => 'user'],
            ]
        );

        return [
            'provider' => $credential->provider,
            'has_custom_key' => true,
            'is_active' => (bool) $credential->is_active,
        ];
    }

    private function resolveProviderApiKey(int $tenantId, string $provider): ?string
    {
        $tenantCredential = ProviderCredential::query()
            ->where('tenant_id', $tenantId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if ($tenantCredential && is_string($tenantCredential->encrypted_api_key) && $tenantCredential->encrypted_api_key !== '') {
            return $tenantCredential->encrypted_api_key;
        }

        return config("ai.providers.{$provider}.api_key");
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,mixed>  $response
     */
    private function logRequest(
        int $tenantId,
        ?int $apiKeyId,
        string $provider,
        array $payload,
        array $response,
        int $latencyMs,
        ?string $errorMessage
    ): void {
        RequestLog::query()->create([
            'tenant_id' => $tenantId,
            'api_key_id' => $apiKeyId,
            'provider' => $provider,
            'model' => (string) ($payload['model'] ?? config('ai.default_model')),
            'status' => $errorMessage ? 'failed' : 'success',
            'is_stream' => (bool) ($payload['stream'] ?? false),
            'latency_ms' => $latencyMs,
            'prompt_tokens' => (int) data_get($response, 'usage.prompt_tokens', 0),
            'completion_tokens' => (int) data_get($response, 'usage.completion_tokens', 0),
            'total_tokens' => (int) data_get($response, 'usage.total_tokens', 0),
            'request_payload' => $payload,
            'response_payload' => $response,
            'error_message' => $errorMessage,
        ]);
    }

    private function storeUsage(int $tenantId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $connection = DB::connection((string) config('ai.usage_connection'));
        $existing = $connection->table('ai_user_usages')->where('user_id', $tenantId)->first();
        $defaultTokenLimit = (int) config('ai.default_user_token_limit', 10000);

        if ($existing) {
            $connection->table('ai_user_usages')
                ->where('user_id', $tenantId)
                ->update([
                    'total_tokens' => (int) $existing->total_tokens + $tokens,
                    'token_limit' => (int) ($existing->token_limit ?? $defaultTokenLimit),
                    'updated_at' => now(),
                ]);

            return;
        }

        $connection->table('ai_user_usages')->insert([
            'user_id' => $tenantId,
            'total_tokens' => $tokens,
            'token_limit' => $defaultTokenLimit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveConversation(int $tenantId, array $payload): AiConversation
    {
        $historyConnection = (string) config('ai.history_connection');
        $conversationId = (int) ($payload['conversation_id'] ?? 0);

        $conversation = null;
        if ($conversationId > 0) {
            $conversation = AiConversation::on($historyConnection)
                ->where('id', $conversationId)
                ->where('user_id', $tenantId)
                ->first();
        }

        if ($conversation) {
            return $conversation;
        }

        $subject = (string) ($payload['subject'] ?? '');
        if ($subject === '') {
            $subject = $this->deriveConversationSubject((array) ($payload['messages'] ?? []));
        }

        return AiConversation::on($historyConnection)->create([
            'user_id' => $tenantId,
            'subject' => $subject,
            'status' => 'active',
            'model' => (string) ($payload['model'] ?? config('ai.default_model')),
            'purpose' => (string) ($payload['purpose'] ?? 'chat'),
            'settings' => [
                'provider' => (string) ($payload['provider'] ?? config('ai.default_provider')),
                'temperature' => $payload['temperature'] ?? null,
                'max_tokens' => $payload['max_tokens'] ?? null,
            ],
            'last_used_at' => now(),
        ]);
    }

    private function persistConversationHistory(AiConversation $conversation, array $payload, array $response, string $provider): void
    {
        if (($payload['save_history'] ?? true) !== true) {
            return;
        }

        $historyConnection = (string) config('ai.history_connection');
        $usage = (array) ($response['usage'] ?? []);
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? 0);

        $latestUserMessage = $this->extractLatestUserMessage((array) ($payload['messages'] ?? []));
        if ($latestUserMessage !== null && $this->shouldPersistUserMessage($conversation, $latestUserMessage, $historyConnection)) {
            AiMessage::on($historyConnection)->create([
                'ai_conversation_id' => $conversation->id,
                'user_id' => $conversation->user_id,
                'content' => $latestUserMessage,
                'role' => 'user',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'raw_request' => null,
                'raw_response' => null,
            ]);
        }

        AiMessage::on($historyConnection)->create([
            'ai_conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'content' => (string) ($response['content'] ?? ''),
            'role' => 'assistant',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'raw_request' => $payload,
            'raw_response' => $response,
        ]);

        $conversation->forceFill([
            'status' => 'active',
            'model' => (string) ($response['model'] ?? $payload['model'] ?? $conversation->model),
            'total_prompt_tokens' => (int) $conversation->total_prompt_tokens + $promptTokens,
            'total_completion_tokens' => (int) $conversation->total_completion_tokens + $completionTokens,
            'total_tokens' => (int) $conversation->total_tokens + $totalTokens,
            'last_used_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
            'settings' => array_merge((array) $conversation->settings, [
                'last_provider' => $provider,
            ]),
        ])->save();
    }

    private function getOrCreateUsageState(int $tenantId): object
    {
        $connection = DB::connection((string) config('ai.usage_connection'));
        $usage = $connection->table('ai_user_usages')->where('user_id', $tenantId)->first();

        if ($usage) {
            return $usage;
        }

        $defaultTokenLimit = (int) config('ai.default_user_token_limit', 10000);
        $connection->table('ai_user_usages')->insert([
            'user_id' => $tenantId,
            'total_tokens' => 0,
            'token_limit' => $defaultTokenLimit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) [
            'user_id' => $tenantId,
            'total_tokens' => 0,
            'token_limit' => $defaultTokenLimit,
        ];
    }

    private function assertUserWithinTokenLimit(object $usageState): void
    {
        $usedTokens = (int) ($usageState->total_tokens ?? 0);
        $tokenLimit = (int) ($usageState->token_limit ?? config('ai.default_user_token_limit', 10000));

        if ($usedTokens >= $tokenLimit) {
            throw new TokenLimitExceededException($tokenLimit, $usedTokens);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function applyTokenBudgetToPayload(array $payload, object $usageState): array
    {
        $usedTokens = (int) ($usageState->total_tokens ?? 0);
        $tokenLimit = (int) ($usageState->token_limit ?? config('ai.default_user_token_limit', 10000));
        $remaining = max(1, $tokenLimit - $usedTokens);
        $requestedMaxTokens = (int) ($payload['max_tokens'] ?? 1024);

        $payload['max_tokens'] = max(1, min($requestedMaxTokens, $remaining));

        return $payload;
    }

    /**
     * @param  array<int,array<string,mixed>>  $messages
     */
    private function extractLatestUserMessage(array $messages): ?string
    {
        $latestUserMessage = collect($messages)
            ->reverse()
            ->first(fn ($message) => is_array($message) && ($message['role'] ?? null) === 'user');

        $content = trim((string) data_get($latestUserMessage, 'content', ''));

        return $content === '' ? null : $content;
    }

    private function shouldPersistUserMessage(AiConversation $conversation, string $content, string $historyConnection): bool
    {
        $lastUserMessage = AiMessage::on($historyConnection)
            ->where('ai_conversation_id', $conversation->id)
            ->where('role', 'user')
            ->orderByDesc('id')
            ->first();

        return ! $lastUserMessage || trim((string) $lastUserMessage->content) !== $content;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function ensureProviderFromModel(array $payload): array
    {
        if (! empty($payload['provider'])) {
            return $payload;
        }

        $modelKey = (string) ($payload['model'] ?? config('ai.default_model'));
        $provider = AiModel::query()
            ->where('model_key', $modelKey)
            ->where('is_active', true)
            ->value('provider');

        $payload['provider'] = is_string($provider) && $provider !== ''
            ? $provider
            : (string) config('ai.default_provider');

        return $payload;
    }

    /**
     * @param  array<int,array<string,mixed>>  $messages
     */
    private function deriveConversationSubject(array $messages): string
    {
        $subject = collect($messages)
            ->reverse()
            ->first(fn ($message) => is_array($message) && ($message['role'] ?? null) === 'user');

        $text = trim((string) data_get($subject, 'content', 'New Conversation'));
        if ($text === '') {
            return 'New Conversation';
        }

        return mb_substr($text, 0, 80);
    }
}
