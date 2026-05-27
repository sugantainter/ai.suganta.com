@php
    $header = config('public_nav.header');
    $logo = $header['logo'];
    $navItems = $header['nav'];
    $cta = $header['cta'];

    $resolveHref = function (array $item): string {
        if (! empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route'])) {
            return route($item['route']);
        }
        $href = $item['href'] ?? '#';
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return url($href);
        }
        return $href;
    };

    $isExternal = function (array $item) use ($resolveHref): bool {
        if (! empty($item['external'])) {
            return true;
        }
        $href = $resolveHref($item);
        $host = parse_url($href, PHP_URL_HOST);
        return $host && $host !== request()->getHost();
    };

    $isActive = function (array $item) use ($resolveHref): bool {
        $href = $resolveHref($item);
        $current = url()->current();
        if ($href === $current) {
            return true;
        }
        $parsed = parse_url($href);
        $host = $parsed['host'] ?? null;
        if ($host && $host === request()->getHost()) {
            $path = rtrim($parsed['path'] ?? '/', '/') ?: '/';
            $currentPath = rtrim(request()->path(), '/') ?: '/';
            if ($path === $currentPath) {
                return true;
            }
        }
        if (str_contains($href, 'ai.suganta.com') && request()->getHost() === 'ai.suganta.com') {
            return ($item['label'] ?? '') === 'Kaalo Ai';
        }
        return false;
    };

    $loginHref = $resolveHref($cta['login']);
    $registerHref = $resolveHref($cta['register']);
@endphp

<header class="public-header" id="site-header">
    <div class="container public-header__inner">
        <a href="{{ $logo['href'] }}" class="public-logo" aria-label="{{ $logo['alt'] }} — Home">
            <img
                src="{{ asset(ltrim($logo['src'], '/')) }}"
                alt="{{ $logo['alt'] }}"
                width="40"
                height="40"
                class="public-logo__img"
                decoding="async"
            >
            <span class="public-logo__text">{{ $logo['alt'] }}</span>
        </a>

        <nav class="public-nav" id="public-nav" aria-label="Main navigation">
            <ul class="public-nav__list">
                @foreach ($navItems as $item)
                    @php
                        $href = $resolveHref($item);
                        $active = $isActive($item);
                    @endphp
                    <li>
                        <a
                            href="{{ $href }}"
                            class="public-nav__link{{ $active ? ' is-active' : '' }}"
                            @if ($isExternal($item)) target="_blank" rel="noopener noreferrer" @endif
                            @if ($active) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="public-header__actions">
            <a
                href="{{ $loginHref }}"
                class="public-btn public-btn--ghost"
                @if ($isExternal($cta['login'])) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ $cta['login']['label'] }}
            </a>
            <a
                href="{{ $registerHref }}"
                class="public-btn public-btn--primary"
                @if ($isExternal($cta['register'])) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ $cta['register']['label'] }}
            </a>

            <button
                type="button"
                class="public-nav-toggle"
                id="public-nav-toggle"
                aria-expanded="false"
                aria-controls="public-nav-drawer"
                aria-label="Open menu"
            >
                <span class="public-nav-toggle__bar" aria-hidden="true"></span>
                <span class="public-nav-toggle__bar" aria-hidden="true"></span>
                <span class="public-nav-toggle__bar" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div class="public-nav-drawer" id="public-nav-drawer" hidden>
        <div class="public-nav-drawer__backdrop" data-nav-close tabindex="-1" aria-hidden="true"></div>
        <div class="public-nav-drawer__panel" role="dialog" aria-modal="true" aria-label="Mobile menu">
            <div class="public-nav-drawer__head">
                <span class="public-logo__text">{{ $logo['alt'] }}</span>
                <button type="button" class="public-nav-drawer__close" data-nav-close aria-label="Close menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <ul class="public-nav-drawer__list">
                @foreach ($navItems as $item)
                    @php
                        $href = $resolveHref($item);
                        $active = $isActive($item);
                    @endphp
                    <li>
                        <a
                            href="{{ $href }}"
                            class="public-nav-drawer__link{{ $active ? ' is-active' : '' }}"
                            @if ($isExternal($item)) target="_blank" rel="noopener noreferrer" @endif
                            @if ($active) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="public-nav-drawer__cta">
                <a href="{{ $loginHref }}" class="public-btn public-btn--ghost public-btn--block">{{ $cta['login']['label'] }}</a>
                <a href="{{ $registerHref }}" class="public-btn public-btn--primary public-btn--block">{{ $cta['register']['label'] }}</a>
            </div>
        </div>
    </div>
</header>
