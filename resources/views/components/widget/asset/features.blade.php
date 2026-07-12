@php
    use Capell\Core\Contracts\Pageable;
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Core\Models\Page;
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::theme();
@endphp

@props ([
    'assetRenderDataItems',
    'assets',
    'color' => $widget->getMeta('color', 'dark'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'total' => $assets->count(),
    'widget',
    'widgetIndex',
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
])

@if ($assets->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="capell-asset-features widget-assets widget-assets-features"
        :$container
        :$containerKey
        :$containerWidth
        container-class="space-y-6 md:space-y-10"
        :index="$loop->index"
        :$widget
    >
        @if ($widget->translation)
            <x-capell::content
                :compact="true"
                :content="$widget->translation->content"
                :content-type="$widget->blueprint->content_structure"
                :color="$color"
                :divider="$widget->getMeta('content_divider')"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :title="$widget->translation->title"
                :text-align="$widget->getMeta('align')"
                :heading-size="$widget->getMeta('heading_size')"
                :heading-style="$widget->getMeta('heading_style')"
                align="center"
            />
        @endif

        @if ($assets->isNotEmpty())
            <div
                @class ([
                    'grid grid-cols-1 items-start gap-x-10 gap-y-6 md:grid-cols-2',
                    'lg:grid-cols-3' => $widget->image,
                ])
            >
                @if ($widget->image)
                    <div
                        class="flex min-h-full justify-center md:col-span-2 lg:order-2 lg:col-span-1"
                    >
                        <x-capell::media
                            :media="$widget->image"
                            format="webp"
                            size="xl"
                            fit="fit"
                            loading="lazy"
                            class="object-cover"
                        />
                    </div>
                @endif

                <div
                    class="grid space-y-6 md:min-h-full md:auto-rows-fr lg:order-1 lg:space-y-8"
                >
                    @foreach ($assetRenderDataItems->slice(0, ceil($assetRenderDataItems->count() / 2)) as $assetRenderDataItem)
                        <x-capell-theme-foundation::widget.asset.feature-item
                            :$color
                            column="1"
                            :$widget
                            :widget-asset="$assetRenderDataItem['widgetAsset']"
                            :asset-render-data="$assetRenderDataItem['renderData']"
                        />
                    @endforeach
                </div>

                <div
                    class="grid space-y-6 md:min-h-full md:auto-rows-fr lg:order-3 lg:space-y-8"
                >
                    @foreach ($assetRenderDataItems->slice(ceil($assetRenderDataItems->count() / 2)) as $assetRenderDataItem)
                        <x-capell-theme-foundation::widget.asset.feature-item
                            :$color
                            column="2"
                            :$widget
                            :widget-asset="$assetRenderDataItem['widgetAsset']"
                            :asset-render-data="$assetRenderDataItem['renderData']"
                        />
                    @endforeach
                </div>
            </div>
        @endif
    </x-capell-theme-foundation::widget.wrapper>
@endif
