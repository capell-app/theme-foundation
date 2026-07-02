@php
    use Capell\Navigation\Data\NavigationItemRenderData;
    use Illuminate\Support\Collection;

    $themeSecondaryContainers = data_get($theme, 'secondary_containers', []);
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'groupItems' => $widgetData['meta']['group_items'] ?? false,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'items' => [],
    'headingContent' => null,
    'headingContentStructure' => null,
    'headingTitle' => null,
    'listComponent' => 'capell::list',
    'loop',
    'widget',
])
@if ($items->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="capell-widget-navigation widget-navigation"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
    >
        @if ($headingTitle || $headingContent)
            <x-capell::content
                class="mb-5"
                :compact="true"
                :content="$headingContent"
                :content-type="$headingContentStructure"
                :divider="$widget->getMeta('content_divider')"
                :muted="in_array($containerKey, $themeSecondaryContainers, true)"
                :text-align="$widget->getMeta('align')"
                :title="$headingTitle"
                :heading-style="$widget->getMeta('heading_style')"
                :heading-tag="$showPageTitle ? 'h1' : null"
            />
        @endif

        @if ($groupItems && count($items) > 5)
            <div class="grid md:grid-cols-2">
                @php
                    /**
                     * @var Collection<NavigationItemRenderData> $items
                     */
                    $half = (int) ceil(count($items) / $columns);

                    /**
                     * @var Collection<Collection<NavigationItemRenderData>> $chunks
                     */
                    $chunks = $items->chunk($half);
                @endphp

                @foreach ($chunks as $chunk)
                    <x-dynamic-component
                        :component="$listComponent"
                        class="widget-navigation-list"
                    >
                        @foreach ($chunk as $item)
                            <x-dynamic-component
                                :component="$item instanceof NavigationItemRenderData ? ($item->componentItem ?: 'capell::list.item') : (! empty($item->data['component_item']) ? $item->data['component_item'] : 'capell::list.item')"
                                class="widget-navigation-item"
                                :$item
                            />
                        @endforeach
                    </x-dynamic-component>
                @endforeach
            </div>
        @else
            <x-dynamic-component
                :component="$listComponent"
                class="widget-navigation-list widget-navigation-lit-children text-sm"
            >
                @foreach ($items as $item)
                    <x-dynamic-component
                        :component="$item instanceof NavigationItemRenderData ? ($item->componentItem ?: 'capell::list.item') : (! empty($item->data['component_item']) ? $item->data['component_item'] : 'capell::list.item')"
                        :$item
                        class="widget-navigation-item widget-navigation-child-item"
                    />
                @endforeach
            </x-dynamic-component>
        @endif
    </x-capell-theme-foundation::widget.wrapper>
@endif
