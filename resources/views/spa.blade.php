<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $appName = config('app.name', 'SuGanta AI');
            $defaultTitle = $appName.' - Unified Multi-Model AI Chat';
            $defaultDescription = 'Chat with top AI models in one place. Use a fast, secure unified interface with conversation history, file uploads, and settings control.';
            $canonicalUrl = rtrim(config('app.url', 'https://ai.suganta.com'), '/');
        @endphp

        <title>{{ $defaultTitle }}</title>
        <meta name="description" content="{{ $defaultDescription }}">
        <meta name="keywords" content="AI chat, unified AI API, OpenAI, Gemini, Anthropic, multi model chat, SaaS AI platform">
        <meta name="robots" content="index, follow">
        <meta name="author" content="SuGanta">
        <link rel="canonical" href="{{ $canonicalUrl }}/">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:title" content="{{ $defaultTitle }}">
        <meta property="og:description" content="{{ $defaultDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}/">
        <meta property="og:image" content="{{ asset('logo/favicon.png') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $defaultTitle }}">
        <meta name="twitter:description" content="{{ $defaultDescription }}">
        <meta name="twitter:image" content="{{ asset('logo/favicon.png') }}">
        <link rel="icon" type="image/png" href="{{ asset('logo/favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('logo/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo/Su250.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
