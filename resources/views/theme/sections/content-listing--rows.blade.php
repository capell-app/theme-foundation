<section
    class="theme-content-listing theme-content-listing--rows border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8">
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
            @if ($section->summary)
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
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

        <div class="divide-y divide-slate-200/80">
            @foreach (data_get($section, 'resolvedResults.items', data_get($section, 'items', [])) as $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    class="group widget flex items-center gap-5 py-5 transition first:pt-0 last:pb-0"
                >
                    @if (! empty($item['image']))
                        <img
                            src="{{ $item['image'] }}"
                            alt=""
                            width="160"
                            height="120"
                            loading="lazy"
                            decoding="async"
                            sizes="160px"
                            class="aspect-[4/3] w-28 shrink-0 rounded-[var(--theme-radius-value)] object-cover sm:w-40"
                        />
                    @endif

                    <span class="widget min-w-0 flex-1">
                        <span class="text-xs font-medium text-slate-500">
                            {{ $item['type'] ?? $item['publishedDate'] ?? '' }}
                        </span>
                        <span
                            class="widget mt-1 block text-lg font-semibold text-slate-950 group-hover:underline"
                        >
                            {{ $item['title'] }}
                        </span>
                        @if (! empty($item['summary']))
                            <span
                                class="widget mt-1 block text-sm leading-6 text-slate-600"
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
