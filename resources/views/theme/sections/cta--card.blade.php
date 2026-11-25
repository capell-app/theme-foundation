@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
<section
    class="theme-cta theme-cta--card border-b border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)]"
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)] p-8 text-center shadow-sm sm:p-12"
        >
            <h2
                class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>

            @if ($section->summary)
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
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
        </div>
    </div>
</section>
