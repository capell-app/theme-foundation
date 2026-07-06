@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\FoundationTheme\Support\ResponsiveAssetLayoutOptions;
    use Capell\Frontend\Contracts\AssetsRegistryInterface;
    use Capell\Frontend\Facades\Frontend;
    use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;

    $theme = Frontend::theme();
@endphp

@props ([
    'assets',
    'color' => $widget->getMeta('color', 'dark'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'widget',
    'widgetIndex' => null,
    'loop',
    'total' => $assets->count(),
    'widget' => $widget,
    'widgetIndex' => $widgetIndex,
    'maxWidth' => $widget->getMeta('max_width'),
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
    'spacing' => $widget->getMeta('spacing', true),
    'columns' => (int) $widget->getMeta('columns'),
    'headingSize' => $widget->getMeta('heading_size'),
    'imagePosition' => $widget->getMeta('image_position', 'left'),
    'responsiveLayoutOptions' => ResponsiveAssetLayoutOptions::fromWidget($widget, $total),
])
@php
    $responsiveLayoutPattern = $responsiveLayoutOptions->pattern;
    $assetLayoutKey = sprintf('%s-%s-%s', $containerKey, $widget->id ?? $widget->key, $loop->index);
    $assetGridId = "asset-grid-{$assetLayoutKey}";
    $assetCarouselId = "asset-carousel-{$assetLayoutKey}";
    $maxWidthStyle = $maxWidth && ! in_array($maxWidth, ['none', 'sm', 'md', 'lg', 'xl'], true)
        ? '--max-max-width: ' . $maxWidth . ';'
        : '';
@endphp

@if ($assets->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="site-widget-asset widget-assets widget-assets-grid"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :widget="$widget"
        container-class="space-y-6 md:space-y-10"
    >
        @if ($widget->translation)
            <x-capell::content
                :compact="true"
                :content="$widget->translation->content"
                :content-type="$widget->type->content_structure"
                :color="$color"
                :divider="$widget->getMeta('content_divider')"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :title="$widget->translation->title"
                :text-align="$widget->getMeta('align')"
                :heading-style="$widget->getMeta('heading_style')"
            />
        @endif

        @if ($assets->isNotEmpty())
            @if ($responsiveLayoutPattern->usesMobileCarousel())
                <div
                    wire:ignore
                    data-carousel="1"
                    data-carousel-align="{{ $responsiveLayoutOptions->carouselAlign() }}"
                    data-carousel-autoplay="{{ (int) $responsiveLayoutOptions->carouselAutoPlay }}"
                    data-carousel-autoplay-delay="{{ $responsiveLayoutOptions->carouselAutoDelay }}"
                    data-carousel-disable-on-interaction="{{ (int) $responsiveLayoutOptions->carouselDisableOnInteraction }}"
                    data-carousel-drag="{{ (int) $responsiveLayoutOptions->carouselDrag }}"
                    data-carousel-effect="slide"
                    data-carousel-highlight-active="{{ (int) $responsiveLayoutOptions->highlightActive }}"
                    data-carousel-id="{{ $assetCarouselId }}"
                    data-carousel-loop="{{ (int) $responsiveLayoutOptions->carouselLoop() }}"
                    data-carousel-navigation="{{ (int) $responsiveLayoutOptions->carouselArrows }}"
                    data-carousel-pagination="{{ (int) $responsiveLayoutOptions->carouselPagination }}"
                    data-carousel-pause-on-hover="{{ (int) $responsiveLayoutOptions->carouselPauseOnHover }}"
                    data-carousel-rewind="{{ (int) $responsiveLayoutOptions->carouselRewind }}"
                    data-carousel-rows="{{ $responsiveLayoutOptions->carouselRows }}"
                    data-carousel-speed="{{ $responsiveLayoutOptions->carouselSpeed }}"
                    data-carousel-touch="{{ (int) $responsiveLayoutOptions->carouselTouch }}"
                    data-carousel-watch-overflow="1"
                    data-carousel-breakpoints="{{ $responsiveLayoutOptions->carouselBreakpointsJson() }}"
                    data-carousel-breakpoints-base="container"
                    @class ([
                        'widget-assets-carousel',
                        'md:hidden' => $responsiveLayoutPattern === ResponsiveLayoutPattern::DesktopGridMobileCarousel,
                        'swiper' => $total > 1,
                    ])
                >
                    <div class="swiper-wrapper">
                        @foreach ($assets as $asset)
                            <div class="swiper-slide h-auto">
                                <x-dynamic-component
                                    :component="app(AssetsRegistryInterface::class)->getAsset($asset['asset_type'])->component"
                                    :componentItem="$widget->getMeta('component_item', AssetComponentEnum::Card->value)"
                                    :$container
                                    :$containerKey
                                    :$loop
                                    :asset="$asset->asset"
                                    :with-child-count="$withChildCount"
                                    :with-date="$withDate"
                                    :with-image="$withImage"
                                    :heading-size="$headingSize"
                                    :image-position="$imagePosition"
                                    :with-parent="$withParent"
                                    :with-summary="$withSummary"
                                    class="widget-asset h-full"
                                />
                            </div>
                        @endforeach
                    </div>

                    @if ($total > 1)
                        <div
                            data-carousel-controls="{{ $assetCarouselId }}"
                            class="swiper-controls mt-4 flex justify-center"
                        >
                            <div
                                class="swiper-pagination pointer-events-auto flex justify-center"
                            ></div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($responsiveLayoutPattern->usesDesktopGrid())
                @if ($responsiveLayoutOptions->shouldUseResponsiveGrid())
                    {!! $responsiveLayoutOptions->gridRowsStyle($assetGridId) !!}
                @endif

                <div
                    id="{{ $assetGridId }}"
                    style="{{ $responsiveLayoutOptions->shouldUseResponsiveGrid() ? $responsiveLayoutOptions->gridColumnsStyle($maxWidthStyle) : '--columns: ' . ($columns ?: $total) . ';' . $maxWidthStyle }}"
                    @class ([
                        'grid',
                        'md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]' => ! $responsiveLayoutOptions->shouldUseResponsiveGrid(),
                        'sm:grid-cols-[repeat(var(--columns-sm),minmax(0,1fr))] md:grid-cols-[repeat(var(--columns-md),minmax(0,1fr))] lg:grid-cols-[repeat(var(--columns-lg),minmax(0,1fr))] xl:grid-cols-[repeat(var(--columns-xl),minmax(0,1fr))]' => $responsiveLayoutOptions->shouldUseResponsiveGrid(),
                        'hidden md:grid' => $responsiveLayoutPattern === ResponsiveLayoutPattern::DesktopGridMobileCarousel,
                        'mx-auto' => $maxWidth,
                        $maxWidth ? match ($maxWidth) {
                            'none' => 'max-w-none',
                            'sm' => 'max-w-sm',
                            'md' => 'max-w-md',
                            'lg' => 'max-w-lg',
                            'xl' => 'max-w-xl',
                            '2xl' => 'max-w-2xl',
                            '3xl' => 'max-w-3xl',
                            default => 'max-w-[var(--max-max-width)]',
                        } : '',
                        'gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-10' => $spacing && $spacing !== 'none',
                        'sm:grid-cols-2' => ! $responsiveLayoutOptions->shouldUseResponsiveGrid() && $total >= 2 && $columns === 0,
                        'md:grid-cols-2' => ! $responsiveLayoutOptions->shouldUseResponsiveGrid() && $total >= 2 && $columns !== 0 && $total <= $columns,
                        'lg:grid-cols-4' => ! $responsiveLayoutOptions->shouldUseResponsiveGrid() && $total >= 4 && $columns !== 0 && $total <= $columns,
                        '2xl:grid-cols-6' => ! $responsiveLayoutOptions->shouldUseResponsiveGrid() && $total >= 6 && $columns !== 0 && $total <= $columns,
                    ])
                >
                    @foreach ($assets as $asset)
                        <x-dynamic-component
                            :component="app(AssetsRegistryInterface::class)->getAsset($asset['asset_type'])->component"
                            :componentItem="$widget->getMeta('component_item', AssetComponentEnum::Card->value)"
                            :$container
                            :$containerKey
                            :$loop
                            :asset="$asset->asset"
                            :with-child-count="$withChildCount"
                            :with-date="$withDate"
                            :with-image="$withImage"
                            :heading-size="$headingSize"
                            :image-position="$imagePosition"
                            :with-parent="$withParent"
                            :with-summary="$withSummary"
                            class="widget-asset"
                        />
                    @endforeach
                </div>
            @endif
        @endif
    </x-capell-theme-foundation::widget.wrapper>
@endif
