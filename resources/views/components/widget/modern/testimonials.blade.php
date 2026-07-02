@props([
    'assetRenderDataItems',
    'columns' => $widget->getMeta('columns', 2),
    'displayMode' => $widget->getMeta('display_mode', 'grid'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    $gridClasses = [
        1 => 'mx-auto max-w-2xl grid-cols-1',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    ];

    $gridClass = $gridClasses[(int) $columns] ?? $gridClasses[2];
    $responsiveGrid = '!flex snap-x [scrollbar-width:none] gap-4 !overflow-x-auto pb-3 md:!grid md:!overflow-visible md:pb-0 [&::-webkit-scrollbar]:hidden';
@endphp

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-testimonials widget-ap-testimonials"
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
                        class="mb-4 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl"
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

        @if ($displayMode === 'carousel')
            <div
                class="layout-builder-testimonials-carousel relative mx-auto max-w-2xl"
            >
                <div class="relative overflow-hidden rounded-2xl">
                    <div
                        class="carousel-container flex transition-transform duration-300 ease-in-out"
                    >
                        @forelse ($assetRenderDataItems as $assetRenderDataItem)
                            @php
                                $assetRenderData = $assetRenderDataItem['renderData'];
                                $icon = $assetRenderData->icon;
                                $role = $assetRenderData->position;
                            @endphp

                            <div class="carousel-slide min-w-full">
                                <div
                                    class="h-full rounded-xl border border-stone-200 bg-white p-8"
                                >
                                    <div
                                        class="mb-4 font-serif text-5xl leading-none text-stone-300"
                                    >
                                        &ldquo;
                                    </div>

                                    @if ($assetRenderData->content)
                                        <blockquote class="mb-6">
                                            <p
                                                class="text-lg leading-relaxed text-gray-700 italic"
                                            >
                                                {{ strip_tags($assetRenderData->content) }}
                                            </p>
                                        </blockquote>
                                    @endif

                                    <div
                                        class="flex items-center gap-4 border-t border-gray-200 pt-6"
                                    >
                                        @if ($icon)
                                            <div
                                                class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                                            >
                                                @if (str_starts_with((string) $icon, 'heroicon-'))
                                                    @svg($icon, 'h-5 w-5')
                                                @else
                                                    <span class="text-3xl">
                                                        {{ $icon }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        <div>
                                            @if ($assetRenderData->title)
                                                <p
                                                    class="font-bold text-gray-900"
                                                >
                                                    {{ $assetRenderData->title }}
                                                </p>
                                            @endif

                                            @if ($role)
                                                <p
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{ $role }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="w-full py-12 text-center">
                                <p class="text-gray-500">
                                    {{ __('capell-theme-foundation::generic.empty_testimonials') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($assetRenderDataItems->count() > 1)
                    <button
                        class="carousel-prev absolute top-1/2 left-0 -translate-x-12 -translate-y-1/2 text-2xl text-gray-600 hover:text-gray-900"
                        data-carousel-direction="-1"
                    >
                        ←
                    </button>
                    <button
                        class="carousel-next absolute top-1/2 right-0 translate-x-12 -translate-y-1/2 text-2xl text-gray-600 hover:text-gray-900"
                        data-carousel-direction="1"
                    >
                        →
                    </button>

                    <div class="mt-6 flex justify-center gap-2">
                        @for ($dotIndex = 0; $dotIndex < $assetRenderDataItems->count(); $dotIndex++)
                            <button
                                class="{{ $dotIndex === 0 ? 'is-active bg-stone-900' : 'bg-stone-300' }} carousel-dot h-2.5 w-2.5 rounded-full transition-all"
                                data-carousel-slide="{{ $dotIndex }}"
                            ></button>
                        @endfor
                    </div>
                @endif
            </div>
        @else
            <div class="{{ $responsiveGrid }} {{ $gridClass }}">
                @forelse ($assetRenderDataItems as $assetRenderDataItem)
                    @php
                        $assetRenderData = $assetRenderDataItem['renderData'];
                        $icon = $assetRenderData->icon;
                        $role = $assetRenderData->position;
                    @endphp

                    <div
                        class="min-w-full snap-start rounded-xl border border-stone-200 bg-white p-6 md:min-w-0 md:p-8"
                    >
                        <div
                            class="mb-4 font-serif text-5xl leading-none text-indigo-200"
                        >
                            &ldquo;
                        </div>

                        @if ($assetRenderData->content)
                            <blockquote class="mb-6">
                                <p
                                    class="text-lg leading-relaxed text-gray-700 italic"
                                >
                                    {{ strip_tags($assetRenderData->content) }}
                                </p>
                            </blockquote>
                        @endif

                        <div
                            class="flex items-center gap-4 border-t border-gray-200 pt-6"
                        >
                            @if ($icon)
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-700"
                                >
                                    @if (str_starts_with((string) $icon, 'heroicon-'))
                                        @svg($icon, 'h-5 w-5')
                                    @else
                                        <span class="text-3xl">
                                            {{ $icon }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div>
                                @if ($assetRenderData->title)
                                    <p class="font-bold text-gray-900">
                                        {{ $assetRenderData->title }}
                                    </p>
                                @endif

                                @if ($role)
                                    <p class="text-sm text-gray-500">
                                        {{ $role }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <p class="text-gray-500">
                            {{ __('capell-theme-foundation::generic.empty_testimonials') }}
                        </p>
                    </div>
                @endforelse
            </div>
        @endif
    </section>
</x-capell-theme-foundation::widget.wrapper>
