@php
    $mobileLinks = collect($links ?? [])
        ->filter(
            static fn (mixed $link): bool => filled(data_get($link, 'label', data_get($link, 'title')))
                && filled(data_get($link, 'url', data_get($link, 'href'))),
        )
        ->values();
    $mobileCtaLabel = $ctaLabel ?? null;
    $mobileCtaUrl = $ctaUrl ?? null;
    $mobileMenuId = preg_replace(
        '/[^A-Za-z0-9_-]+/',
        '-',
        (string) ($menuId ?? 'capell-mobile-navigation-panel'),
    ) ?: 'capell-mobile-navigation-panel';
    $hasMobileCta = filled($mobileCtaLabel) && filled($mobileCtaUrl);
@endphp

@if ($mobileLinks->isNotEmpty() || $hasMobileCta)
    <details class="capell-mobile-nav">
        <summary
            class="capell-mobile-nav__toggle"
            aria-controls="{{ $mobileMenuId }}"
        >
            <span>
                {{ $menuLabel ?? __('capell-theme-foundation::generic.menu') }}
            </span>
        </summary>

        <nav
            id="{{ $mobileMenuId }}"
            class="capell-mobile-nav__panel"
            aria-label="{{ __('capell-theme-foundation::generic.mobile_navigation') }}"
        >
            @foreach ($mobileLinks as $link)
                <a
                    href="{{ data_get($link, 'url', data_get($link, 'href')) }}"
                    @if ((bool) data_get($link, 'active', false)) aria-current="page" @endif
                >
                    {{ data_get($link, 'label', data_get($link, 'title', '')) }}
                </a>
            @endforeach

            @if ($hasMobileCta)
                <a
                    class="capell-mobile-nav__cta"
                    href="{{ $mobileCtaUrl }}"
                >
                    {{ $mobileCtaLabel }}
                </a>
            @endif
        </nav>
    </details>
@endif
