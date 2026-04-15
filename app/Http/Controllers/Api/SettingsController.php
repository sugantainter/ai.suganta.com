<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderCredentialRequest;
use App\Services\UnifiedChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly UnifiedChatService $chatService) {}

    public function overview(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $authUser = (array) $request->attributes->get('auth_user', []);

        return response()->json([
            'tenant_id' => $tenantId,
            'auth_user' => $authUser,
            'usage' => $this->chatService->getUsageSummary($tenantId),
            'provider_keys' => $this->chatService->listProviderCredentials($tenantId),
            'active_models_count' => count($this->chatService->listModels()),
        ]);
    }

    public function providerKeys(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        return response()->json([
            'provider_keys' => $this->chatService->listProviderCredentials($tenantId),
        ]);
    }

    public function storeProviderKey(ProviderCredentialRequest $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $payload = $request->validated();

        return response()->json([
            'provider_key' => $this->chatService->storeProviderCredential(
                $tenantId,
                (string) $payload['provider'],
                (string) $payload['api_key'],
                (bool) ($payload['is_active'] ?? true)
            ),
        ]);
    }
}
