<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderCredentialRequest;
use App\Services\UnifiedChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function __construct(private readonly UnifiedChatService $chatService) {}

    public function overview(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $authUser = $this->resolveAuthUser($request);
        $profileData = $this->fetchProfileData($request);
        $profileUser = (array) data_get($profileData, 'user', []);
        $mergedUser = array_merge($authUser, $profileUser);

        return response()->json([
            'tenant_id' => $tenantId,
            'auth_user_display' => $this->normalizeAuthUser($mergedUser, $profileData),
            'auth_user' => $mergedUser,
            'profile' => $profileData,
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

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $passwordEndpoint = rtrim((string) config('services.suganta_auth.profile_password_endpoint', 'https://api.suganta.com/api/v1/profile/password'), '/');

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->withHeaders(array_filter([
                    'Cookie' => (string) $request->header('cookie', ''),
                    'Authorization' => (string) $request->header('authorization', ''),
                    'User-Agent' => (string) $request->userAgent(),
                ]))
                ->asJson()
                ->put($passwordEndpoint, [
                    'current_password' => (string) $validated['current_password'],
                    'password' => (string) $validated['password'],
                    'password_confirmation' => (string) $validated['password_confirmation'],
                ]);

            $body = (array) $response->json();
            if (! $response->ok()) {
                return response()->json([
                    'message' => (string) data_get($body, 'message', 'Password update failed.'),
                    'errors' => data_get($body, 'errors', []),
                ], $response->status());
            }

            return response()->json([
                'message' => (string) data_get($body, 'message', 'Password updated successfully.'),
                'success' => true,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Unable to update password right now.',
            ], 502);
        }
    }

    private function resolveAuthUser(Request $request): array
    {
        $authUser = (array) $request->attributes->get('auth_user', []);
        if ($authUser !== []) {
            return $authUser;
        }

        $authApiUrl = rtrim((string) config('services.suganta_auth.user_endpoint', 'https://api.suganta.com/api/v1/auth/user'), '/');
        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->withHeaders(array_filter([
                    'Cookie' => (string) $request->header('cookie', ''),
                    'Authorization' => (string) $request->header('authorization', ''),
                    'User-Agent' => (string) $request->userAgent(),
                ]))
                ->get($authApiUrl);

            if (! $response->ok()) {
                return [];
            }

            $payload = (array) $response->json();
            $data = (array) data_get($payload, 'data', []);

            if (! (bool) data_get($data, 'authenticated', false)) {
                return [];
            }

            return (array) data_get($data, 'user', []);
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function fetchProfileData(Request $request): array
    {
        $profileEndpoint = rtrim((string) config('services.suganta_auth.profile_endpoint', 'https://api.suganta.com/api/v1/profile'), '/');

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->withHeaders(array_filter([
                    'Cookie' => (string) $request->header('cookie', ''),
                    'Authorization' => (string) $request->header('authorization', ''),
                    'User-Agent' => (string) $request->userAgent(),
                ]))
                ->get($profileEndpoint);

            if (! $response->ok()) {
                return [];
            }

            $payload = (array) $response->json();

            return (array) data_get($payload, 'data', []);
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function normalizeAuthUser(array $authUser, array $profileData = []): array
    {
        $firstName = (string) data_get($authUser, 'first_name', '');
        $lastName = (string) data_get($authUser, 'last_name', '');
        $fullName = trim((string) data_get($authUser, 'full_name', ''));
        $name = trim((string) data_get($authUser, 'name', ''));
        $derivedName = trim($fullName !== '' ? $fullName : $firstName.' '.$lastName);

        return [
            'id' => data_get($authUser, 'id', data_get($authUser, 'user_id')),
            'name' => $name !== '' ? $name : ($derivedName !== '' ? $derivedName : null),
            'email' => data_get($authUser, 'email'),
            'phone' => data_get($authUser, 'phone'),
            'avatar' => data_get($profileData, 'profile_image_url', data_get($authUser, 'avatar', data_get($authUser, 'profile_image'))),
            'username' => data_get($authUser, 'username'),
            'role' => data_get($authUser, 'role'),
            'completion_percentage' => (int) data_get($profileData, 'completion_percentage', 0),
        ];
    }
}
