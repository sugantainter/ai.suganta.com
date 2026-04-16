<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'suganta_auth' => [
        'base_url' => env('SUGANTA_AUTH_BASE_URL', 'https://api.suganta.com/api/v1'),
        'user_endpoint' => env('SUGANTA_AUTH_USER_ENDPOINT', 'https://api.suganta.com/api/v1/auth/user'),
        'profile_endpoint' => env('SUGANTA_AUTH_PROFILE_ENDPOINT', 'https://api.suganta.com/api/v1/profile'),
        'profile_password_endpoint' => env('SUGANTA_AUTH_PROFILE_PASSWORD_ENDPOINT', 'https://api.suganta.com/api/v1/profile/password'),
        'redirect_url' => env('SUGANTA_AUTH_REDIRECT_URL', 'https://app.suganta.com'),
        'login_required_message' => env('SUGANTA_AUTH_LOGIN_REQUIRED_MESSAGE', 'Login to access this page.'),
        'cache_ttl_seconds' => (int) env('SUGANTA_AUTH_CACHE_TTL_SECONDS', 60),
        'refresh_before_seconds' => (int) env('SUGANTA_AUTH_REFRESH_BEFORE_SECONDS', 15),
    ],

];
