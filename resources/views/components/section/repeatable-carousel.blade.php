@props ([
    'breakpoints' => '{"320":{"slidesPerView":1.1,"spaceBetween":16},"520":{"slidesPerView":2,"spaceBetween":20},"760":{"slidesPerView":3,"spaceBetween":24}}',
    'carouselId',
    'enabled' => true,
    'pagination' => true,
    'trackClass' => '',
])

@if ($enabled)
    <div
        class="theme-repeatable-carousel swiper"
        data-carousel="1"
        data-carousel-align="start"
        data-carousel-breakpoints="{{ $breakpoints }}"
        data-carousel-breakpoints-base="container"
        data-carousel-drag="1"
        data-carousel-effect="slide"
        data-carousel-id="{{ $carouselId }}"
        data-carousel-loop="0"
        data-carousel-pagination="{{ (int) $pagination }}"
        data-carousel-rewind="1"
        data-carousel-speed="300"
        data-carousel-touch="1"
        data-carousel-watch-overflow="1"
    >
        <div {{ $attributes->class(['swiper-wrapper', $trackClass]) }}>
            {{ $slot }}
        </div>

        @if ($pagination)
            <div
                class="swiper-controls mt-5 flex justify-center"
                data-carousel-controls="{{ $carouselId }}"
            >
                <div
                    class="swiper-pagination pointer-events-auto flex justify-center"
                ></div>
            </div>
        @endif
    </div>
@else
    <div {{ $attributes->class($trackClass) }}>{{ $slot }}</div>
@endif
