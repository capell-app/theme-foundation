@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeMediaUrl = PublicUrlSanitizer::sanitize($section->mediaUrl ?? null);
@endphp
<section
    class="theme-hero theme-hero--stacked border-b border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)]"
>
    <div
        class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-4 py-12 text-center sm:px-6 lg:py-20"
    >
        <div class="space-y-5">
            @if ($section->eyebrow)
                <p class="text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]">
                    {{ $section->eyebrow }}
                </p>
            @endif

            <{{ $headingLevel ?? 'h1' }}
                class="mx-auto max-w-3xl text-4xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-5xl lg:text-6xl"
            >
                {{ $section->heading }}
            </{{ $headingLevel ?? 'h1' }}>

            @if ($section->summary)
                <p class="mx-auto max-w-2xl text-base leading-8 text-[var(--foundation-muted-fg)] sm:text-lg">{{ $section->summary }}</p>
            @endif

            @if ($section->actions !== [])
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach ($section->actions as $action)
                        @php
                            $safeActionUrl = PublicUrlSanitizer::sanitize($action['url'] ?? null);
                        @endphp

                        @continue($safeActionUrl === null)

                        <a
                            href="{{ $safeActionUrl }}"
                            class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-[var(--foundation-border)] text-[var(--foundation-body-fg)] hover:border-[var(--foundation-heading-fg)]' : 'bg-[var(--theme-primary)] text-white hover:opacity-90' }} rounded-full px-5 py-3 text-sm font-semibold transition"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($safeMediaUrl)
            <figure
                class="w-full overflow-hidden rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)] shadow-sm"
            >
                <x-capell::image-source
                    :image="$safeMediaUrl"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    width="1200"
                    height="675"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high"
                    sizes="(min-width: 1024px) 60vw, 100vw"
                    class="aspect-[16/9] w-full object-cover"
                />
            </figure>
        @endif
    </div>
</section>
