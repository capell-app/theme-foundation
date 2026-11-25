@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeArchiveUrl = PublicUrlSanitizer::sanitize(data_get($section, 'resolvedResults.archiveUrl'));
@endphp
<section
    class="theme-content-listing theme-content-listing--grid border-b border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)]"
>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
        >
            <div>
                <p class="mb-3 text-base font-semibold text-[var(--foundation-muted-fg)]">
                    {{ __('capell-theme-foundation::generic.content') }}
                </p>
                <h2
                    class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
            </div>
            @if ($section->summary)
                <p class="max-w-2xl text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">{{ $section->summary }}</p>
            @endif
        </div>

        @if (filled($safeArchiveUrl))
            <a
                href="{{ $safeArchiveUrl }}"
                class="mb-8 inline-flex min-h-11 items-center font-semibold text-[var(--foundation-body-fg)] underline underline-offset-4"
            >
                {{ __('capell-theme-foundation::results.archive') }}
            </a>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (data_get($section, 'resolvedResults.items', data_get($section, 'items', [])) as $item)
                @php
                    $safeItemUrl = PublicUrlSanitizer::sanitize($item['url'] ?? null);
                    $safeItemImage = PublicUrlSanitizer::sanitize($item['image'] ?? null);
                @endphp
                <{{ $safeItemUrl ? 'a' : 'span' }}
                    @if ($safeItemUrl) href="{{ $safeItemUrl }}" @endif
                    class="group widget overflow-hidden rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)] transition hover:border-[var(--foundation-heading-fg)]"
                >
                    @if (! empty($safeItemImage))
                        <x-capell::image-source
                            :image="$safeItemImage"
                            alt=""
                            width="900"
                            height="600"
                            loading="{{ $secondaryImageLoading ?? 'lazy' }}"
                            decoding="async"
                            sizes="(min-width: 1024px) 24vw, (min-width: 640px) 46vw, 92vw"
                            class="aspect-[3/2] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        />
                    @endif

                    <span class="widget block p-4">
                        <span
                            class="text-xs font-medium text-[var(--foundation-muted-fg)]"
                        >
                            {{ $item['type'] ?? $item['publishedDate'] ?? '' }}
                        </span>
                        <span
                            class="widget mt-2 block text-base font-semibold text-[var(--foundation-heading-fg)]"
                        >
                            {{ $item['title'] }}
                        </span>
                        @if (! empty($item['summary']))
                            <span
                                class="widget mt-2 block text-sm leading-6 text-[var(--foundation-muted-fg)]"
                            >
                                {{ $item['summary'] }}
                            </span>
                        @endif
                    </span>
                </{{ $safeItemUrl ? 'a' : 'span' }}>
            @endforeach
        </div>
        @include('capell-theme-foundation::theme.sections.results-pagination', ['section' => $section])
    </div>
</section>
