@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
<section
    class="theme-cta theme-cta--inline border-b border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-section-bg)]"
>
    <div
        class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:py-10"
    >
        <div class="min-w-0">
            <h2
                class="text-xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-2xl"
            >
                {{ $section->heading }}
            </h2>

            @if ($section->summary)
                <p class="mt-1 text-sm leading-6 text-[var(--foundation-muted-fg)]">{{ $section->summary }}</p>
            @endif
        </div>

        <div class="flex shrink-0 flex-wrap gap-3">
            @foreach ($section->actions as $action)
                @php
                    $safeActionUrl = PublicUrlSanitizer::sanitize($action['url'] ?? null);
                @endphp

                @continue($safeActionUrl === null)

                <a
                    href="{{ $safeActionUrl }}"
                    class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-[var(--foundation-border)] text-[var(--foundation-body-fg)] hover:border-[var(--foundation-heading-fg)]' : 'bg-[var(--theme-primary)] text-white hover:opacity-90' }} rounded-full px-5 py-2.5 text-sm font-semibold transition"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
