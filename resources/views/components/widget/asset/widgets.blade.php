<?php
use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Contracts\AssetsRegistryInterface;
@endphp

@props ([
    'assets',
    'color' => $widget->getMeta('color', 'dark'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'firstAssetRenderData' => null,
    'lastAssetRenderData' => null,
    'loop',
    'total' => null,
    'widget',
    'widgetIndex',
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
    'spacing' => $widget->getMeta('spacing', true),
    'columns' => (int) $widget->getMeta('columns'),
])

@php
    $total ??= $assets->count();
@endphp

@if ($assets->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="site-widget-asset widget-assets-widgets relative"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
        container-class="space-y-6 md:space-y-10"
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
                :heading-style="$widget->getMeta('heading_style')"
            />
        @endif

        @if ($assets->isNotEmpty())
            <div>
                @if ($color = ($firstAssetRenderData->meta['color'] ?? null))
                    <x-capell-theme-foundation::widget.asset.extended-background
                        :$color
                        position="left"
                    />
                @endif

                <div
                    style="--columns: {{ $columns ?: $total }}"
                    @class ([
                        'grid md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]',
                        'gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-10' => $spacing && $spacing !== 'none',
                        'sm:grid-cols-2' => $total >= 2 && $columns === 0,
                        'md:grid-cols-2' => $total >= 2 && $columns !== 0 && $total <= $columns,
                        'lg:grid-cols-4' => $total >= 4 && $columns !== 0 && $total <= $columns,
                        '2xl:grid-cols-6' => $total >= 6 && $columns !== 0 && $total <= $columns,
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
                            :with-parent="$withParent"
                            :with-summary="$withSummary"
                            class="widget-widget-item"
                        />
                    @endforeach
                </div>
                @if ($color = ($lastAssetRenderData->meta['color'] ?? null))
                    <x-capell-theme-foundation::widget.asset.extended-background
                        :$color
                        position="right"
                    />
                @endif
            </div>
        @endif
    </x-capell-theme-foundation::widget.wrapper>
@endif
