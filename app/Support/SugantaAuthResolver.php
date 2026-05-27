<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SugantaAuthResolver
{
    private const SESSION_KEY = 'suganta_auth.user';

    /**
     * Resolve auth for public pages (navbar). Never redirects.
     *
     * @return array{
     *     authenticated: bool,
     *     name: string,
     *     email: ?string,
     *     initials: string,
     *     avatar_url: ?string,
     *     dashboard_url: string,
     *     dashboard_label: string
     * }
     */
    public function resolveForPublicNav(Request $request): array
    {
        $dashboard = $this->dashboardLink($request);
        $guest = [
            'authenticated' => false,
            'name' => '',
            'email' => null,
            'initials' => '',
            'avatar_url' => null,
            'dashboard_url' => $dashboard['url'],
            'dashboard_label' => $dashboard['label'],
        ];

        $existingUser = (array) $request->attributes->get('auth_user', []);
        if ($existingUser !== []) {
            return $this->buildNavUser($existingUser, $dashboard);
        }

        $cookieHeader = (string) $request->header('cookie', '');
        $authorizationHeader = (string) $request->header('authorization', '');
        if ($cookieHeader === '' && $authorizationHeader === '') {
            return $guest;
        }

        $cacheTtlSeconds = (int) config('services.suganta_auth.cache_ttl_seconds', 60);
        $cacheKey = $this->authCacheKey($cookieHeader, $authorizationHeader);

        $cachedAuth = [];
        if ($request->hasSession()) {
            $cachedAuth = (array) $request->session()->get(self::SESSION_KEY, []);
        }
        if ($cachedAuth === []) {
            $cachedAuth = (array) Cache::get($cacheKey, []);
        }

        if ($this->isAuthenticatedAndFresh($cachedAuth, $cacheTtlSeconds)) {
            $user = (array) data_get($cachedAuth, 'user', []);

            return $this->buildNavUser($user, $dashboard);
        }

        try {
            $authApiUrl = rtrim((string) config('services.suganta_auth.user_endpoint'), '/');
            $response = Http::acceptJson()
                ->timeout(3)
                ->withHeaders(array_filter([
                    'Cookie' => $cookieHeader,
                    'Authorization' => $authorizationHeader,
                    'User-Agent' => (string) $request->userAgent(),
                ]))
                ->get($authApiUrl);

            $payload = $response->json();
            $data = is_array($payload) ? (array) data_get($payload, 'data', []) : [];
            $authenticated = (bool) data_get($data, 'authenticated', false);
            $userData = (array) data_get($data, 'user', []);

            if (! $response->ok() || ! $authenticated || $userData === []) {
                return $guest;
            }

            $authContext = $this->buildAuthContext($userData);
            if ($request->hasSession()) {
                $request->session()->put(self::SESSION_KEY, $authContext);
            }
            Cache::put($cacheKey, $authContext, now()->addSeconds(max($cacheTtlSeconds * 3, 180)));

            return $this->buildNavUser($userData, $dashboard);
        } catch (\Throwable) {
            if ((bool) data_get($cachedAuth, 'authenticated', false)) {
                return $this->buildNavUser((array) data_get($cachedAuth, 'user', []), $dashboard);
            }

            return $guest;
        }
    }

    /**
     * @return array{url: string, label: string}
     */
    private function dashboardLink(Request $request): array
    {
        $config = (array) config('public_nav.authenticated.dashboard', []);
        $label = (string) ($config['label'] ?? 'Dashboard');
        $defaultHref = (string) ($config['href'] ?? 'https://www.suganta.com/dashboard');
        $aiConfig = (array) config('public_nav.authenticated.ai_dashboard', []);
        $aiHref = (string) ($aiConfig['href'] ?? '/');

        $host = strtolower($request->getHost());
        $isAiApp = str_contains($host, 'ai.suganta.com')
            || $host === 'localhost'
            || $host === '127.0.0.1';

        if ($isAiApp) {
            return [
                'url' => str_starts_with($aiHref, 'http') ? $aiHref : url($aiHref),
                'label' => (string) ($aiConfig['label'] ?? 'Open Kaalo AI'),
            ];
        }

        return [
            'url' => $defaultHref,
            'label' => $label,
        ];
    }

    /**
     * @param  array<string, mixed>  $user
     * @param  array{url: string, label: string}  $dashboard
     * @return array{
     *     authenticated: bool,
     *     name: string,
     *     email: ?string,
     *     initials: string,
     *     avatar_url: ?string,
     *     dashboard_url: string,
     *     dashboard_label: string
     * }
     */
    private function buildNavUser(array $user, array $dashboard): array
    {
        $firstName = trim((string) data_get($user, 'first_name', ''));
        $lastName = trim((string) data_get($user, 'last_name', ''));
        $fullName = trim((string) data_get($user, 'full_name', ''));
        $name = trim((string) data_get($user, 'name', ''));
        if ($name === '') {
            $name = $fullName !== '' ? $fullName : trim($firstName.' '.$lastName);
        }
        if ($name === '') {
            $name = trim((string) data_get($user, 'email', 'Account'));
        }

        $email = data_get($user, 'email');
        $email = is_string($email) && $email !== '' ? $email : null;

        $avatar = data_get($user, 'avatar', data_get($user, 'profile_image', data_get($user, 'profile_image_url')));
        $avatarUrl = is_string($avatar) && $avatar !== '' ? $avatar : null;

        return [
            'authenticated' => true,
            'name' => $name,
            'email' => $email,
            'initials' => $this->initials($name, $email),
            'avatar_url' => $avatarUrl,
            'dashboard_url' => $dashboard['url'],
            'dashboard_label' => $dashboard['label'],
        ];
    }

    private function initials(string $name, ?string $email): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= strtoupper(substr($part, 0, 1));
        }
        if ($letters !== '') {
            return $letters;
        }
        if ($email) {
            return strtoupper(substr($email, 0, 1));
        }

        return 'U';
    }

    private function authCacheKey(string $cookieHeader, string $authorizationHeader): string
    {
        return 'suganta_auth:user:'.hash('sha256', $cookieHeader.'|'.$authorizationHeader);
    }

    /**
     * @param  array<string, mixed>  $userData
     * @return array{authenticated: true, user: array<string, mixed>, fetched_at: string, request_id: string}
     */
    private function buildAuthContext(array $userData): array
    {
        return [
            'authenticated' => true,
            'user' => $userData,
            'fetched_at' => now()->toIso8601String(),
            'request_id' => (string) Str::uuid(),
        ];
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
}
