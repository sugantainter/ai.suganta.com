<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authApiUrl = rtrim((string) config('services.suganta_auth.user_endpoint', 'https://api.suganta.com/api/v1/auth/user'), '/');
        $redirectBase = rtrim((string) config('services.suganta_auth.redirect_url', 'https://app.suganta.com'), '/');
        $redirectMessage = 'Login To Access SuGanta Ai';
        $sessionKey = 'suganta_auth.user';
        $cacheTtlSeconds = (int) config('services.suganta_auth.cache_ttl_seconds', 60);
        $refreshBeforeSeconds = (int) config('services.suganta_auth.refresh_before_seconds', 15);
        $cookieHeader = (string) $request->header('cookie', '');
        $authorizationHeader = (string) $request->header('authorization', '');
        $userAgent = (string) $request->userAgent();
        $cacheKey = $this->authCacheKey($cookieHeader, $authorizationHeader);

        // 1) Reuse auth context from session/cache to reduce external API calls.
        $cachedAuth = [];
        if ($request->hasSession()) {
            $cachedAuth = (array) $request->session()->get($sessionKey, []);
        }
        if (empty($cachedAuth)) {
            $cachedAuth = (array) Cache::get($cacheKey, []);
        }

        if ($this->isAuthenticatedAndFresh($cachedAuth, $cacheTtlSeconds)) {
            $this->applyRequestContext($request, $cachedAuth);
            if ($request->hasSession()) {
                $request->session()->put($sessionKey, $cachedAuth);
            }

            // 2) Refresh shortly before expiry after response is sent.
            if ($this->shouldRefreshSoon($cachedAuth, $cacheTtlSeconds, $refreshBeforeSeconds)) {
                $this->scheduleBackgroundRefresh(
                    $authApiUrl,
                    $cacheKey,
                    $cookieHeader,
                    $authorizationHeader,
                    $userAgent,
                    $cacheTtlSeconds
                );
            }

            return $next($request);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->withHeaders(array_filter([
                    // Forward browser cookies so API can validate session auth.
                    'Cookie' => $cookieHeader,
                    'Authorization' => $authorizationHeader,
                    'User-Agent' => $userAgent,
                ]))
                ->get($authApiUrl);

            $payload = $response->json();
            $data = is_array($payload) ? (array) data_get($payload, 'data', []) : [];
            $authenticated = (bool) data_get($data, 'authenticated', false);
            $userData = (array) data_get($data, 'user', []);

            if (! $response->ok() || ! $authenticated) {
                return redirect()->away($redirectBase.'?message='.urlencode($redirectMessage));
            }

            $authContext = $this->buildAuthContext($userData);

            // Make user/auth data available to all downstream handlers in this request.
            $this->applyRequestContext($request, $authContext);

            // Persist latest authenticated user for future requests.
            if ($request->hasSession()) {
                $request->session()->put($sessionKey, $authContext);
            }
            Cache::put($cacheKey, $authContext, now()->addSeconds(max($cacheTtlSeconds * 3, 180)));
        } catch (\Throwable $exception) {
            // As a fallback, rehydrate request data from the latest successful session auth.
            if ((bool) data_get($cachedAuth, 'authenticated', false)) {
                $this->applyRequestContext($request, $cachedAuth);

                return $next($request);
            }

            return redirect()->away($redirectBase.'?message='.urlencode($redirectMessage));
        }

        return $next($request);
    }

    private function authCacheKey(string $cookieHeader, string $authorizationHeader): string
    {
        $fingerprint = hash('sha256', $cookieHeader.'|'.$authorizationHeader);

        return 'suganta_auth:user:'.$fingerprint;
    }

    private function buildAuthContext(array $userData): array
    {
        return [
            'authenticated' => true,
            'user' => $userData,
            'fetched_at' => now()->toIso8601String(),
            'request_id' => (string) Str::uuid(),
        ];
    }

    private function applyRequestContext(Request $request, array $authContext): void
    {
        $request->attributes->set('auth_user', (array) data_get($authContext, 'user', []));
        $request->attributes->set('auth_context', $authContext);
    }

    private function isAuthenticatedAndFresh(array $authContext, int $cacheTtlSeconds): bool
    {
        $fetchedAt = (string) data_get($authContext, 'fetched_at', '');
        $fetchedAtTimestamp = strtotime($fetchedAt);
        if ($fetchedAtTimestamp === false) {
            return false;
        }

        return (bool) data_get($authContext, 'authenticated', false)
            && (time() - $fetchedAtTimestamp) < $cacheTtlSeconds;
    }

    private function shouldRefreshSoon(array $authContext, int $cacheTtlSeconds, int $refreshBeforeSeconds): bool
    {
        $fetchedAt = (string) data_get($authContext, 'fetched_at', '');
        $fetchedAtTimestamp = strtotime($fetchedAt);
        if ($fetchedAtTimestamp === false) {
            return false;
        }

        $secondsLeft = $cacheTtlSeconds - (time() - $fetchedAtTimestamp);

        return $secondsLeft > 0 && $secondsLeft <= max($refreshBeforeSeconds, 1);
    }

    private function scheduleBackgroundRefresh(
        string $authApiUrl,
        string $cacheKey,
        string $cookieHeader,
        string $authorizationHeader,
        string $userAgent,
        int $cacheTtlSeconds
    ): void {
        app()->terminating(function () use (
            $authApiUrl,
            $cacheKey,
            $cookieHeader,
            $authorizationHeader,
            $userAgent,
            $cacheTtlSeconds
        ): void {
            $refreshLockKey = $cacheKey.':refresh-lock';
            if (! Cache::add($refreshLockKey, 1, now()->addSeconds(20))) {
                return;
            }

            try {
                $response = Http::acceptJson()
                    ->timeout(3)
                    ->withHeaders(array_filter([
                        'Cookie' => $cookieHeader,
                        'Authorization' => $authorizationHeader,
                        'User-Agent' => $userAgent,
                    ]))
                    ->get($authApiUrl);

                $payload = $response->json();
                $data = is_array($payload) ? (array) data_get($payload, 'data', []) : [];
                $authenticated = (bool) data_get($data, 'authenticated', false);
                $userData = (array) data_get($data, 'user', []);

                if ($response->ok() && $authenticated) {
                    Cache::put(
                        $cacheKey,
                        $this->buildAuthContext($userData),
                        now()->addSeconds(max($cacheTtlSeconds * 3, 180))
                    );
                }
            } catch (\Throwable $exception) {
                // Ignore background refresh failures. Main request already succeeded.
            } finally {
                Cache::forget($refreshLockKey);
            }
        });
    }
}
