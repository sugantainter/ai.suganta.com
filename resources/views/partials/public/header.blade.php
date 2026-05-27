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
        if (request()->routeIs('kaalo.home') && ($item['label'] ?? '') === 'Kaalo Ai') {
            return true;
        }
        if (str_contains($href, 'ai.suganta.com') && request()->getHost() === 'ai.suganta.com' && ! request()->routeIs('kaalo.home')) {
            return ($item['label'] ?? '') === 'Kaalo Ai';
        }
        return false;
    };

    $loginHref = $resolveHref($cta['login']);
    $registerHref = $resolveHref($cta['register']);

    $navUser = $publicNavUser ?? [
        'authenticated' => false,
        'name' => '',
        'email' => null,
        'initials' => '',
        'avatar_url' => null,
        'dashboard_url' => url('/'),
        'dashboard_label' => 'Dashboard',
    ];
@endphp

<header class="public-header" id="site-header">
    <div class="container public-header__inner">
        <a href="{{ $logo['href'] }}"  aria-label="{{ $logo['alt'] }} — Home">
            <img
                src="{{ asset(ltrim($logo['src'], '/')) }}"
                alt="{{ $logo['alt'] }}"
                width="120"
                height="50"
                decoding="async"
            >
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
            @if ($navUser['authenticated'])
                <div class="public-user-menu" aria-label="Account">
                    <div class="public-user-chip">
                        @if (! empty($navUser['avatar_url']))
                            <img
                                src="{{ $navUser['avatar_url'] }}"
                                alt=""
                                class="public-user-chip__avatar"
                                width="36"
                                height="36"
                                decoding="async"
                            >
                        @else
                            <span class="public-user-chip__avatar public-user-chip__avatar--initials" aria-hidden="true">
                                {{ $navUser['initials'] }}
                            </span>
                        @endif
                        <span class="public-user-chip__meta">
                            <span class="public-user-chip__name">{{ $navUser['name'] }}</span>
                            @if (! empty($navUser['email']))
                                <span class="public-user-chip__email">{{ $navUser['email'] }}</span>
                            @endif
                        </span>
                    </div>
                    <a href="{{ $navUser['dashboard_url'] }}" class="public-btn public-btn--primary public-btn--dashboard">
                        {{ $navUser['dashboard_label'] }}
                    </a>
                </div>
            @else
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
            @endif

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
                @if ($navUser['authenticated'])
                    <div class="public-user-drawer-card">
                        @if (! empty($navUser['avatar_url']))
                            <img src="{{ $navUser['avatar_url'] }}" alt="" class="public-user-drawer-card__avatar" width="48" height="48" decoding="async">
                        @else
                            <span class="public-user-drawer-card__avatar public-user-drawer-card__avatar--initials">{{ $navUser['initials'] }}</span>
                        @endif
                        <div>
                            <div class="public-user-drawer-card__name">{{ $navUser['name'] }}</div>
                            @if (! empty($navUser['email']))
                                <div class="public-user-drawer-card__email">{{ $navUser['email'] }}</div>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $navUser['dashboard_url'] }}" class="public-btn public-btn--primary public-btn--block">{{ $navUser['dashboard_label'] }}</a>
                @else
                    <a href="{{ $loginHref }}" class="public-btn public-btn--ghost public-btn--block">{{ $cta['login']['label'] }}</a>
                    <a href="{{ $registerHref }}" class="public-btn public-btn--primary public-btn--block">{{ $cta['register']['label'] }}</a>
                @endif
            </div>
        </div>
    </div>
</header>
