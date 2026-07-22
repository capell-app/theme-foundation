<section
    class="theme-content-listing theme-content-listing--grid border-b border-slate-200/80 bg-[var(--theme-surface)]"
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

        @if (filled(data_get($section, 'resolvedResults.archiveUrl')))
            <a
                href="{{ data_get($section, 'resolvedResults.archiveUrl') }}"
                class="mb-8 inline-flex font-semibold text-[var(--theme-primary)] underline-offset-4 hover:underline"
            >
                {{ __('capell-theme-foundation::results.archive') }}
            </a>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (data_get($section, 'resolvedResults.items', data_get($section, 'items', [])) as $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    class="group widget overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white transition hover:border-slate-950"
                >
                    @if (! empty($item['image']))
                        <img
                            src="{{ $item['image'] }}"
                            alt=""
                            width="900"
                            height="600"
                            loading="lazy"
                            decoding="async"
                            sizes="(min-width: 1024px) 24vw, (min-width: 640px) 46vw, 92vw"
                            class="aspect-[3/2] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        />
                    @endif

                    <span class="widget block p-4">
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
