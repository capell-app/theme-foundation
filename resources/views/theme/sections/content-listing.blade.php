@php
    $isGallery = ($section->variant ?? null) === 'gallery';
    $isPathways = ($section->variant ?? null) === 'pathways';
    $isSpotlight = ($section->variant ?? null) === 'spotlight';
    $sectionId = $isGallery ? 'gallery' : ($isSpotlight ? 'spotlight' : 'content');
    $sectionHash = substr(hash('xxh128', (string) $section->heading), 0, 10);
    $carouselId = 'theme-gallery-' . $sectionHash;
    $spotlightId = 'theme-spotlight-' . $sectionHash;
    $responsiveImageSrcset = static function (string $url): ?string {
        if (! str_contains($url, 'images.unsplash.com')) {
            return null;
        }

        return collect([480, 800, 1200, 1600])
            ->map(static function (int $width) use ($url): string {
                $imageUrl = preg_replace('/([?&])w=\d+/', '$1w=' . $width, $url);
                $imageUrl ??= $url . (str_contains($url, '?') ? '&' : '?') . 'w=' . $width;

                return $imageUrl . ' ' . $width . 'w';
            })
            ->implode(', ');
    };
@endphp

<section
    id="{{ $sectionId }}"
    class="theme-content-listing border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <style>
        .theme-content-listing {
            --theme-content-accent: color-mix(
                in srgb,
                var(--theme-primary, #2563eb) 54%,
                var(--theme-foreground, #0f172a)
            );
            --theme-content-heading: var(--theme-foreground, #0f172a);
            --theme-content-muted: color-mix(
                in srgb,
                var(--theme-foreground, #0f172a) 72%,
                var(--theme-surface, #ffffff)
            );
        }

        .theme-content-listing [data-theme-section-label] {
            color: var(--theme-content-accent);
        }

        .theme-content-listing [data-theme-section-heading] {
            color: var(--theme-content-heading);
        }

        .theme-content-listing [data-theme-section-summary] {
            color: var(--theme-content-muted);
        }

        .theme-content-listing [data-theme-pathway-badge] {
            background: var(--theme-primary, #2563eb);
            color: #fff;
        }
    </style>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
        >
            <div>
                <p
                    class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
                    data-theme-section-label
                >
                    @if ($isGallery)
                        {{ __('capell-theme-foundation::generic.gallery') }}
                    @elseif ($isSpotlight)
                        {{ __('capell-theme-foundation::generic.spotlight') }}
                    @elseif ($isPathways)
                        {{ __('capell-theme-foundation::generic.pathways') }}
                    @else
                        {{ ucfirst($section->variant ?? __('capell-theme-foundation::generic.content')) }}
                    @endif
                </p>
                <h2
                    class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
                    data-theme-section-heading
                >
                    {{ $section->heading }}
                </h2>
            </div>
            @if ($section->summary)
                <p
                    class="max-w-2xl text-sm leading-7 text-slate-600 sm:text-base"
                    data-theme-section-summary
                >
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($isSpotlight)
            <div
                class="theme-content-spotlight grid gap-6 lg:grid-cols-[0.42fr_1fr] lg:items-start"
                data-theme-spotlight
            >
                <style>
                    .theme-content-spotlight
                        [data-spotlight-tab][data-active='true'] {
                        background: #fff;
                        border-color: #0f172a;
                        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.1);
                        color: #0f172a;
                    }

                    .theme-content-spotlight
                        [data-spotlight-tab][data-active='false'] {
                        background: color-mix(
                            in srgb,
                            var(--theme-surface) 88%,
                            white
                        );
                        color: var(--theme-content-muted);
                    }
                </style>

                <div
                    class="flex gap-3 overflow-x-auto pb-2 lg:grid lg:overflow-visible lg:pb-0"
                    role="tablist"
                    aria-label="{{ $section->heading }}"
                >
                    @foreach ($section->items as $item)
                        <button
                            type="button"
                            id="{{ $spotlightId }}-tab-{{ $loop->index }}"
                            class="min-w-[17rem] rounded-[var(--theme-radius-value)] border border-slate-200 p-4 text-start transition hover:border-slate-950 focus:ring-2 focus:ring-[var(--theme-primary)] focus:ring-offset-2 focus:outline-none lg:min-w-0 lg:p-5"
                            role="tab"
                            aria-controls="{{ $spotlightId }}-panel-{{ $loop->index }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            data-active="{{ $loop->first ? 'true' : 'false' }}"
                            data-spotlight-index="{{ $loop->index }}"
                            data-spotlight-tab
                            @if (! $loop->first) tabindex="-1" @endif
                        >
                            <span
                                class="mb-3 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase ring-1 ring-slate-200"
                            >
                                {{ $item['type'] ?? str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="widget text-lg font-semibold">
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span class="widget mt-2 text-sm leading-6">
                                    {{ $item['summary'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="min-w-0">
                    @foreach ($section->items as $item)
                        <div
                            id="{{ $spotlightId }}-panel-{{ $loop->index }}"
                            class="overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white shadow-xl shadow-slate-950/8"
                            role="tabpanel"
                            aria-labelledby="{{ $spotlightId }}-tab-{{ $loop->index }}"
                            data-spotlight-index="{{ $loop->index }}"
                            data-spotlight-panel
                            @if (! $loop->first) hidden @endif
                        >
                            @if (! empty($item['image']))
                                <img
                                    src="{{ $item['image'] }}"
                                    @if ($responsiveImageSrcset((string) $item['image']) !== null) srcset="{{ $responsiveImageSrcset((string) $item['image']) }}" @endif
                                    alt=""
                                    width="1200"
                                    height="675"
                                    loading="lazy"
                                    decoding="async"
                                    sizes="(min-width: 1024px) 48vw, 92vw"
                                    class="aspect-[16/9] w-full object-cover"
                                />
                            @endif

                            <div class="grid gap-4 p-6 sm:p-8">
                                <p
                                    class="text-xs font-semibold tracking-[0.14em] text-[var(--theme-primary)] uppercase"
                                >
                                    {{ $item['type'] ?? __('capell-theme-foundation::generic.spotlight') }}
                                </p>
                                <h3
                                    class="text-2xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-3xl"
                                >
                                    {{ $item['title'] }}
                                </h3>
                                @if (! empty($item['summary']))
                                    <p
                                        class="max-w-2xl text-base leading-7 text-slate-600"
                                    >
                                        {{ $item['summary'] }}
                                    </p>
                                @endif

                                @if (! empty($item['url']))
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="inline-flex w-fit items-center rounded-full bg-[var(--theme-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                    >
                                        {{ __('capell-theme-foundation::generic.view_preview') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif ($isGallery)
            <div
                class="theme-content-gallery"
                data-carousel-scope
            >
                <style>
                    .theme-content-gallery .swiper-controls .swiper-button-prev,
                    .theme-content-gallery
                        .swiper-controls
                        .swiper-button-next {
                        bottom: auto;
                        color: #0f172a;
                        height: 2.5rem;
                        left: auto;
                        margin: 0;
                        position: relative;
                        right: auto;
                        top: auto;
                        transform: none;
                        width: 2.5rem;
                    }

                    .theme-content-gallery
                        .swiper-controls
                        .swiper-button-prev::after,
                    .theme-content-gallery
                        .swiper-controls
                        .swiper-button-next::after {
                        content: none;
                    }

                    .theme-content-gallery .swiper-controls .swiper-pagination {
                        bottom: auto;
                        left: auto;
                        position: relative;
                        text-align: left;
                        top: auto;
                        transform: none;
                        width: auto;
                    }
                </style>
                <div
                    class="swiper overflow-visible"
                    data-carousel-id="{{ $carouselId }}"
                    data-carousel-autoplay="true"
                    data-carousel-autoplay-delay="4200"
                    data-carousel-align="start"
                    data-carousel-loop="false"
                    data-carousel-pagination="true"
                    data-carousel-navigation="true"
                    data-carousel-per-view="1"
                    data-carousel-breakpoints='{"760":{"slidesPerView":2},"1080":{"slidesPerView":3}}'
                >
                    <div class="swiper-wrapper">
                        @foreach ($section->items as $item)
                            <article class="swiper-slide h-auto pe-4">
                                <a
                                    href="{{ $item['url'] ?? '#' }}"
                                    class="group grid h-full overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-950 hover:shadow-xl"
                                >
                                    @if (! empty($item['image']))
                                        <img
                                            src="{{ $item['image'] }}"
                                            @if ($responsiveImageSrcset((string) $item['image']) !== null) srcset="{{ $responsiveImageSrcset((string) $item['image']) }}" @endif
                                            alt=""
                                            width="1200"
                                            height="900"
                                            loading="lazy"
                                            decoding="async"
                                            sizes="(min-width: 1080px) 31vw, (min-width: 760px) 46vw, 92vw"
                                            class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                        />
                                    @endif

                                    <span class="grid content-start p-5">
                                        <span
                                            class="text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase"
                                        >
                                            {{ $item['type'] ?? $item['publishedDate'] ?? '' }}
                                        </span>
                                        <span
                                            class="widget mt-3 text-lg font-semibold text-slate-950"
                                        >
                                            {{ $item['title'] }}
                                        </span>
                                        @if (! empty($item['summary']))
                                            <span
                                                class="widget mt-2 text-sm leading-6 text-slate-600"
                                            >
                                                {{ $item['summary'] }}
                                            </span>
                                        @endif
                                    </span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div
                    class="swiper-controls mt-6 flex items-center justify-between gap-4"
                    data-carousel-controls="{{ $carouselId }}"
                >
                    <div class="swiper-pagination flex justify-start"></div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="swiper-button-prev relative inset-auto m-0 flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-950 after:hidden hover:border-slate-950"
                            aria-label="{{ __('capell-theme-foundation::generic.previous') }}"
                        >
                            <svg
                                aria-hidden="true"
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <path
                                    d="M12.5 4.5 7 10l5.5 5.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="swiper-button-next relative inset-auto m-0 flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-950 after:hidden hover:border-slate-950"
                            aria-label="{{ __('capell-theme-foundation::generic.next') }}"
                        >
                            <svg
                                aria-hidden="true"
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <path
                                    d="M7.5 4.5 13 10l-5.5 5.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @elseif ($isPathways)
            <div
                class="grid gap-3 lg:grid-cols-2"
                data-theme-pathways
            >
                @foreach ($section->items as $item)
                    <details
                        class="group rounded-[var(--theme-radius-value)] border border-slate-200 bg-white p-5 shadow-sm transition open:border-slate-950 open:shadow-lg"
                        data-active="{{ $loop->first ? 'true' : 'false' }}"
                        data-pathway-panel
                        @if ($loop->first) open @endif
                    >
                        <summary
                            class="flex cursor-pointer list-none items-start justify-between gap-4"
                        >
                            <span>
                                <span
                                    class="mb-3 inline-flex rounded-full bg-[var(--theme-surface)] px-3 py-1 text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase"
                                    data-theme-pathway-badge
                                >
                                    {{ $item['type'] ?? str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span
                                    class="widget text-lg font-semibold text-slate-950"
                                >
                                    {{ $item['title'] }}
                                </span>
                            </span>
                            <span
                                class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition group-open:rotate-45 group-open:border-slate-950 group-open:text-slate-950"
                            >
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                >
                                    <path
                                        d="M10 4v12M4 10h12"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>
                        </summary>
                        @if (! empty($item['summary']))
                            <p
                                class="mt-4 max-w-2xl text-sm leading-7 text-slate-600"
                            >
                                {{ $item['summary'] }}
                            </p>
                        @endif
                    </details>
                @endforeach
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="group widget overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white transition hover:border-slate-950"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                @if ($responsiveImageSrcset((string) $item['image']) !== null) srcset="{{ $responsiveImageSrcset((string) $item['image']) }}" @endif
                                alt=""
                                width="1200"
                                height="720"
                                loading="lazy"
                                decoding="async"
                                sizes="(min-width: 768px) 31vw, 92vw"
                                class="aspect-[5/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                            />
                        @endif

                        <span class="widget p-5">
                            <span class="text-xs font-medium text-slate-500">
                                {{ $item['type'] ?? $item['publishedDate'] ?? '' }}
                            </span>
                            <span
                                class="widget mt-2 text-base font-semibold text-slate-950"
                            >
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="widget mt-2 text-sm leading-6 text-slate-600"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
