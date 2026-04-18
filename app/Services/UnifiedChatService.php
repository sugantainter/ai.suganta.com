<?php

namespace App\Services;

use App\AI\Exceptions\TokenLimitExceededException;
use App\AI\Exceptions\TooManyConcurrentRequestsException;
use App\AI\ProviderRegistry;
use App\Jobs\SyncBufferedRequestLogJob;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiModel;
use App\Models\AiUploadAsset;
use App\Models\ProviderCredential;
use App\Models\RequestLog;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UnifiedChatService
{
    private const DEFAULT_USER_TOKEN_LIMIT = 100000;

    private const AI_SUBSCRIPTION_TYPE = 3;

    /**
     * @var array<int,int>
     */
    private array $resolvedTokenLimits = [];

    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly AiUploadStorageService $uploadStorageService
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function handle(int $tenantId, ?int $apiKeyId, array $payload): array
    {
        $counterKeys = $this->acquireConcurrencySlots($tenantId);

        try {
            $payload = $this->ensureProviderFromModel($payload);
            $usageState = $this->getOrCreateUsageState($tenantId);
            $activeCustomProviderKeys = $this->getActiveCustomProviderKeys($tenantId);
            $deadlineAt = microtime(true) + max(5, (int) config('ai.max_request_duration_seconds', 20));

            $conversation = $this->resolveConversation($tenantId, $payload);
            $payload = $this->normalizePayloadAttachments($payload, $tenantId, (int) $conversation->id);
            $utilityResponse = $this->buildUtilityResponse($payload);
            if ($utilityResponse !== null) {
                $this->logRequest($tenantId, $apiKeyId, 'system', $payload, $utilityResponse, 0, null);
                $this->persistConversationHistory($conversation, $payload, $utilityResponse, 'system');
                $utilityResponse['conversation_id'] = $conversation->id;
                return $utilityResponse;
            }
            $preferred = (string) ($payload['provider'] ?? config('ai.default_provider'));
            $fallbacks = $payload['fallback_providers'] ?? config('ai.fallback_providers', []);
            $resolvedModelProvider = $this->resolveProviderForModel((string) ($payload['model'] ?? ''));
            if ($resolvedModelProvider !== null) {
                // When model is known, lock routing to its owning provider to prevent
                // cross-provider mismatches (e.g. Grok model sent to DeepSeek fallback).
                $preferred = $resolvedModelProvider;
                $fallbacks = [];
                $payload['provider'] = $resolvedModelProvider;
            }
            $providerPayloadBase = $this->buildProviderPayload($payload);
            $providerPayloadBase = $this->optimizeProviderPayloadForTokenEfficiency($providerPayloadBase, $payload);
            $providerOrder = collect(array_merge([$preferred], $fallbacks))
                ->map(fn ($provider) => (string) $provider)
                ->unique()
                ->values();

            $lastException = null;
            $attemptErrors = [];
            foreach ($providerOrder as $providerKey) {
                if (microtime(true) >= $deadlineAt) {
                    $attemptErrors[] = 'request deadline exceeded';
                    break;
                }

                if ($this->isProviderCircuitOpen($providerKey)) {
                    $attemptErrors[] = "{$providerKey}: circuit breaker open";
                    continue;
                }

                $providerAccess = $this->resolveProviderAccess($providerKey, $activeCustomProviderKeys);
                $usingCustomProviderKey = (bool) ($providerAccess['using_custom_provider_key'] ?? false);
                $providerApiKey = $providerAccess['provider_api_key'] ?? null;
                if (! $providerApiKey) {
                    $attemptErrors[] = "{$providerKey}: missing provider API key";
                    continue;
                }

                $providerPayload = $providerPayloadBase;
                $providerPayload['provider'] = $providerKey;
                if (! $usingCustomProviderKey) {
                    $this->assertUserWithinTokenLimit($usageState);
                }

                $start = microtime(true);
                try {
                    $response = $this->providers->get($providerKey)->chat($providerPayload, $providerApiKey);
                    $latencyMs = (int) round((microtime(true) - $start) * 1000);
                    $usage = (array) ($response['usage'] ?? []);

                    if (! $usingCustomProviderKey) {
                        $consumedTokens = (int) ($usage['total_tokens'] ?? 0);
                        $this->storeUsage($tenantId, $consumedTokens);
                        $usageState->total_tokens = (int) ($usageState->total_tokens ?? 0) + $consumedTokens;
                    }

                    $this->logRequest($tenantId, $apiKeyId, $providerKey, $providerPayload, $response, $latencyMs, null);
                    $this->resetProviderCircuit($providerKey);
                    $this->persistConversationHistory($conversation, $payload, $response, $providerKey);
                    $response['conversation_id'] = $conversation->id;

                    return $response;
                } catch (\Throwable $exception) {
                    $this->recordProviderFailure($providerKey);
                    $lastException = $exception;
                    $attemptErrors[] = "{$providerKey}: ".$exception->getMessage();
                    $latencyMs = (int) round((microtime(true) - $start) * 1000);
                    $this->logRequest($tenantId, $apiKeyId, $providerKey, $providerPayload, [], $latencyMs, $exception->getMessage());
                    $conversation->forceFill([
                        'status' => 'error',
                        'last_error_code' => 'provider_error',
                        'last_error_message' => $exception->getMessage(),
                        'last_used_at' => now(),
                    ])->save();
                }
            }

            $summary = $attemptErrors !== []
                ? ' Attempts: '.implode(' | ', $attemptErrors)
                : '';
            if ($lastException instanceof \Throwable) {
                throw new \RuntimeException('No provider succeeded for this model.'.$summary, previous: $lastException);
            }
            throw new \RuntimeException('No provider succeeded for this model.'.$summary);
        } finally {
            $this->releaseConcurrencySlots($counterKeys);
        }
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

    public function deleteConversation(int $tenantId, int $conversationId): bool
    {
        $conversation = AiConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $conversation) {
            return false;
        }

        $assets = AiUploadAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('ai_conversation_id', $conversationId)
            ->get(['id', 'storage_disk', 'storage_path']);

        DB::connection((string) config('ai.history_connection'))->transaction(function () use ($tenantId, $conversationId): void {
            AiMessage::query()
                ->where('ai_conversation_id', $conversationId)
                ->where('user_id', $tenantId)
                ->delete();

            AiUploadAsset::query()
                ->where('tenant_id', $tenantId)
                ->where('ai_conversation_id', $conversationId)
                ->delete();

            AiConversation::query()
                ->where('id', $conversationId)
                ->where('user_id', $tenantId)
                ->delete();
        });

        foreach ($assets as $asset) {
            $disk = (string) ($asset->storage_disk ?? '');
            $path = (string) ($asset->storage_path ?? '');
            if ($disk === '' || $path === '') {
                continue;
            }
            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable) {
                // Best-effort storage cleanup; DB deletion is the source of truth.
            }
        }

        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function createConversationShareLink(int $tenantId, int $conversationId): array
    {
        $conversation = AiConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $conversation) {
            return [
                'conversation' => null,
                'share_token' => null,
                'share_enabled' => false,
            ];
        }

        $shareToken = (string) ($conversation->share_token ?? '');
        if ($shareToken === '') {
            $shareToken = Str::random(48);
        }

        $conversation->forceFill([
            'is_share_enabled' => true,
            'share_token' => $shareToken,
            'share_expires_at' => null,
        ])->save();

        return [
            'conversation' => [
                'id' => (int) $conversation->id,
                'subject' => (string) ($conversation->subject ?? ''),
            ],
            'share_token' => $shareToken,
            'share_enabled' => true,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getSharedConversationHistory(string $shareToken, int $limit = 100): array
    {
        $token = trim($shareToken);
        if ($token === '') {
            return [
                'conversation' => null,
                'messages' => [],
            ];
        }

        $connection = DB::connection((string) config('ai.history_connection'));
        $conversation = $connection->table('ai_conversations')
            ->where('share_token', $token)
            ->where('is_share_enabled', true)
            ->where(function ($query): void {
                $query->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>', now());
            })
            ->first();

        if (! $conversation) {
            return [
                'conversation' => null,
                'messages' => [],
            ];
        }

        $messages = $connection->table('ai_messages')
            ->where('ai_conversation_id', (int) $conversation->id)
            ->orderBy('id', 'desc')
            ->limit(max(1, $limit))
            ->get()
            ->reverse()
            ->values();

        return [
            'conversation' => [
                'id' => (int) $conversation->id,
                'subject' => (string) ($conversation->subject ?? ''),
                'model' => (string) ($conversation->model ?? ''),
                'last_used_at' => (string) ($conversation->last_used_at ?? ''),
                'share_enabled' => true,
            ],
            'messages' => $messages->map(fn ($message): array => [
                'id' => (int) ($message->id ?? 0),
                'role' => (string) ($message->role ?? 'assistant'),
                'content' => (string) ($message->content ?? ''),
                'created_at' => (string) ($message->created_at ?? ''),
            ])->values()->all(),
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

        $query = $connection->table('ai_conversations')
            ->where('ai_conversations.user_id', $tenantId)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $limit)->get();
        $conversationIds = $rows->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        $lastAssistantMessages = $this->fetchLastAssistantMessages($connection, $conversationIds);

        $conversations = $rows->map(function ($conversation) use ($lastAssistantMessages): array {
            $conversationId = (int) ($conversation->id ?? 0);
            return [
                'id' => $conversationId,
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
                'last_assistant_message' => (string) ($lastAssistantMessages[$conversationId] ?? ''),
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
        $term = mb_substr(trim($query), 0, 120);

        if ($term === '') {
            return $this->listConversationHistories($tenantId, $limit, $page);
        }

        $likeTerm = '%'.$term.'%';
        $queryBuilder = $connection->table('ai_conversations')
            ->where('ai_conversations.user_id', $tenantId)
            ->where(function ($search) use ($likeTerm): void {
                $search->where('ai_conversations.subject', 'like', $likeTerm)
                    ->orWhereExists(function ($exists) use ($likeTerm): void {
                        $exists->selectRaw('1')
                            ->from('ai_messages')
                            ->whereColumn('ai_messages.ai_conversation_id', 'ai_conversations.id')
                            ->where('ai_messages.role', 'assistant')
                            ->where('ai_messages.content', 'like', $likeTerm);
                    });
            })
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        $total = (clone $queryBuilder)->count();
        $rows = $queryBuilder->forPage($page, $limit)->get();
        $conversationIds = $rows->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        $lastAssistantMessages = $this->fetchLastAssistantMessages($connection, $conversationIds);

        $conversations = $rows->map(function ($conversation) use ($lastAssistantMessages): array {
            $conversationId = (int) ($conversation->id ?? 0);
            return [
                'id' => $conversationId,
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
                'last_assistant_message' => (string) ($lastAssistantMessages[$conversationId] ?? ''),
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
     * @param  array<int,int>  $conversationIds
     * @return array<int,string>
     */
    private function fetchLastAssistantMessages($connection, array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $latestAssistantMessageIds = $connection->table('ai_messages')
            ->selectRaw('ai_conversation_id, MAX(id) AS latest_id')
            ->where('role', 'assistant')
            ->whereIn('ai_conversation_id', $conversationIds)
            ->groupBy('ai_conversation_id');

        $messageRows = $connection->table('ai_messages as messages')
            ->joinSub($latestAssistantMessageIds, 'latest_assistant', function ($join): void {
                $join->on('messages.id', '=', 'latest_assistant.latest_id');
            })
            ->select(['messages.ai_conversation_id', 'messages.content'])
            ->get();

        return $messageRows
            ->mapWithKeys(fn ($row): array => [
                (int) ($row->ai_conversation_id ?? 0) => (string) ($row->content ?? ''),
            ])
            ->filter(fn (string $content, int $conversationId): bool => $conversationId > 0)
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    public function getUsageSummary(int $tenantId): array
    {
        $totalUsage = $this->getOrCreateUsageState($tenantId);
        $requests = RequestLog::query()
            ->select([
                'id',
                'tenant_id',
                'provider',
                'model',
                'status',
                'latency_ms',
                'prompt_tokens',
                'completion_tokens',
                'total_tokens',
                'created_at',
            ])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return [
            'tenant_id' => $tenantId,
            'total_tokens' => (int) ($totalUsage->total_tokens ?? 0),
            'token_limit' => (int) ($totalUsage->token_limit ?? self::DEFAULT_USER_TOKEN_LIMIT),
            'remaining_tokens' => max(
                0,
                (int) ($totalUsage->token_limit ?? self::DEFAULT_USER_TOKEN_LIMIT) - (int) ($totalUsage->total_tokens ?? 0)
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
        $cacheKey = 'ai:models:list:v1';
        $ttlSeconds = max(10, (int) config('ai.models_cache_ttl_seconds', 120));

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function (): array {
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
        });
    }

    /**
     * Return only models that this tenant can currently access.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listAvailableModelsForTenant(int $tenantId): array
    {
        $activeCustomProviderKeys = $this->getActiveCustomProviderKeys($tenantId);
        $allModels = $this->listModels();

        $isProviderAvailable = function (string $provider) use ($activeCustomProviderKeys): bool {
            $customKey = $activeCustomProviderKeys[$provider] ?? null;
            if (is_string($customKey) && trim($customKey) !== '') {
                return true;
            }

            $systemKey = (string) config("ai.providers.{$provider}.api_key", '');
            return trim($systemKey) !== '';
        };

        return array_values(array_filter($allModels, function (array $model) use ($isProviderAvailable): bool {
            $provider = (string) ($model['provider'] ?? '');
            if ($provider === '') {
                return false;
            }
            return $isProviderAvailable($provider);
        }));
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

        $this->forgetProviderKeyCache($tenantId);

        return [
            'provider' => $credential->provider,
            'has_custom_key' => true,
            'is_active' => (bool) $credential->is_active,
        ];
    }

    public function removeProviderCredential(int $tenantId, string $provider): bool
    {
        $removed = ProviderCredential::query()
            ->where('tenant_id', $tenantId)
            ->where('provider', $provider)
            ->delete() > 0;

        if ($removed) {
            $this->forgetProviderKeyCache($tenantId);
        }

        return $removed;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listConversationAssets(int $tenantId, int $conversationId, int $limit = 50): array
    {
        $conversation = AiConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $conversation) {
            return [];
        }

        $assets = AiUploadAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('ai_conversation_id', $conversationId)
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 200)))
            ->get();

        return $assets->map(static fn (AiUploadAsset $asset): array => [
            'id' => (int) $asset->id,
            'conversation_id' => (int) ($asset->ai_conversation_id ?? 0),
            'attachment_type' => (string) $asset->attachment_type,
            'name' => (string) $asset->name,
            'mime_type' => (string) ($asset->mime_type ?? ''),
            'size_bytes' => (int) ($asset->size_bytes ?? 0),
            'storage_disk' => (string) $asset->storage_disk,
            'storage_path' => (string) $asset->storage_path,
            'text_preview' => (string) ($asset->text_preview ?? ''),
            'created_at' => optional($asset->created_at)?->toIso8601String(),
        ])->values()->all();
    }

    public function getConversationAsset(int $tenantId, int $conversationId, int $assetId): ?AiUploadAsset
    {
        $conversation = AiConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $conversation) {
            return null;
        }

        return AiUploadAsset::query()
            ->where('id', $assetId)
            ->where('tenant_id', $tenantId)
            ->where('ai_conversation_id', $conversationId)
            ->first();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listUserUploadAssets(int $tenantId, int $limit = 100, int $page = 1): array
    {
        $limit = max(1, min($limit, 200));
        $page = max(1, $page);

        $assets = AiUploadAsset::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();

        return $assets->map(static fn (AiUploadAsset $asset): array => [
            'id' => (int) $asset->id,
            'conversation_id' => (int) ($asset->ai_conversation_id ?? 0),
            'attachment_type' => (string) $asset->attachment_type,
            'name' => (string) $asset->name,
            'mime_type' => (string) ($asset->mime_type ?? ''),
            'size_bytes' => (int) ($asset->size_bytes ?? 0),
            'storage_disk' => (string) $asset->storage_disk,
            'storage_path' => (string) $asset->storage_path,
            'text_preview' => (string) ($asset->text_preview ?? ''),
            'created_at' => optional($asset->created_at)?->toIso8601String(),
        ])->values()->all();
    }

    /**
     * @return array{using_custom_provider_key: bool, provider_api_key: string|null}
     */
    private function resolveProviderAccess(string $provider, array $activeCustomProviderKeys): array
    {
        $customKey = $activeCustomProviderKeys[$provider] ?? null;
        if (is_string($customKey) && $customKey !== '') {
            return [
                'using_custom_provider_key' => true,
                'provider_api_key' => $customKey,
            ];
        }

        return [
            'using_custom_provider_key' => false,
            'provider_api_key' => config("ai.providers.{$provider}.api_key"),
        ];
    }

    /**
     * @return array<string,string>
     */
    private function getActiveCustomProviderKeys(int $tenantId): array
    {
        $cacheKey = $this->providerKeyCacheKey($tenantId);
        $ttlSeconds = max(5, (int) config('ai.provider_keys_cache_ttl_seconds', 30));

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($tenantId): array {
            return ProviderCredential::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get(['provider', 'encrypted_api_key'])
                ->filter(fn (ProviderCredential $credential): bool => is_string($credential->encrypted_api_key) && $credential->encrypted_api_key !== '')
                ->mapWithKeys(fn (ProviderCredential $credential): array => [
                    (string) $credential->provider => (string) $credential->encrypted_api_key,
                ])
                ->all();
        });
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
        $entry = [
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
        ];

        if (! (bool) config('ai.buffer_request_logs_to_redis', true)) {
            RequestLog::query()->create($entry);
            return;
        }

        try {
            $encodedEntry = json_encode($entry, JSON_UNESCAPED_UNICODE);
            if (is_string($encodedEntry) && $encodedEntry !== '') {
                Redis::rpush((string) config('ai.request_log_redis_queue_key', 'ai:reqlog:queue'), $encodedEntry);
            } else {
                throw new \RuntimeException('Unable to encode request log payload.');
            }
            SyncBufferedRequestLogJob::dispatch()->onQueue('ai-sync');
            return;
        } catch (\Throwable) {
            // Fallback to legacy cache buffering when Redis list operations are unavailable.
        }

        $bufferKey = 'ai:reqlog:'.Str::uuid();
        $ttlSeconds = max(60, (int) config('ai.request_log_buffer_ttl_seconds', 600));
        Cache::put($bufferKey, $entry, now()->addSeconds($ttlSeconds));
        SyncBufferedRequestLogJob::dispatch($bufferKey)->onQueue('ai-sync');
    }

    private function storeUsage(int $tenantId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $connection = DB::connection((string) config('ai.usage_connection'));
        $now = now();

        $updatedRows = $connection->table('ai_user_usages')
            ->where('user_id', $tenantId)
            ->increment('total_tokens', $tokens, ['updated_at' => $now]);

        if ($updatedRows > 0) {
            return;
        }

        // Fallback safety for first-time usage rows.
        $resolvedTokenLimit = $this->resolveUserTokenLimit($tenantId);
        $connection->table('ai_user_usages')->upsert([
            [
                'user_id' => $tenantId,
                'total_tokens' => 0,
                'token_limit' => $resolvedTokenLimit,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id'], ['token_limit', 'updated_at']);

        $connection->table('ai_user_usages')
            ->where('user_id', $tenantId)
            ->increment('total_tokens', $tokens, ['updated_at' => $now]);
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
        $resolvedTokenLimit = $this->resolveUserTokenLimit($tenantId);
        $now = now();

        $connection->table('ai_user_usages')->upsert([
            [
                'user_id' => $tenantId,
                'total_tokens' => 0,
                'token_limit' => $resolvedTokenLimit,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id'], ['token_limit', 'updated_at']);

        $usage = $connection->table('ai_user_usages')
            ->where('user_id', $tenantId)
            ->first(['user_id', 'total_tokens', 'token_limit']);

        if ($usage) {
            return $usage;
        }

        return (object) [
            'user_id' => $tenantId,
            'total_tokens' => 0,
            'token_limit' => $resolvedTokenLimit,
        ];
    }

    private function assertUserWithinTokenLimit(object $usageState): void
    {
        $usedTokens = (int) ($usageState->total_tokens ?? 0);
        $tokenLimit = (int) ($usageState->token_limit ?? self::DEFAULT_USER_TOKEN_LIMIT);

        if ($usedTokens >= $tokenLimit) {
            throw new TokenLimitExceededException($tokenLimit, $usedTokens);
        }
    }

    private function resolveUserTokenLimit(int $userId): int
    {
        if (array_key_exists($userId, $this->resolvedTokenLimits)) {
            return $this->resolvedTokenLimits[$userId];
        }

        $subscription = UserSubscription::query()
            ->forUser($userId)
            ->ofType(self::AI_SUBSCRIPTION_TYPE)
            ->active()
            ->orderByDesc('id')
            ->with(['subscriptionPlan:id,ai_tokens'])
            ->first();

        $planTokenLimit = (int) ($subscription?->subscriptionPlan?->ai_tokens ?? 0);

        $resolvedLimit = $planTokenLimit > 0 ? $planTokenLimit : self::DEFAULT_USER_TOKEN_LIMIT;
        $this->resolvedTokenLimits[$userId] = $resolvedLimit;

        return $resolvedLimit;
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
        $lastUserMessageContent = AiMessage::on($historyConnection)
            ->where('ai_conversation_id', $conversation->id)
            ->where('role', 'user')
            ->orderByDesc('id')
            ->value('content');

        return trim((string) $lastUserMessageContent) !== $content;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function ensureProviderFromModel(array $payload): array
    {
        $modelProvider = $this->resolveProviderForModel((string) ($payload['model'] ?? ''));
        if ($modelProvider !== null) {
            $payload['provider'] = $modelProvider;
            return $payload;
        }

        if (! empty($payload['provider'])) {
            return $payload;
        }

        $payload['provider'] = (string) config('ai.default_provider');

        return $payload;
    }

    private function resolveProviderForModel(string $modelKey): ?string
    {
        $normalizedModel = trim($modelKey);
        if ($normalizedModel === '') {
            return null;
        }

        $provider = AiModel::query()
            ->where('model_key', $normalizedModel)
            ->where('is_active', true)
            ->value('provider');

        if (! is_string($provider) || $provider === '') {
            return null;
        }

        return $provider;
    }

    /**
     * @return array{global_key: string, tenant_key: string}
     */
    private function acquireConcurrencySlots(int $tenantId): array
    {
        $globalKey = 'ai:inflight:global';
        $tenantKey = "ai:inflight:tenant:{$tenantId}";
        $ttl = now()->addSeconds(max(5, (int) config('ai.max_request_duration_seconds', 20) + 20));

        $globalCount = Cache::increment($globalKey);
        Cache::put($globalKey, $globalCount, $ttl);

        $tenantCount = Cache::increment($tenantKey);
        Cache::put($tenantKey, $tenantCount, $ttl);

        $maxGlobal = max(100, (int) config('ai.max_in_flight_requests_global', 1200));
        $maxTenant = max(1, (int) config('ai.max_in_flight_requests_per_tenant', 12));

        if ($globalCount > $maxGlobal || $tenantCount > $maxTenant) {
            $this->releaseConcurrencySlots([
                'global_key' => $globalKey,
                'tenant_key' => $tenantKey,
            ]);
            throw new TooManyConcurrentRequestsException();
        }

        return [
            'global_key' => $globalKey,
            'tenant_key' => $tenantKey,
        ];
    }

    /**
     * @param  array{global_key: string, tenant_key: string}  $keys
     */
    private function releaseConcurrencySlots(array $keys): void
    {
        $globalKey = (string) ($keys['global_key'] ?? '');
        $tenantKey = (string) ($keys['tenant_key'] ?? '');
        if ($globalKey !== '') {
            $nextGlobal = max(0, (int) Cache::decrement($globalKey));
            if ($nextGlobal === 0) {
                Cache::forget($globalKey);
            }
        }
        if ($tenantKey !== '') {
            $nextTenant = max(0, (int) Cache::decrement($tenantKey));
            if ($nextTenant === 0) {
                Cache::forget($tenantKey);
            }
        }
    }

    private function providerKeyCacheKey(int $tenantId): string
    {
        return "ai:provider-keys:tenant:{$tenantId}";
    }

    private function forgetProviderKeyCache(int $tenantId): void
    {
        Cache::forget($this->providerKeyCacheKey($tenantId));
    }

    private function providerCircuitOpenKey(string $provider): string
    {
        return "ai:circuit:{$provider}:open_until";
    }

    private function providerCircuitFailuresKey(string $provider): string
    {
        return "ai:circuit:{$provider}:failures";
    }

    private function isProviderCircuitOpen(string $provider): bool
    {
        $openUntil = (int) Cache::get($this->providerCircuitOpenKey($provider), 0);
        return $openUntil > time();
    }

    private function recordProviderFailure(string $provider): void
    {
        $failureThreshold = max(2, (int) config('ai.circuit_breaker_failure_threshold', 3));
        $cooldownSeconds = max(10, (int) config('ai.circuit_breaker_cooldown_seconds', 45));
        $windowSeconds = max(15, (int) config('ai.circuit_breaker_failure_window_seconds', 60));

        $failuresKey = $this->providerCircuitFailuresKey($provider);
        $failures = Cache::increment($failuresKey);
        Cache::put($failuresKey, $failures, now()->addSeconds($windowSeconds));

        if ($failures >= $failureThreshold) {
            Cache::put($this->providerCircuitOpenKey($provider), time() + $cooldownSeconds, now()->addSeconds($cooldownSeconds));
            Cache::forget($failuresKey);
        }
    }

    private function resetProviderCircuit(string $provider): void
    {
        Cache::forget($this->providerCircuitOpenKey($provider));
        Cache::forget($this->providerCircuitFailuresKey($provider));
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

    /**
     * Convert uploaded file/image payloads into chat context.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function normalizePayloadAttachments(array $payload, int $tenantId, int $conversationId): array
    {
        $attachments = $payload['attachments'] ?? null;
        if (! is_array($attachments) || $attachments === []) {
            return $payload;
        }

        $storedAttachments = $this->uploadStorageService->storeAttachments($tenantId, $conversationId, $attachments);
        $normalizedBlocks = collect($storedAttachments)->map(function (array $item): string {
            $name = (string) ($item['name'] ?? 'attachment');
            $type = (string) ($item['type'] ?? 'file');
            $mimeType = (string) ($item['mime_type'] ?? '');
            $preview = mb_substr((string) ($item['preview'] ?? ''), 0, 15000);
            $assetId = (int) ($item['asset_id'] ?? 0);

            $header = '['.ucfirst($type).": {$name}]".($mimeType !== '' ? " ({$mimeType})" : '');
            $storageMarker = $assetId > 0 ? "\nStored Asset ID: {$assetId}" : '';

            return $header.$storageMarker.($preview !== '' ? "\n{$preview}" : "\n(No readable text extracted)");
        })->values()->all();

        if ($normalizedBlocks === []) {
            return $payload;
        }

        $attachmentContext = "\n\nAttached content:\n".implode("\n\n", $normalizedBlocks);
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];

        for ($index = count($messages) - 1; $index >= 0; $index--) {
            $message = $messages[$index] ?? null;
            if (! is_array($message) || ($message['role'] ?? null) !== 'user') {
                continue;
            }

            $content = trim((string) ($message['content'] ?? ''));
            $messages[$index]['content'] = $content.$attachmentContext;
            $payload['messages'] = $messages;

            return $payload;
        }

        $messages[] = [
            'role' => 'user',
            'content' => trim($attachmentContext),
        ];
        $payload['messages'] = $messages;

        return $payload;
    }

    /**
     * Return a deterministic server-side answer for simple utility queries.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>|null
     */
    private function buildUtilityResponse(array $payload): ?array
    {
        $latestUserMessage = $this->extractLatestUserMessage(is_array($payload['messages'] ?? null) ? $payload['messages'] : []);
        if ($latestUserMessage === null) {
            return null;
        }

        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $latestUserMessage) ?? ''));
        $dateQueryPatterns = [
            'today date',
            "today's date",
            'todays date',
            'date today',
            'current date',
            'what is today date',
            "what is today's date",
            'what is the date today',
        ];

        if (! in_array($normalized, $dateQueryPatterns, true)) {
            return null;
        }

        $tz = (string) config('app.timezone', 'UTC');
        return [
            'provider' => 'system',
            'model' => 'server-utility',
            'content' => now()->timezone($tz)->format('l, F j, Y'),
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            'raw' => [
                'type' => 'utility_date_response',
            ],
        ];
    }

    /**
     * Keep provider context intentionally small by default.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function buildProviderPayload(array $payload): array
    {
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $normalizedMessages = collect($messages)
            ->filter(fn ($message): bool => is_array($message))
            ->map(function (array $message): array {
                return [
                    'role' => (string) ($message['role'] ?? 'user'),
                    'content' => trim((string) ($message['content'] ?? '')),
                ];
            })
            ->filter(fn (array $message): bool => $message['content'] !== '')
            ->values();

        if ($normalizedMessages->isEmpty()) {
            return $payload;
        }

        $useFullContext = (bool) ($payload['use_full_context'] ?? false);
        if ($useFullContext) {
            $maxMessages = max(2, min((int) config('ai.provider_context_message_limit', 24), 80));
            $payload['messages'] = $normalizedMessages->slice(-$maxMessages)->values()->all();
            return $payload;
        }

        $latestSystem = $normalizedMessages
            ->reverse()
            ->first(fn (array $message): bool => $message['role'] === 'system');

        $latestUser = $normalizedMessages
            ->reverse()
            ->first(fn (array $message): bool => $message['role'] === 'user');

        $fallbackMessage = $normalizedMessages->last();
        $focusedMessages = collect([$latestSystem, $latestUser ?? $fallbackMessage])
            ->filter(fn ($message): bool => is_array($message) && trim((string) ($message['content'] ?? '')) !== '')
            ->unique(fn (array $message): string => $message['role'].'::'.$message['content'])
            ->values()
            ->all();

        $payload['messages'] = $focusedMessages !== [] ? $focusedMessages : [$fallbackMessage];

        return $payload;
    }

    /**
     * @param  array<string,mixed>  $providerPayload
     * @param  array<string,mixed>  $originalPayload
     * @return array<string,mixed>
     */
    private function optimizeProviderPayloadForTokenEfficiency(array $providerPayload, array $originalPayload): array
    {
        $tokenOptimization = (array) config('ai.token_optimization', []);
        if (! (bool) ($tokenOptimization['enabled'] ?? true)) {
            return $providerPayload;
        }

        $latestUserMessage = $this->extractLatestUserMessage((array) ($originalPayload['messages'] ?? [])) ?? '';
        $wantsDetailedResponse = $this->wantsDetailedResponse($latestUserMessage);
        $requiresComplexResponse = $this->requiresComplexResponse($latestUserMessage);
        $responseStyle = strtolower(trim((string) ($originalPayload['response_style'] ?? 'balanced')));

        $defaultMaxTokens = max(32, (int) ($tokenOptimization['default_max_tokens'] ?? 220));
        $hardCapMaxTokens = max($defaultMaxTokens, (int) ($tokenOptimization['hard_cap_max_tokens'] ?? 320));
        $complexMaxTokens = max($hardCapMaxTokens, (int) ($tokenOptimization['complex_max_tokens'] ?? 700));
        $detailedMaxTokens = max($hardCapMaxTokens, (int) ($tokenOptimization['detailed_max_tokens'] ?? 900));

        $effectiveCap = ($responseStyle === 'detailed' || $wantsDetailedResponse)
            ? $detailedMaxTokens
            : ($requiresComplexResponse ? $complexMaxTokens : $hardCapMaxTokens);
        $requestedMaxTokens = (int) ($providerPayload['max_tokens'] ?? 0);
        if ($requestedMaxTokens > 0) {
            $providerPayload['max_tokens'] = min($requestedMaxTokens, $effectiveCap);
        } else {
            $providerPayload['max_tokens'] = $responseStyle === 'concise'
                ? min($defaultMaxTokens, $hardCapMaxTokens)
                : ((($responseStyle === 'detailed') || $wantsDetailedResponse)
                    ? $complexMaxTokens
                    : ($requiresComplexResponse ? $complexMaxTokens : $defaultMaxTokens));
        }

        $requestedTemperature = (float) ($providerPayload['temperature'] ?? 0.7);
        $providerPayload['temperature'] = $responseStyle === 'concise'
            ? min($requestedTemperature, 0.45)
            : ($responseStyle === 'detailed'
                ? max($requestedTemperature, 0.6)
                : $requestedTemperature);

        $conciseInstruction = trim((string) ($tokenOptimization['concise_system_instruction'] ?? ''));
        if ($conciseInstruction === '') {
            return $providerPayload;
        }

        $messages = is_array($providerPayload['messages'] ?? null) ? $providerPayload['messages'] : [];
        if ($messages === []) {
            return $providerPayload;
        }

        $styleInstruction = $responseStyle === 'concise'
            ? 'Style: concise. Keep the answer short while covering all required points.'
            : ($responseStyle === 'detailed'
                ? 'Style: detailed. Provide complete and structured explanation with practical details.'
                : 'Style: balanced. Keep it compact but complete for the user request.');
        $instruction = trim($conciseInstruction.' '.$styleInstruction);

        $firstMessage = $messages[0] ?? null;
        if (is_array($firstMessage) && ($firstMessage['role'] ?? null) === 'system') {
            $messages[0]['content'] = trim(((string) ($firstMessage['content'] ?? '')).' '.$instruction);
        } else {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $instruction,
            ]);
        }

        $providerPayload['messages'] = $messages;

        return $providerPayload;
    }

    private function wantsDetailedResponse(string $text): bool
    {
        $normalized = strtolower(trim($text));
        if ($normalized === '') {
            return false;
        }

        $detailedCues = [
            'in detail',
            'detailed',
            'step by step',
            'comprehensive',
            'explain deeply',
            'long answer',
            'full explanation',
        ];

        foreach ($detailedCues as $cue) {
            if (str_contains($normalized, $cue)) {
                return true;
            }
        }

        return false;
    }

    private function requiresComplexResponse(string $text): bool
    {
        $normalized = strtolower(trim($text));
        if ($normalized === '') {
            return false;
        }

        $complexCues = [
            'report',
            'seo report',
            'audit',
            'analysis',
            'analyze',
            'compare',
            'roadmap',
            'strategy',
            'checklist',
            'plan',
        ];

        foreach ($complexCues as $cue) {
            if (str_contains($normalized, $cue)) {
                return true;
            }
        }

        return false;
    }
}
