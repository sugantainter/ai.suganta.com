@extends('layouts.seo')

@section('content')
<section class="hero-section" style="padding-bottom: 48px;">
    <div class="category-badge">404</div>
    <h1 class="hero-title">Page not found</h1>
    <p class="hero-desc">
        We could not find an article at this address. It may have moved, or the URL might be incorrect.
    </p>
    <div class="meta-row" style="border-bottom: none; padding-bottom: 0;">
        <a href="{{ config('public_nav.header.logo.href') }}" class="public-btn public-btn--primary">Go to SuGanta Home</a>
        <a href="{{ url('/chatgpt-vs-gemini') }}" class="public-btn public-btn--ghost" style="margin-left: 12px;">Browse AI comparisons</a>
    </div>
</section>
@endsection
