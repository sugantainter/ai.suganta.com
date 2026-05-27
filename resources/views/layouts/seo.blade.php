<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{!! $seo['title'] !!}</title>
    <meta name="description" content="{!! $seo['description'] !!}">
    <meta name="keywords" content="{!! $seo['keywords'] !!}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    <!-- OpenGraph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{!! $seo['title'] !!}">
    <meta property="og:description" content="{!! $seo['description'] !!}">
    <meta property="og:image" content="{{ asset('logo/favicon.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $seo['canonical'] }}">
    <meta name="twitter:title" content="{!! $seo['title'] !!}">
    <meta name="twitter:description" content="{!! $seo['description'] !!}">
    <meta name="twitter:image" content="{{ asset('logo/favicon.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('logo/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/seo.css', 'resources/css/seo-page.css', 'resources/js/seo.js'])

    <!-- Schema Markup Injection -->
    @if(!empty($schema))
        @foreach($schema as $type => $json)
            <script type="application/ld+json">
                {!! is_array($json) ? json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $json !!}
            </script>
        @endforeach
    @endif
</head>
<body>
    <div class="ambient-glow-1 glow-pulse"></div>
    <div class="ambient-glow-2"></div>

    @include('partials.public.header')

    <!-- Main Container -->
    <main class="container">
        <!-- Breadcrumbs Navigation -->
        @if(!empty($breadcrumbs))
            <ul class="breadcrumbs" aria-label="breadcrumb">
                <li><a href="{{ config('public_nav.header.logo.href') }}">Home</a></li>
                @foreach($breadcrumbs as $label => $url)
                    @if($loop->last)
                        <li aria-current="page">{{ $label }}</li>
                    @else
                        <li><a href="{{ $url }}">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ul>
        @endif

        <!-- Dynamic Content Injected Here -->
        @yield('content')
    </main>

    @include('partials.public.footer')
</body>
</html>
