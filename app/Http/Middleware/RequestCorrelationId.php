<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->headers->get('X-Request-Id', Str::uuid()->toString());
        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
