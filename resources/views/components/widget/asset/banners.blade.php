@php
    use Capell\FoundationTheme\Actions\BuildAssetBannerItemsAction;
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'containerKey',
    'containerIndex',
    'backgroundOverlay' => (bool) $widget->getMeta('background_overlay'),
    'loop',
    'total' => null,
    'widget',
    'widgetIndex',
])
@php
    $carouselId = sprintf('banner-carousel-%s-%s', $widget->id ?? $widget->key, $loop->index);
    $bannerItems = BuildAssetBannerItemsAction::run($widget);
    $total ??= $bannerItems->count();
@endphp

@if ($bannerItems->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <section
        class="capell-asset-banners widget-assets-banner relative flex w-full items-center justify-center overflow-hidden"
        style="
            --swiper-pagination-bottom: 2rem;
            --swiper-pagination-color: #111827;
            --swiper-pagination-bullet-inactive-color: #6b7280;
        "
        data-carousel-scope
    >
        <div
            class="swiper relative grid h-full w-full"
            data-auto="0"
            data-carousel="1"
            data-carousel-autoplay="0"
            data-carousel-effect="slide"
            data-carousel-id="{{ $carouselId }}"
            data-carousel-loop="0"
            data-carousel-navigation="0"
            data-carousel-pagination="{{ (int) ($total > 1) }}"
            data-carousel-touch="1"
            data-carousel-watch-overflow="1"
            data-loop="0"
        >
            <div class="swiper-wrapper h-full w-full">
                @foreach ($bannerItems as $bannerItem)
                    @php
                        $hasImage = (bool) $bannerItem->image;
                    @endphp

                    <div
                        @class([
                            'swiper-slide widget-banner-item relative flex min-h-[20rem] w-full shrink-0 basis-full items-center justify-center',
                            'bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-50' => ! $hasImage,
                            'swiper-slide-active' => $loop->first,
                        ])
                    >
                        @if ($bannerItem->image)
                            <x-capell::media
                                format="webp"
                                curation="hero"
                                :media="$bannerItem->image"
                                height="100vh"
                                :alt="$bannerItem->alt"
                                :loading="$loop->first ? 'eager' : 'lazy'"
                                :class="
                                    Arr::toCssClasses([
                                        'absolute inset-0 w-full h-full object-cover pointer-events-none z-0 bg-no-repeat bg-center bg-cover',
                                    ])
                                "
                            />
                            @if ($backgroundOverlay)
                                <div
                                    class="absolute inset-0 z-10 bg-black/40 shadow-[inset_0_0_8rem_4rem_rgba(0,0,0,0.7)]"
                                ></div>
                            @endif
                        @endif

                        @if ($bannerItem->title || $bannerItem->content)
                            <div
                                class="relative z-20 flex flex-col items-center justify-center space-y-6 px-4 py-20 text-center"
                            >
                                @if ($bannerItem->title)
                                    <h4
                                        @class([
                                            'font-heading text-2xl font-bold md:text-4xl',
                                            'text-white' => $hasImage,
                                            'text-gray-900 dark:text-gray-50' => ! $hasImage,
                                        ])
                                    >
                                        @if ($bannerItem->url)
                                            <a
                                                href="{{ $bannerItem->url }}"
                                                class="hover:underline"
                                            >
                                                {{ $bannerItem->title }}
                                            </a>
                                        @else
                                            {{ $bannerItem->title }}
                                        @endif
                                    </h4>
                                @endif

                                @if ($bannerItem->content)
                                    <div
                                        @class([
                                            'max-w-2xl text-lg md:text-2xl',
                                            'text-white' => $hasImage,
                                            'text-gray-700 dark:text-gray-200' => ! $hasImage,
                                        ])
                                    >
                                        {!! $bannerItem->content !!}
                                    </div>
                                @endif

                                @if ($bannerItem->url && $bannerItem->linkText)
                                    <x-capell::button
                                        :url="$bannerItem->url"
                                        color="primary"
                                        icon="heroicon-o-chevron-right"
                                    >
                                        {{ $bannerItem->linkText }}
                                    </x-capell::button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($total > 1)
                <div
                    class="swiper-controls inline-flex rounded-full bg-white/85 px-3 py-2 shadow-sm backdrop-blur"
                    data-carousel-controls="{{ $carouselId }}"
                >
                    <div
                        class="swiper-pagination flex justify-center"
                        wire:ignore
                    ></div>
                </div>
            @endif
        </div>
    </section>
@endif
