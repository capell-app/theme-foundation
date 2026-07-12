@props ([
    'assetRenderDataItems',
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-alternating-content widget-ap-alternating-content"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="px-6 py-11 md:px-12 md:py-14">
        @if ($widget->translation)
            <div class="mx-auto mb-8 max-w-2xl text-center md:mb-10">
                @if ($widget->translation->title)
                    <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p class="mt-3 text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        <div class="mx-auto max-w-5xl space-y-8 md:space-y-10">
            @forelse ($assetRenderDataItems as $assetRenderDataItem)
                @php
                    $assetRenderData = $assetRenderDataItem['renderData'];
                    $isRight = $assetRenderData->position === 'right';
                    $icon = (string) ($assetRenderData->icon ?? '');
                    $media = $assetRenderData->image?->media;
                @endphp

                <div
                    class="grid grid-cols-1 items-center gap-5 md:grid-cols-2 md:gap-8"
                >
                    <div
                        @class ([
                            'flex min-h-40 items-center justify-center rounded-2xl bg-gray-50 p-6 md:min-h-48 md:p-8',
                            'md:order-last' => $isRight,
                        ])
                    >
                        @if ($icon !== '')
                            <span class="text-blue-700">
                                @if (str_starts_with($icon, 'heroicon-'))
                                    @svg ($icon, 'h-20 w-20')
                                @else
                                    <span class="text-8xl">{{ $icon }}</span>
                                @endif
                            </span>
                        @elseif ($media)
                            <img
                                src="{{ $media->getFullUrl() }}"
                                alt="{{ $assetRenderData->title }}"
                                class="h-full w-full rounded-xl object-cover"
                            />
                        @endif
                    </div>

                    <div>
                        <div
                            class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white"
                        >
                            {{ $loop->index + 1 }}
                        </div>

                        @if ($assetRenderData->title)
                            <h3 class="mb-3 text-2xl font-bold text-gray-900">
                                {{ $assetRenderData->title }}
                            </h3>
                        @endif

                        @if ($assetRenderData->content)
                            <p class="text-base leading-relaxed text-gray-600">
                                {{ strip_tags($assetRenderData->content) }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p class="text-gray-500">
                        {{ __('capell-theme-foundation::generic.empty_content_sections') }}
                    </p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
