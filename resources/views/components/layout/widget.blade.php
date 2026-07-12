@php
    use Capell\FoundationTheme\View\Components\Widget\Page\Children as PageChildrenComponent;
    use Capell\FoundationTheme\View\Components\Widget\Page\Content as PageContentComponent;
    use Capell\FoundationTheme\View\Components\Widget\Page\Latest as PageLatestComponent;
    use Capell\FoundationTheme\View\Components\Widget\Page\Siblings as PageSiblingsComponent;
    use Capell\FoundationTheme\View\Components\Widget\Slot as SlotComponent;
    use Capell\Frontend\Facades\Frontend;
    use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
    use Illuminate\Contracts\View\View as ViewContract;
    use Livewire\Blaze\Blaze;
@endphp

@props ([
    'component',
    'container',
    'containerColspan' => null,
    'containerKey',
    'containerIndex',
    'containerWidth' => null,
    'layout',
    'loop',
    'occurrence' => $widgetData['occurrence'] ?? 1,
    'pageSlot' => null,
    'type',
    'widget',
    'widgetIndex',
    'widgetData',
])

@if ($type === 'blade')
    @php
        $pageWidgetComponent = match ($component) {
            'capell::widget.page.children' => PageChildrenComponent::class,
            'capell::widget.page.content' => PageContentComponent::class,
            'capell::widget.page.latest' => PageLatestComponent::class,
            'capell::widget.page.siblings' => PageSiblingsComponent::class,
            'capell::widget.slot' => SlotComponent::class,
            'capell::widget.page.children' => PageChildrenComponent::class,
            'capell::widget.page.content' => PageContentComponent::class,
            'capell::widget.page.latest' => PageLatestComponent::class,
            'capell::widget.page.siblings' => PageSiblingsComponent::class,
            'capell::widget.slot' => SlotComponent::class,
            default => null,
        };
    @endphp

    @if ($pageWidgetComponent !== null)
        @php
            $pageWidget = new $pageWidgetComponent(
                container: $container,
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
                loop: $loop,
                widget: $widget,
                widgetData: $widgetData,
                pageSlot: $pageSlot,
            );

            $pageWidgetOutput = $pageWidget->render();

            if ($pageWidgetOutput instanceof ViewContract) {
                $wasBlazeEnabled = Blaze::isEnabled();
                Blaze::disable();

                try {
                    $pageWidgetOutput = $pageWidgetOutput->render();
                } finally {
                    if ($wasBlazeEnabled) {
                        Blaze::enable();
                    }
                }
            }
        @endphp

        {!! $pageWidgetOutput !!}
    @else
        <x-dynamic-component
            class="site-layout-widget"
            :component="$component"
            :$container
            :$containerColspan
            :$containerKey
            :$containerIndex
            :$containerWidth
            :widget="$widget"
            :widgetData="$widgetData"
            :widgetIndex="$widgetIndex"
            :$loop
            :$pageSlot
            :$occurrence
            :$widget
            :$widgetData
            :$widgetIndex
        />
    @endif
@elseif ($type === 'livewire')
    @php
        $widgetReference = OpaqueWidgetReference::encode([
            'container_key' => $containerKey,
            'widget_key' => $widgetData['widget_key'] ?? $widget->key,
            'layout_id' => $layout?->getKey(),
            'language_id' => Frontend::language()?->getKey(),
            'occurrence' => $occurrence,
            'page_id' => Frontend::page()?->getKey(),
            'page_type' => Frontend::page()?->getMorphClass(),
            'site_id' => Frontend::site()?->getKey(),
            'widget_data' => $widgetData,
            'widget_index' => $widgetIndex,
        ]);
    @endphp

    @livewire ($component,
        [
            'widgetReference' => $widgetReference,
        ],
        key($containerKey . '-' . $widget->key . '-' . $occurrence))
@endif
