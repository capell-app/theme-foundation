<section
    class="theme-hero border-b border-[var(--foundation-border)] bg-[var(--foundation-section-bg)]"
>
    <div
        class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:py-20"
    >
        <div class="space-y-5">
            @if ($section->eyebrow)
                <p class="text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase">
                    {{ $section->eyebrow }}
                </p>
            @endif

            <h2
                class="max-w-4xl text-4xl leading-tight font-[var(--theme-heading-font)] font-semibold text-[var(--foundation-heading-fg)] sm:text-5xl lg:text-6xl"
            >
                {{ $section->heading }}
            </h2>

            @if ($section->summary)
                <p class="max-w-2xl text-base leading-8 text-[var(--foundation-muted-fg)] sm:text-lg">{{ $section->summary }}</p>
            @endif

            @if ($section->actions !== [])
                <div class="flex flex-wrap gap-3">
                    @foreach ($section->actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-[var(--foundation-border-strong)] text-[var(--foundation-body-fg)] hover:border-[var(--foundation-primary-action)]' : 'bg-[var(--theme-primary)] text-white hover:opacity-90' }} rounded-full px-5 py-3 text-sm font-semibold transition"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($section->mediaUrl)
            <figure
                class="overflow-hidden rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-[var(--foundation-card-bg)] shadow-sm"
            >
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    width="1200"
                    height="750"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high"
                    sizes="(min-width: 1024px) 50vw, 100vw"
                    class="aspect-[16/10] w-full object-cover"
                />
            </figure>
        @endif
    </div>
</section>
