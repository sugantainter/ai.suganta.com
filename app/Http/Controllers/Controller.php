<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function authUser(Request $request): array
    {
        return (array) $request->attributes->get('auth_user', []);
    }

    protected function authContext(Request $request): array
    {
        return (array) $request->attributes->get('auth_context', []);
    }
}
