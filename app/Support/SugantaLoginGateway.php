<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SugantaLoginGateway
{
    public static function message(): string
    {
        return (string) config(
            'services.suganta_auth.login_required_message',
            'Login to access this page.'
        );
    }

    public static function redirectBase(): string
    {
        return rtrim((string) config('services.suganta_auth.redirect_url', 'https://app.suganta.com'), '/');
    }

    public static function loginUrl(Request $request, ?string $message = null): string
    {
        return self::buildUrl(
            self::redirectBase(),
            $message ?? self::message(),
            $request->fullUrl()
        );
    }

    public static function redirect(Request $request, ?string $message = null): RedirectResponse
    {
        $url = self::loginUrl($request, $message);

        return redirect()->away($url, Response::HTTP_FOUND, [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public static function unauthorizedApiResponse(Request $request, string $apiMessage): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return self::redirect($request);
        }

        return response()->json([
            'message' => $apiMessage,
            'redirect_url' => self::loginUrl($request),
        ], Response::HTTP_UNAUTHORIZED);
    }

    private static function buildUrl(string $redirectBase, string $message, string $returnUrl): string
    {
        $redirectBase = trim($redirectBase);
        $parts = parse_url($redirectBase) ?: [];

        $scheme = isset($parts['scheme']) ? (string) $parts['scheme'] : 'https';
        $host = isset($parts['host']) ? (string) $parts['host'] : '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        $query = [];
        if (! empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        $query['message'] = $message;
        $query['redirect'] = $returnUrl;

        $authority = $host !== '' ? $scheme.'://'.$host.$port : $redirectBase;

        $built = $host !== ''
            ? $authority.$path
            : $redirectBase;

        $separator = str_contains($built, '?') ? '&' : '?';

        return $built.$separator.http_build_query($query, '', '&', PHP_QUERY_RFC3986).$fragment;
    }
}
