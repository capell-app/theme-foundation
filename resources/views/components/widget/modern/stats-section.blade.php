@props ([
    'assetRenderDataItems',
    'layout' => $widget->getMeta('layout', 'horizontal'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    $responsiveGrid = '!flex snap-x [scrollbar-width:none] gap-4 !overflow-x-auto pb-3 md:!grid md:!overflow-visible md:pb-0 [&::-webkit-scrollbar]:hidden';
@endphp

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-stats-section widget-ap-stats-section"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="px-6 py-12 md:px-12 md:py-16">
        @if ($widget->translation)
            <div class="mx-auto mb-12 max-w-2xl text-center">
                @if ($widget->translation->title)
                    <h2
                        class="mb-3 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p class="text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        <div
            @class ([
                'mx-auto',
                'grid max-w-md grid-cols-1 gap-6' => $layout === 'vertical',
                'max-w-5xl ' . $responsiveGrid . ' md:grid-cols-4' => $layout !== 'vertical',
            ])
        >
            @forelse ($assetRenderDataItems as $assetRenderDataItem)
                @php
                    $assetRenderData = $assetRenderDataItem['renderData'];
                    $icon = (string) ($assetRenderData->icon ?? '');
                @endphp

                <div
                    class="min-w-full snap-start rounded-xl border border-stone-200 bg-white p-6 text-center md:min-w-0 md:p-8"
                >
                    @if ($icon !== '')
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                        >
                            @if (str_starts_with($icon, 'heroicon-'))
                                @svg ($icon, 'h-6 w-6')
                            @else
                                <span class="text-3xl">{{ $icon }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($assetRenderData->content)
                        <p
                            class="mb-1 text-3xl font-bold text-emerald-700 md:text-4xl"
                        >
                            {{ strip_tags($assetRenderData->content) }}
                        </p>
                    @endif

                    @if ($assetRenderData->title)
                        <p class="text-sm font-medium text-gray-500">
                            {{ $assetRenderData->title }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500">
                        {{ __('capell-theme-foundation::generic.empty_stats') }}
                    </p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
