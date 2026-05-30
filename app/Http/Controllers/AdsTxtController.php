<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class AdsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $clientId = (string) config('adsense.client_id');

        if (! config('adsense.enabled') || $clientId === '') {
            return response('', Response::HTTP_NOT_FOUND);
        }

        $publisherId = str_starts_with($clientId, 'ca-pub-')
            ? substr($clientId, 3)
            : $clientId;

        $body = "google.com, {$publisherId}, DIRECT, f08c47fec0942fa0\n";

        return response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
