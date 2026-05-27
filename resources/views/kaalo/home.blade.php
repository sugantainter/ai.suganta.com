@extends('layouts.seo')

@section('content')
@php
    $hero = $page['hero'];
    $pricing = $page['pricing'];
    $registerHref = config('public_nav.header.cta.register.href');
@endphp

<section class="kaalo-hero">
    <div class="container kaalo-hero__grid">
        <div class="kaalo-hero__copy">
            <span class="category-badge">{{ $hero['badge'] }}</span>
            <h1 class="kaalo-hero__title">{{ $hero['title'] }}</h1>
            <p class="kaalo-hero__subtitle">{{ $hero['subtitle'] }}</p>

            <div class="kaalo-model-pills" aria-label="Supported AI models">
                @foreach ($hero['models'] as $model)
                    <span class="kaalo-model-pill">{{ $model }}</span>
                @endforeach
            </div>

            <div class="kaalo-hero__cta">
                <a href="{{ $registerHref }}" class="public-btn public-btn--primary">{{ $hero['cta_primary']['label'] }}</a>
                <a href="{{ $hero['cta_secondary']['href'] }}" class="public-btn public-btn--ghost" target="_blank" rel="noopener noreferrer">{{ $hero['cta_secondary']['label'] }}</a>
            </div>
        </div>

        <div class="kaalo-hero__panel sidebar-card">
            <h2 class="kaalo-panel__title">At a glance</h2>
            <ul class="kaalo-highlight-list">
                @foreach ($page['highlights'] as $item)
                    <li>
                        <span class="kaalo-highlight-list__label">{{ $item['label'] }}</span>
                        <span class="kaalo-highlight-list__value">{{ $item['value'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="kaalo-trust-row">
                @foreach ($page['trust_stats'] as $stat)
                    <div class="kaalo-trust-stat">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="kaalo-section kaalo-section--pricing" id="pricing">
    <div class="container">
        <div class="kaalo-section__head">
            <span class="category-badge">{{ $pricing['eyebrow'] }}</span>
            <h2 class="kaalo-section__title">{{ $pricing['title'] }}</h2>
            <p class="kaalo-section__subtitle">{{ $pricing['subtitle'] }}</p>
        </div>

        <div class="kaalo-pricing-grid">
            <div class="kaalo-pricing-compare sidebar-card">
                <h3>{{ $pricing['comparison_title'] }}</h3>
                <p class="kaalo-muted">{{ $pricing['comparison_intro'] }}</p>
                <ul class="kaalo-cost-list">
                    @foreach ($pricing['competitor_costs'] as $cost)
                        <li>
                            <span>{{ $cost['name'] }}</span>
                            <span>{{ $cost['price'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="kaalo-cost-note">{{ $pricing['comparison_note'] }}</p>
            </div>

            <div class="kaalo-pricing-plans">
                <p class="kaalo-pricing-brand">Kaalo AI <span>by SuGanta</span></p>
                <div class="kaalo-plan-cards">
                    @foreach ($pricing['plans'] as $plan)
                        <div class="kaalo-plan-card{{ ! empty($plan['featured']) ? ' kaalo-plan-card--featured' : '' }}">
                            @if (! empty($plan['badge']))
                                <span class="kaalo-plan-card__badge">{{ $plan['badge'] }}</span>
                            @endif
                            <h4>{{ $plan['name'] }}</h4>
                            <p class="kaalo-plan-card__price">{{ $plan['price'] }}<small>{{ $plan['period'] }}</small></p>
                        </div>
                    @endforeach
                </div>
                <ul class="kaalo-plan-benefits">
                    @foreach ($pricing['benefits'] as $benefit)
                        <li>{{ $benefit }}</li>
                    @endforeach
                </ul>
                <a href="{{ $pricing['cta']['href'] }}" class="public-btn public-btn--primary public-btn--block">{{ $pricing['cta']['label'] }}</a>
            </div>
        </div>
    </div>
</section>

<section class="kaalo-section">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">{{ $page['workflow']['title'] }}</h2>
            <p class="kaalo-section__subtitle">{{ $page['workflow']['subtitle'] }}</p>
        </div>
        <div class="kaalo-steps">
            @foreach ($page['workflow']['steps'] as $step)
                <article class="kaalo-step-card feature-card">
                    <span class="kaalo-step-card__num">{{ $step['number'] }}</span>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['body'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-section kaalo-section--alt">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">{{ $page['audiences']['title'] }}</h2>
            <p class="kaalo-section__subtitle">{{ $page['audiences']['subtitle'] }}</p>
        </div>
        <div class="card-grid">
            @foreach ($page['audiences']['cards'] as $card)
                <article class="feature-card">
                    <h3 class="feature-title"><span class="bullet"></span>{{ $card['title'] }}</h3>
                    <ul class="kaalo-bullet-list">
                        @foreach ($card['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-section">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">{{ $page['features']['title'] }}</h2>
            <p class="kaalo-section__subtitle">{{ $page['features']['subtitle'] }}</p>
        </div>
        <div class="card-grid">
            @foreach ($page['features']['items'] as $feature)
                <article class="feature-card">
                    <h3 class="feature-title"><span class="bullet"></span>{{ $feature['title'] }}</h3>
                    <p class="feature-desc">{{ $feature['body'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-section kaalo-section--alt">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">{{ $page['prompts']['title'] }}</h2>
            <p class="kaalo-section__subtitle">{{ $page['prompts']['subtitle'] }}</p>
        </div>
        <div class="kaalo-prompt-grid">
            @foreach ($page['prompts']['examples'] as $example)
                <blockquote class="kaalo-prompt-card sidebar-card">
                    <cite>{{ $example['category'] }}</cite>
                    <p>{{ $example['text'] }}</p>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-section">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">{{ $page['trust']['title'] }}</h2>
        </div>
        <div class="card-grid">
            @foreach ($page['trust']['items'] as $item)
                <article class="feature-card">
                    <h3 class="feature-title"><span class="bullet"></span>{{ $item['title'] }}</h3>
                    <p class="feature-desc">{{ $item['body'] }}</p>
                </article>
            @endforeach
        </div>
        <div class="kaalo-trust-links">
            @foreach ($page['trust']['links'] as $link)
                <a href="{{ $link['href'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-section kaalo-section--faq" id="faq">
    <div class="container">
        <div class="kaalo-section__head">
            <h2 class="kaalo-section__title">Frequently asked questions</h2>
        </div>
        <div class="faq-container">
            @foreach ($page['faqs'] as $faq)
                <div class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">{{ $faq['question'] }}</button>
                    <div class="faq-content">
                        <p>{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="kaalo-cta-band">
    <div class="container kaalo-cta-band__inner">
        <div>
            <h2>Ready to study smarter with Kaalo AI?</h2>
            <p>One workspace. Multiple models. Built for education on SuGanta.</p>
        </div>
        <div class="kaalo-hero__cta">
            <a href="{{ $registerHref }}" class="public-btn public-btn--primary">Get Started with Kaalo AI</a>
            <a href="{{ $hero['cta_secondary']['href'] }}" class="public-btn public-btn--ghost" target="_blank" rel="noopener noreferrer">Explore SuGanta</a>
        </div>
    </div>
</section>
@endsection
