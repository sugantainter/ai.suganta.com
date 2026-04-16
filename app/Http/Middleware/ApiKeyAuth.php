<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\SugantaLoginGateway;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = (string) $request->header('x-api-key', '');
        if ($rawKey !== '') {
            $keyHash = hash('sha256', $rawKey);
            $apiKey = ApiKey::query()
                ->where('key_hash', $keyHash)
                ->where('is_active', true)
                ->first();

            if (! $apiKey) {
                return SugantaLoginGateway::unauthorizedApiResponse($request, 'Invalid API key.');
            }

            $apiKey->forceFill(['last_used_at' => now()])->save();
            $request->attributes->set('tenant_id', (int) $apiKey->tenant_id);
            $request->attributes->set('api_key_id', (int) $apiKey->id);
            $request->attributes->set('api_key_rate_limit', (int) $apiKey->rate_limit_per_minute);

            return $next($request);
        }

        $authUser = (array) $request->attributes->get('auth_user', []);
        if ($authUser === []) {
            $authUser = $this->fetchAuthUser($request);
            if ($authUser !== []) {
                $request->attributes->set('auth_user', $authUser);
            }
        }

        $userId = $this->extractUserId($authUser);
        if ($userId === null) {
            return SugantaLoginGateway::unauthorizedApiResponse(
                $request,
                'Missing authentication. Provide X-API-Key or user auth session/token.'
            );
        }

        $request->attributes->set('tenant_id', $userId);
        $request->attributes->set('api_key_id', null);
        $request->attributes->set('api_key_rate_limit', (int) env('AI_API_DEFAULT_RATE_LIMIT', 60));

        return $next($request);
    }

    private function fetchAuthUser(Request $request): array
    {
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
                Log::warning('Auth user lookup failed in ApiKeyAuth middleware.', [
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
            Log::error('Auth user lookup exception in ApiKeyAuth middleware.', [
                'endpoint' => $authApiUrl,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            return [];
        }
    }

    private function extractUserId(array $authUser): ?int
    {
        $candidates = [
            data_get($authUser, 'id'),
            data_get($authUser, 'user_id'),
            data_get($authUser, 'uuid'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        return null;
    }
}
