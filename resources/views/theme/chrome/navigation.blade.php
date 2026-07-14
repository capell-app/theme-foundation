{{--
    Shared vertical-theme chrome: top navigation bar.
    Rendered for every theme whose ServiceProvider points its 'navigation'
    ViewSectionRenderer at `capell-theme-foundation::theme.chrome.navigation`.
    Styled entirely with the `theme-chrome-nav__*` plain-CSS classes shipped in
    `resources/css/theme/chrome.css`, driven by the brand `--theme-*` custom
    properties, so each theme keeps its own colours/typography.
    Data shape (NavigationData): brandName, items[{label,url,active?}],
    ctaLabel, ctaUrl.
--}}
@php
    $navigationItems = collect($section->items ?? [])
        ->filter(
            static fn (mixed $item): bool => filled(data_get($item, 'label'))
                && filled(data_get($item, 'url')),
        )
        ->values();
    $hasNavigationCta = filled($section->ctaLabel ?? null) && filled($section->ctaUrl ?? null);
@endphp

<nav
    class="theme-chrome-nav"
    aria-label="{{ __('capell-theme-foundation::generic.main_navigation') }}"
>
    <div class="theme-chrome-nav__inner">
        <a
            href="/"
            class="theme-chrome-nav__brand"
            aria-label="{{ __('capell-theme-foundation::generic.home_for', ['name' => $section->brandName]) }}"
        >
            <span
                class="theme-chrome-nav__brand-mark"
                aria-hidden="true"
            ></span>
            <span class="theme-chrome-nav__brand-name">
                {{ $section->brandName }}
            </span>
        </a>

        @if ($navigationItems->isNotEmpty())
            <div class="theme-chrome-nav__links">
                @foreach ($navigationItems as $item)
                    <a
                        href="{{ data_get($item, 'url') }}"
                        class="theme-chrome-nav__link"
                        @if ((bool) data_get($item, 'active', false)) aria-current="page" @endif
                    >
                        {{ data_get($item, 'label') }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($navigationItems->isNotEmpty() || $hasNavigationCta)
            <details class="theme-chrome-nav__mobile">
                <summary
                    class="theme-chrome-nav__mobile-toggle"
                    aria-controls="theme-chrome-navigation-panel"
                >
                    <span
                        >{{ __('capell-theme-foundation::generic.menu') }}</span
                    >
                </summary>

                <div
                    id="theme-chrome-navigation-panel"
                    class="theme-chrome-nav__mobile-panel"
                >
                    @if ($navigationItems->isNotEmpty())
                        @foreach ($navigationItems as $item)
                            <a
                                href="{{ data_get($item, 'url') }}"
                                class="theme-chrome-nav__link"
                                @if ((bool) data_get($item, 'active', false)) aria-current="page" @endif
                            >
                                {{ data_get($item, 'label') }}
                            </a>
                        @endforeach
                    @endif

                    @if ($hasNavigationCta)
                        <a
                            href="{{ $section->ctaUrl }}"
                            class="theme-chrome-nav__cta theme-chrome-nav__mobile-cta"
                        >
                            {{ $section->ctaLabel }}
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if ($hasNavigationCta)
            <a
                href="{{ $section->ctaUrl }}"
                class="theme-chrome-nav__cta"
            >
                {{ $section->ctaLabel }}
            </a>
        @endif
    </div>
</nav>
