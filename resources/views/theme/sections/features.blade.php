<section class="theme-features border-b border-slate-200/80 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8 max-w-3xl">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.features') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>
            @if ($section->summary)
                <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($section->features as $feature)
                <article
                    class="overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-[var(--theme-surface)]"
                >
                    @if (! empty($feature['image']))
                        <img
                            src="{{ $feature['image'] }}"
                            alt="{{ $feature['image_alt'] ?? $feature['title'] }}"
                            width="800"
                            height="480"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[5/3] w-full object-cover"
                        />
                    @endif

                    <div class="p-5">
                        @if (! empty($feature['type']))
                            <p
                                class="mb-3 text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase"
                            >
                                {{ $feature['type'] }}
                            </p>
                        @endif

                        <h3 class="text-base font-semibold text-slate-950">
                            {{ $feature['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $feature['description'] ?? $feature['summary'] ?? '' }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
