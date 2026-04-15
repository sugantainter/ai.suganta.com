<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderCredentialRequest;
use App\Services\UnifiedChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'profile_form' => $this->buildProfileFormData($mergedUser, $profileData),
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

    public function deleteProviderKey(Request $request, string $provider): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $availableProviders = array_keys((array) config('ai.providers', []));

        if (! in_array($provider, $availableProviders, true)) {
            return response()->json([
                'message' => 'Unsupported provider.',
            ], 422);
        }

        $removed = $this->chatService->removeProviderCredential($tenantId, $provider);

        return response()->json([
            'message' => $removed
                ? "Removed key for {$provider}."
                : "No stored key found for {$provider}.",
            'provider' => $provider,
            'removed' => $removed,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $passwordEndpoint = $this->resolveSugantaEndpoint(
            'profile_password_endpoint',
            '/profile/password',
            'https://api.suganta.com/api/v1/profile/password'
        );

        try {
            $http = Http::acceptJson()
                ->timeout(8)
                ->withHeaders($this->forwardAuthHeaders($request))
                ->asJson();

            $payload = [
                'current_password' => (string) $validated['current_password'],
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $validated['password_confirmation'],
            ];

            $response = $http->put($passwordEndpoint, $payload);
            if (in_array($response->status(), [404, 405], true)) {
                $response = $http->patch($passwordEndpoint, $payload);
            }

            $body = (array) $response->json();
            if (! $response->ok()) {
                Log::warning('Password update upstream request failed.', [
                    'tenant_id' => (int) $request->attributes->get('tenant_id'),
                    'status' => $response->status(),
                    'endpoint' => $passwordEndpoint,
                    'upstream_message' => data_get($body, 'message'),
                ]);
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
            Log::error('Password update proxy failed.', [
                'tenant_id' => (int) $request->attributes->get('tenant_id'),
                'endpoint' => $passwordEndpoint,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return response()->json([
                'message' => 'Unable to update password right now.',
            ], 502);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'phone_primary' => ['nullable', 'string', 'max:40'],
        ]);

        $profileEndpoint = $this->resolveSugantaEndpoint(
            'profile_endpoint',
            '/profile',
            'https://api.suganta.com/api/v1/profile'
        );

        $payload = array_filter([
            'name' => $validated['name'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'phone_primary' => $validated['phone_primary'] ?? null,
        ], fn ($value): bool => $value !== null);

        if ($payload === []) {
            return response()->json([
                'message' => 'No profile fields provided.',
            ], 422);
        }

        try {
            $http = Http::acceptJson()
                ->timeout(8)
                ->withHeaders($this->forwardAuthHeaders($request))
                ->asJson();

            $response = $http->put($profileEndpoint, $payload);
            if (in_array($response->status(), [404, 405], true)) {
                $response = $http->patch($profileEndpoint, $payload);
            }

            $body = (array) $response->json();
            if (! $response->ok()) {
                Log::warning('Profile update upstream request failed.', [
                    'tenant_id' => (int) $request->attributes->get('tenant_id'),
                    'status' => $response->status(),
                    'endpoint' => $profileEndpoint,
                    'payload_keys' => array_keys($payload),
                    'upstream_message' => data_get($body, 'message'),
                ]);
                return response()->json([
                    'message' => (string) data_get($body, 'message', 'Profile update failed.'),
                    'errors' => data_get($body, 'errors', []),
                ], $response->status());
            }

            return response()->json([
                'message' => (string) data_get($body, 'message', 'Profile updated successfully.'),
                'data' => data_get($body, 'data', []),
                'success' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Profile update proxy failed.', [
                'tenant_id' => (int) $request->attributes->get('tenant_id'),
                'endpoint' => $profileEndpoint,
                'payload_keys' => array_keys($payload),
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return response()->json([
                'message' => 'Unable to update profile right now.',
            ], 502);
        }
    }

    private function resolveAuthUser(Request $request): array
    {
        $authUser = (array) $request->attributes->get('auth_user', []);
        if ($authUser !== []) {
            return $authUser;
        }

        $authApiUrl = $this->resolveSugantaEndpoint(
            'user_endpoint',
            '/auth/user',
            'https://api.suganta.com/api/v1/auth/user'
        );
        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->withHeaders($this->forwardAuthHeaders($request))
                ->get($authApiUrl);

            if (! $response->ok()) {
                Log::warning('Auth user lookup failed in settings controller.', [
                    'endpoint' => $authApiUrl,
                    'status' => $response->status(),
                ]);
                return [];
            }

            $payload = (array) $response->json();
            $data = (array) data_get($payload, 'data', []);

            if (! (bool) data_get($data, 'authenticated', false)) {
                return [];
            }

            return (array) data_get($data, 'user', []);
        } catch (\Throwable $exception) {
            Log::error('Auth user lookup threw exception in settings controller.', [
                'endpoint' => $authApiUrl,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return [];
        }
    }

    private function fetchProfileData(Request $request): array
    {
        $profileEndpoint = $this->resolveSugantaEndpoint(
            'profile_endpoint',
            '/profile',
            'https://api.suganta.com/api/v1/profile'
        );
        $tenantId = (int) $request->attributes->get('tenant_id');
        $cooldownCacheKey = "settings:profile-fetch-cooldown:{$tenantId}";

        // Skip repeated failing upstream calls for a short period.
        if (Cache::has($cooldownCacheKey)) {
            return $this->fallbackProfileData($request);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->withHeaders($this->forwardAuthHeaders($request))
                ->get($profileEndpoint);

            if (! $response->ok()) {
                // Put brief cooldown on upstream 5xx responses to avoid log spam/noisy retries.
                if ($response->serverError()) {
                    Cache::put($cooldownCacheKey, true, now()->addMinutes(2));
                }

                $this->logProfileFetchFailure(
                    $tenantId,
                    $profileEndpoint,
                    $response->status(),
                    (string) data_get((array) $response->json(), 'message', '')
                );

                return $this->fallbackProfileData($request);
            }

            $payload = (array) $response->json();

            $data = (array) data_get($payload, 'data', []);
            if ($data === []) {
                return $this->fallbackProfileData($request);
            }

            return $data;
        } catch (\Throwable $exception) {
            Cache::put($cooldownCacheKey, true, now()->addMinutes(2));
            $this->logProfileFetchFailure(
                $tenantId,
                $profileEndpoint,
                null,
                $exception->getMessage(),
                $exception::class
            );

            return $this->fallbackProfileData($request);
        }
    }

    private function fallbackProfileData(Request $request): array
    {
        $authUser = $this->resolveAuthUser($request);
        $firstName = trim((string) data_get($authUser, 'first_name', ''));
        $lastName = trim((string) data_get($authUser, 'last_name', ''));
        $fullName = trim((string) data_get($authUser, 'name', trim($firstName.' '.$lastName)));

        return [
            'user' => [
                'id' => data_get($authUser, 'id', data_get($authUser, 'user_id')),
                'name' => $fullName !== '' ? $fullName : null,
                'email' => data_get($authUser, 'email'),
                'role' => data_get($authUser, 'role'),
            ],
            'profile' => [
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'phone_primary' => data_get($authUser, 'phone'),
            ],
            'profile_image_url' => data_get($authUser, 'avatar', data_get($authUser, 'profile_image')),
            'completion_percentage' => 0,
            'source' => 'fallback',
        ];
    }

    private function logProfileFetchFailure(
        int $tenantId,
        string $profileEndpoint,
        ?int $status = null,
        string $message = '',
        ?string $exceptionClass = null
    ): void {
        $statusPart = $status !== null ? "status:{$status}" : 'status:none';
        $throttleKey = "settings:profile-fetch-log:{$tenantId}:{$statusPart}";

        // Log same upstream failure at most once every 2 minutes per tenant/status.
        if (! Cache::add($throttleKey, true, now()->addMinutes(2))) {
            return;
        }

        Log::warning('Profile fetch failed in settings controller.', [
            'tenant_id' => $tenantId,
            'endpoint' => $profileEndpoint,
            'status' => $status,
            'upstream_message' => $message !== '' ? $message : null,
            'exception' => $exceptionClass,
        ]);
    }

    private function normalizeAuthUser(array $authUser, array $profileData = []): array
    {
        $profile = (array) data_get($profileData, 'profile', []);
        $profileUser = (array) data_get($profileData, 'user', []);

        $firstName = trim((string) data_get($authUser, 'first_name', data_get($profile, 'first_name', '')));
        $lastName = trim((string) data_get($authUser, 'last_name', data_get($profile, 'last_name', '')));
        $fullName = trim((string) data_get($authUser, 'full_name', ''));
        $name = trim((string) data_get($authUser, 'name', data_get($profileUser, 'name', '')));
        $derivedName = trim($fullName !== '' ? $fullName : $firstName.' '.$lastName);

        $phone = data_get(
            $authUser,
            'phone',
            data_get(
                $profile,
                'phone_primary',
                data_get($profile, 'principal_phone', data_get($profile, 'parent_phone', data_get($profile, 'phone_secondary')))
            )
        );

        return [
            'id' => data_get($authUser, 'id', data_get($authUser, 'user_id', data_get($profileUser, 'id'))),
            'name' => $name !== '' ? $name : ($derivedName !== '' ? $derivedName : null),
            'email' => data_get($authUser, 'email', data_get($profileUser, 'email')),
            'phone' => $phone,
            'avatar' => data_get(
                $profileData,
                'profile_image_url',
                data_get($authUser, 'avatar', data_get($authUser, 'profile_image', data_get($profile, 'profile_image_url')))
            ),
            'username' => data_get($authUser, 'username', data_get($profile, 'username')),
            'role' => data_get($authUser, 'role', data_get($profileUser, 'role')),
            'completion_percentage' => (int) data_get($profileData, 'completion_percentage', 0),
        ];
    }

    private function buildProfileFormData(array $authUser, array $profileData): array
    {
        $profile = (array) data_get($profileData, 'profile', []);
        $profileUser = (array) data_get($profileData, 'user', []);

        return [
            'name' => (string) data_get($authUser, 'name', data_get($profileUser, 'name', '')),
            'first_name' => (string) data_get($authUser, 'first_name', data_get($profile, 'first_name', '')),
            'last_name' => (string) data_get($authUser, 'last_name', data_get($profile, 'last_name', '')),
            'phone' => (string) data_get($authUser, 'phone', data_get($profile, 'phone_primary', '')),
        ];
    }

    /**
     * @return array<string,string>
     */
    private function forwardAuthHeaders(Request $request): array
    {
        return array_filter([
            'Cookie' => (string) $request->header('cookie', ''),
            'Authorization' => (string) $request->header('authorization', ''),
            'User-Agent' => (string) $request->userAgent(),
            'Origin' => (string) $request->header('origin', ''),
            'Referer' => (string) $request->header('referer', ''),
            'X-Requested-With' => 'XMLHttpRequest',
        ], fn ($value): bool => is_string($value) && trim($value) !== '');
    }

    private function resolveSugantaEndpoint(string $configKey, string $path, string $fallback): string
    {
        $configured = trim((string) config("services.suganta_auth.{$configKey}", ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $baseUrl = rtrim((string) config('services.suganta_auth.base_url', 'https://api.suganta.com/api/v1'), '/');
        if ($baseUrl !== '') {
            return $baseUrl.'/'.ltrim($path, '/');
        }

        return rtrim($fallback, '/');
    }
}
