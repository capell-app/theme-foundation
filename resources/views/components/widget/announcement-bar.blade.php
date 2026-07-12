@props ([
    'align' => $widget->getMeta('align', 'center'),
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'container',
    'loop',
    'containerKey',
    'containerWidth' => null,
    'widget',
])

<x-capell-theme-foundation::widget.wrapper
    class="capell-widget-announcement-bar widget-announcement-bar"
    container-class="text-center"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <div class="rounded-md border border-current/15 px-4 py-3">
        <x-capell::content
            class="widget-content"
            :compact="true"
            :content="$content"
            :content-type="$widget->blueprint->content_structure"
            :heading-size="$widget->getMeta('heading_size', 'h3')"
            :title="$title"
            :text-align="$align"
        />

        @if ($widget->getMeta('actions'))
            <x-capell::actions
                class="mt-3"
                :actions="$widget->getMeta('actions')"
                :align="$align"
            />
        @endif
    </div>
</x-capell-theme-foundation::widget.wrapper>
