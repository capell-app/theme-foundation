@php
    use Capell\FoundationTheme\Actions\ResolveSafeCssColorTokenAction;
    use Capell\FoundationTheme\View\Components\Footer\Index as FooterComponent;
    use Capell\Frontend\Actions\RenderHtmlContentAction;
    use Capell\Frontend\Facades\Frontend;

    if (! isset($footerDividerColor)) {
        foreach (get_object_vars(new FooterComponent) as $footerVariable => $footerValue) {
            ${$footerVariable} = $footerValue;
        }
    }

    $theme ??= Frontend::theme();
    $resolveFooterColor = static fn (string $key, string $fallback): string => ResolveSafeCssColorTokenAction::run($theme->getMeta($key, $fallback), $fallback);
@endphp

<style>
    :root {
        --color-footer: {{ $resolveFooterColor('footer_color', '#1f2937') }};
        --color-footer-heading: color-mix(
            in srgb,
            var(--color-footer),
            #020617 18%
        );
        --color-footer-muted: color-mix(
            in srgb,
            var(--color-footer),
            var(--bg-color-footer) 28%
        );
        --color-footer-link: color-mix(
            in srgb,
            var(--color-footer),
            #020617 8%
        );
        --bg-color-footer: {{ $resolveFooterColor('footer_background_color', '#f1f5f9') }};
        --bg-color-footer-panel: color-mix(
            in srgb,
            var(--bg-color-footer),
            #020617 3%
        );
        --bg-color-footer-muted: color-mix(
            in srgb,
            var(--bg-color-footer),
            #020617 6%
        );
        --border-color-footer: {{ $resolveFooterColor('footer_border_color', '#e2e8f0') }};
    }

    .dark:root {
        --color-footer: {{ $resolveFooterColor('footer_dark_color', '#e5e7eb') }};
        --color-footer-heading: color-mix(
            in srgb,
            var(--color-footer),
            #ffffff 10%
        );
        --color-footer-muted: color-mix(
            in srgb,
            var(--color-footer),
            var(--bg-color-footer) 24%
        );
        --color-footer-link: color-mix(
            in srgb,
            var(--color-footer),
            #ffffff 6%
        );
        --bg-color-footer: {{ $resolveFooterColor('footer_dark_background_color', '#111827') }};
        --bg-color-footer-panel: color-mix(
            in srgb,
            var(--bg-color-footer),
            #ffffff 5%
        );
        --bg-color-footer-muted: color-mix(
            in srgb,
            var(--bg-color-footer),
            #ffffff 8%
        );
        --border-color-footer: {{ $resolveFooterColor('footer_dark_border_color', '#374151') }};
    }
</style>

<button
    type="button"
    class="site-scroll-top scroll-top hover:bg-primary focus:bg-primary text-primary sticky bottom-0 left-full z-999 hidden h-10 w-10 -translate-x-6 items-center justify-center rounded-t-sm bg-gray-200 transition hover:text-white focus:text-white"
    aria-label="{{ __('capell-theme-foundation::generic.scroll_to_top') }}"
    title="{{ __('capell-theme-foundation::generic.scroll_to_top') }}"
    onclick="
        window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches
                ? 'auto'
                : 'smooth',
        })
    "
>
    @svg('heroicon-o-chevron-up', 'h-6 w-6', ['aria-hidden' => 'true'])
</button>
<footer
    id="footer"
    @class([
        'z-0 bg-[var(--bg-color-footer)] text-sm text-[var(--color-footer)]',
        'border-t border-[var(--border-color-footer)]' => $footerDividerColor,
    ])
>
    <div
        @class([
            '@container flex-wrap px-8',
            'py-6 lg:py-7' => $footerSpacing === 'compact',
            'py-8 lg:py-10' => $footerSpacing === 'default' && ! $hasFooterPrimaryContent,
            'py-10 lg:py-12' => $footerSpacing === 'default' && $hasFooterPrimaryContent,
            'py-12 lg:py-14' => $footerSpacing === 'comfortable' && ! $hasFooterPrimaryContent,
            'py-14 lg:py-16' => $footerSpacing === 'comfortable' && $hasFooterPrimaryContent,
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            @class([
                'px-0 py-0',
                'flex justify-center' => ! $hasFooterPrimaryContent,
                'grid gap-x-8 gap-y-8 xl:flex xl:flex-row xl:gap-x-10 @2xl:grid-cols-2 @4xl:grid-cols-3' => $hasFooterPrimaryContent,
            ])
        >
            <x-capell::footer.site-info
                :$site
                :$contactPage
                @class([
                    'shrink-0',
                    'max-w-xl text-center' => ! $hasFooterPrimaryContent,
                    'order-2 text-center lg:order-1 lg:text-left xl:max-w-[30%] xl:pr-10' => $hasFooterPrimaryContent,
                ])
            />

            @if ($hasFooterPrimaryContent)
                <div
                    class="order-1 grid grow gap-8 lg:order-2 xl:flex @4xl:col-span-2"
                >
                    @if ($hasFooterMenu)
                        <x-capell::footer.menu
                            :$headingClass
                            :items="$footerMenuItems"
                            class="grow"
                        />
                    @endif

                    @if ($hasLatestFooterPages)
                        <x-capell::footer.latest-pages
                            :$headingClass
                            :pages="$latestFooterPages"
                        />
                    @endif

                    {!! $footerRenderHooks !!}
                </div>
            @endif
        </div>
    </div>

    @if ($relatedSites->isNotEmpty())
        <div
            @class([
                '@container px-8 pb-8 lg:pb-10',
                $containerWidth->getContainerClass(),
            ])
        >
            <x-capell::footer.related-sites :$relatedSites />
        </div>
    @endif

    @if ($subFooterMenuItems?->isNotEmpty() || $footerCopy || count($siteLanguages) > 1)
        <div class="bg-[var(--bg-color-footer-muted)]">
            <x-capell::footer.sub-footer
                :items="$subFooterMenuItems"
                :$siteLanguages
                class="sub-footer"
            >
                {!!
                    RenderHtmlContentAction::run(Lang::get($footerCopy, [
                        'name' => $site->name,
                        'year' => date('Y'),
                    ]))
                !!}
            </x-capell::footer.sub-footer>
        </div>
    @endif
</footer>

@include('capell::partials.svg-sprite')
