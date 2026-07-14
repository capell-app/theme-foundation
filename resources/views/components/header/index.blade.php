@php
    use Capell\FoundationTheme\Actions\ResolveSafeCssColorTokenAction;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;

    $site = Frontend::site();
    $theme = Frontend::theme();
    $page = Frontend::page();
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
    $usesWireNavigate = $runtimeManifest?->usesWireNavigate ?? false;

    $headerBorderColor = $theme->getMeta('header_divider') ? $theme->getMeta('header_border_color') : null;
    $headerDarkBorderColor = $theme->getMeta('header_divider') ? $theme->getMeta('header_dark_border_color', $headerBorderColor) : null;
    $headerShadow = $theme->getMeta('header_shadow', 'none');
    $pageMeta = is_array($page?->meta) ? $page->meta : [];
    $showHero = ! array_key_exists('show_hero', $pageMeta) || $pageMeta['show_hero'] !== false;
    $headerOverHero = array_key_exists('header_over_hero', $pageMeta)
        ? (bool) $pageMeta['header_over_hero']
        : (bool) $theme->getMeta('header_over_hero', false);

    $containerWidth = GetLayoutContainerWidthAction::run();
    $resolveHeaderColor = static fn (mixed $value, string $fallback): string => ResolveSafeCssColorTokenAction::run($value, $fallback);
@endphp

@props ([
    'menuItemClass' => 'capell-product-nav-item nav-item font-heading group cursor-pointer',
])

<style>
    :root {
        --header-height: {{ $theme->getMeta('header_height', '4.7rem') }};
        --color-header: {{ $resolveHeaderColor($theme->getMeta('header_color', '#101715'), '#101715') }};
        --bg-color-header: {{ $resolveHeaderColor($theme->getMeta('header_background_color', '#fcfffb'), '#fcfffb') }};
        --bg-color-main: {{ $resolveHeaderColor($theme->getMeta('main_background_color', '#f5f7f4'), '#f5f7f4') }};
        --border-header: {{ $headerBorderColor ? $resolveHeaderColor($headerBorderColor, 'transparent') : 'transparent' }};
    }

    .dark:root {
        --color-header: {{ $resolveHeaderColor($theme->getMeta('header_dark_color', '#dceae5'), '#dceae5') }};
        --bg-color-header: {{ $resolveHeaderColor($theme->getMeta('header_dark_background_color', '#0b1716'), '#0b1716') }};
        --bg-color-main: {{ $resolveHeaderColor($theme->getMeta('main_dark_background_color', '#0b1716'), '#0b1716') }};
        --border-header: {{ $headerDarkBorderColor ? $resolveHeaderColor($headerDarkBorderColor, 'transparent') : 'transparent' }};
    }

    #header.has-hero:not(.header-sticky):has(.fixed, .sticky) {
        --header-bg-opacity: 0.8;
    }
</style>

{!! app(RenderHookRegistry::class)->renderAll(RenderHookLocation::HeaderBefore) !!}

@if ($usesAlpine)
    <script>
        window.capellSiteHeader = ({ scrollUp = false } = {}) => ({
            isDarkMode: document.documentElement.classList.contains('dark'),
            isNavigationOverlayOpen: false,
            scrollUp,
            isHidden: false,
            lastScrollY: 0,
            init() {
                if (this.scrollUp) {
                    this.lastScrollY = window.scrollY
                    window.addEventListener(
                        'scroll',
                        () => {
                            const currentY = window.scrollY
                            const delta = currentY - this.lastScrollY
                            if (currentY <= 0) {
                                this.isHidden = false
                            } else if (delta > 4) {
                                this.isHidden = true
                            } else if (delta < -4) {
                                this.isHidden = false
                            }
                            this.lastScrollY = currentY
                        },
                        { passive: true },
                    )
                }

                this.$watch('isDarkMode', (value) => {
                    document.documentElement.classList.toggle('dark', value)
                    localStorage.theme = value ? 'dark' : 'light'
                })

                window.addEventListener(
                    'capell-navigation-menu-open-changed',
                    (event) => {
                        this.isNavigationOverlayOpen = Boolean(
                            event.detail?.open,
                        )
                    },
                )
            },
            toggleDarkMode() {
                this.isDarkMode = !this.isDarkMode
            },
        })
    </script>
@endif

<header
    @if ($usesAlpine)
        x-data="window.capellSiteHeader({
                scrollUp: {{ $theme->scroll_up_header ? 'true' : 'false' }},
            })"
    @endif
    @class ([
        'site-header',
        'capell-product-header transition-padding top-0 right-0 left-0 z-50 flex min-h-[var(--header-height)] w-full text-[var(--color-header)] transition-transform duration-300 ease-in-out motion-reduce:transition-none xl:h-auto',
        'border-b border-[var(--border-header)]' => $headerBorderColor,
        'shadow-sm shadow-black/5 dark:shadow-black/20' => $headerShadow === 'subtle',
        'header-over-hero absolute' => $showHero && $headerOverHero && ! $theme->fixed_header && ! $theme->sticky_header && ! $theme->scroll_up_header,
        'header-sticky sticky top-0 right-0 left-0 z-50' => $theme->sticky_header,
        'header-fixed fixed top-0 right-0 left-0 z-50' => $theme->fixed_header,
        'header-scroll-up fixed top-0 right-0 left-0 z-50' => $theme->scroll_up_header,
    ])
    id="header"
    @if ($usesAlpine)
        x-bind:class="{
            'h-screen': isNavigationOverlayOpen,
            '-translate-y-full':
                scrollUp && isHidden && !isNavigationOverlayOpen,
        }"
    @endif
>
    <div
        @class ([
            'capell-product-header__inner relative isolate w-full max-xl:px-0',
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            @class ([
                'capell-product-header__brand relative',
            ])
        >
            <div
                class="max-w-[min(16rem,70vw)] min-w-0 xl:order-1 xl:w-full xl:max-w-[18rem]"
            >
                <a
                    href="{{ $site->siteDomain->url }}"
                    aria-label="{{ __('capell-frontend::generic.home') }}"
                    @if ($usesWireNavigate) @wireNavigate @endif
                    class="capell-product-header__brand-link text-brand hover:text-primary focus:text-primary"
                >
                    @if ($site->logo || $site->logoInverted)
                        @if ($site->logoInverted)
                            <x-capell::logo
                                :media="$site->logoInverted"
                                :class="'header-logo h-10 max-h-10 w-auto' . ($site->logo ? ' hidden dark:widget' : '')"
                            />
                        @endif

                        @if ($site->logo)
                            <x-capell::logo
                                :media="$site->logo"
                                :class="'header-logo h-10 max-h-10 w-auto' . ($site->logoInverted ? ' dark:hidden' : '')"
                            />
                        @endif
                    @else
                        <span
                            class="capell-product-header__logo-text header-logo-text"
                        >
                            {{ $site->translation->title }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
        {!!
            app(RenderHookRegistry::class)->renderAll(
                RenderHookLocation::HeaderAfter,
                ['menuItemClass' => $menuItemClass],
                scenario: 'theme-foundation-primary-navigation',
                target: 'capell::header.index',
            )
        !!}

        <x-capell::layout.area area="header" />
    </div>
</header>
