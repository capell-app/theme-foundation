{{--
    "masonry-safe" intentionally does NOT use CSS `masonry` (still an
    enhancement-only layout mode per docs' CSS-masonry guardrail — no
    fallback-free reliance on it). Instead this is a deterministic CSS
    grid with `grid-auto-rows` + `span` classes keyed off the loop index,
    which reads visually similar to masonry while staying a fully
    supported, gap-safe CSS Grid layout in every browser.
--}}
<section
    class="theme-content-listing theme-content-listing--masonry-safe border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
        >
            <div>
                <p
                    class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
                >
                    {{ __('capell-theme-foundation::generic.content') }}
                </p>
                <h2
                    class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
            </div>
            @if ($section->summary)
                <p
                    class="max-w-2xl text-sm leading-7 text-slate-600 sm:text-base"
                >
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <div
            class="grid auto-rows-[0.5rem] gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            @php
                $masonrySafeRowSpanClasses = ['row-span-28', 'row-span-34', 'row-span-22'];
                $masonrySafeImageHeights = [600, 720, 460];
            @endphp

            @foreach ($section->items as $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    class="group widget {{ $masonrySafeRowSpanClasses[$loop->index % 3] }} overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white transition hover:border-slate-950"
                >
                    @if (! empty($item['image']))
                        <img
                            src="{{ $item['image'] }}"
                            alt=""
                            width="900"
                            height="{{ $masonrySafeImageHeights[$loop->index % 3] }}"
                            loading="lazy"
                            decoding="async"
                            sizes="(min-width: 1024px) 31vw, (min-width: 640px) 46vw, 92vw"
                            class="h-2/3 w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        />
                    @endif

                    <span class="widget block p-5">
                        <span class="text-xs font-medium text-slate-500">
                            {{ $item['type'] ?? $item['publishedDate'] ?? '' }}
                        </span>
                        <span
                            class="widget mt-2 block text-base font-semibold text-slate-950"
                        >
                            {{ $item['title'] }}
                        </span>
                        @if (! empty($item['summary']))
                            <span
                                class="widget mt-2 block text-sm leading-6 text-slate-600"
                            >
                                {{ $item['summary'] }}
                            </span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
