@php
    use Capell\Core\Contracts\Pageable;
    use Capell\FoundationTheme\Actions\ResolveSafeCssColorTokenAction;
    use Capell\Frontend\Data\MainContentRenderHookData;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Support\Render\RenderHookRegistry;

    $themeData = is_array($theme) ? $theme : [];

    $mainContentHookData = new MainContentRenderHookData(
        layout: $layout,
        page: $page,
        pageSlot: $pageSlot,
        theme: $themeData,
        containerClass: $containerClass,
        mainClass: $mainClass,
        mainContainerClass: $mainContainerClass,
        layoutNeighborLinks: $layoutNeighborLinks,
    );

    $mainContentHookOutput = app(RenderHookRegistry::class)->renderAll(
        RenderHookLocation::MainContent,
        $mainContentHookData,
        scenario: 'frontend-main-layout',
        target: 'capell::layout.main',
    );

    $resolveMainBackgroundColor = static fn (string $key, string $fallback): string => ResolveSafeCssColorTokenAction::run($themeModel->getMeta($key, $fallback), $fallback);
@endphp

<style>
    :root {
        --bg-color-main: {{ $resolveMainBackgroundColor('main_background_color', '#f8fafc') }};
    }

    .dark:root {
        --bg-color-main: {{ $resolveMainBackgroundColor('main_dark_background_color', '#111827') }};
    }
</style>

<main
    id="main"
    @class([
        'capell-layout-main',
        'relative z-0 flex min-h-full flex-1 flex-col overflow-x-hidden bg-[var(--bg-color-main)] lg:!min-h-0',
        $themeData['meta']['main_class'] ?? '',
        $mainClass ?? '',
    ])
>
    {{-- format-ignore-start --}}
    <div
        @class([
            'grow',
            $mainContainerClass => (bool) $mainContainerClass,
        ])
    >
        @if ($mainContentHookOutput !== '')
            {!! $mainContentHookOutput !!}
        @else
            <x-capell::content
                class="px-6 py-10"
                :content="$pageContentRenderData->content ?? ''"
                :content-type="$pageContentRenderData->contentStructure"
                :title="$pageContentRenderData->title ?? ''"
                heading-tag="h1"
            />
        @endif

        @if ($pageSlot && ! $mainContentHookData->slotRendered)
            {{ $pageSlot }}
            @php
                $mainContentHookData->slotRendered = true;
            @endphp
        @endif

        @if (! $mainContentHookData->pageContentWidgetRendered && ($previousPage instanceof Pageable || $nextPage instanceof Pageable))
            <nav
                class="capell-neighbor-links-mobile px-6 pb-12"
                aria-label="{{ __('capell-theme-foundation::generic.page_navigation') }}"
            >
                <div class="neighbor-links">
                    @if ($previousPage)
                        <x-capell::page.neighbor-link
                            :neighbor-page="$previousPage"
                            neighbor="previous"
                        />
                    @endif

                    @if ($nextPage)
                        <x-capell::page.neighbor-link
                            :neighbor-page="$nextPage"
                            neighbor="next"
                        />
                    @endif
                </div>
            </nav>
        @endif

        @if (is_array($finalCta) && filled($finalCta['title'] ?? null))
            <section class="capell-final-cta mx-auto mb-12 mt-4 max-w-7xl px-6">
                <div class="capell-final-cta-panel">
                    <div>
                        @if (filled($finalCta['kicker'] ?? null))
                            <p class="capell-section-kicker">
                                {{ $finalCta['kicker'] }}
                            </p>
                        @endif

                        <h2>{{ $finalCta['title'] }}</h2>

                        @if (filled($finalCta['summary'] ?? null))
                            <p>{{ $finalCta['summary'] }}</p>
                        @endif
                    </div>

                    @if (filled($finalCta['url'] ?? null) && filled($finalCta['label'] ?? null))
                        <a
                            href="{{ $finalCta['url'] }}"
                            class="capell-final-cta-link"
                            @wireNavigate
                        >
                            {{ $finalCta['label'] }}
                        </a>
                    @endif
                </div>
            </section>
        @endif
    </div>
    {{-- format-ignore-end --}}
</main>
