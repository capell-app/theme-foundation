<section
    class="theme-proof border-b border-slate-200/80 bg-slate-950 text-white"
>
    <div
        class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:py-16"
    >
        <div>
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-amber-200 uppercase"
            >
                {{ __('capell-theme-foundation::generic.proof') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-white sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>
            @if ($section->summary)
                <p class="mt-3 text-sm leading-7 text-slate-300 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($section->items as $item)
                <figure
                    class="overflow-hidden rounded-[var(--theme-radius-value)] border border-white/10 bg-white/[0.04]"
                >
                    @if (! empty($item['image']))
                        <img
                            src="{{ $item['image'] }}"
                            alt="{{ $item['image_alt'] ?? $item['title'] ?? $item['name'] ?? '' }}"
                            width="800"
                            height="320"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[5/2] w-full object-cover opacity-90"
                        />
                    @endif

                    <div class="p-5">
                        @if (! empty($item['metric']))
                            <p
                                class="mb-3 text-xs font-semibold tracking-[0.12em] text-amber-200 uppercase"
                            >
                                {{ $item['metric'] }}
                            </p>
                        @endif

                        <blockquote class="text-sm leading-7 text-slate-200">
                            {{ $item['quote'] ?? $item['summary'] ?? '' }}
                        </blockquote>
                        <figcaption
                            class="mt-4 text-sm font-semibold text-white"
                        >
                            {{ $item['title'] ?? $item['name'] ?? $item['logo'] ?? '' }}
                        </figcaption>
                    </div>
                </figure>
            @endforeach
        </div>
    </div>
</section>
