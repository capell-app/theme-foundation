@props ([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-theme-foundation::widget.wrapper
    class="capell-widget-hero"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        class="mx-auto flex max-w-5xl flex-col items-start gap-5 px-6 py-12 lg:py-16"
    >
        @if (is_string($title = $widget->translation?->title) && $title !== '')
            <h2
                class="font-heading text-3xl font-semibold tracking-tight text-gray-950 dark:text-white"
            >
                {{ $title }}
            </h2>
        @endif

        @if (is_string($content = $widget->translation?->content) && $content !== '')
            <div class="max-w-3xl text-lg text-gray-700 dark:text-gray-200">
                {!! strip_tags($content, '<p><strong><em><a><ul><ol><li>') !!}
            </div>
        @endif

        @if (is_string($callToActionLabel = $widget->getMeta('call_to_action_label')) && $callToActionLabel !== '' && is_string($callToActionUrl = $widget->getMeta('call_to_action_url')) && $callToActionUrl !== '')
            <a
                class="inline-flex rounded-md bg-gray-950 px-5 py-3 font-medium text-white outline-offset-4 transition hover:bg-gray-800 focus-visible:outline-2 focus-visible:outline-gray-950 motion-reduce:transition-none dark:bg-white dark:text-gray-950"
                href="{{ $callToActionUrl }}"
            >
                {{ $callToActionLabel }}
            </a>
        @endif
    </section>
</x-capell-theme-foundation::widget.wrapper>
