@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
<section
    class="theme-features border-b border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-section-bg)]"
>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8 max-w-3xl">
            <p class="mb-3 text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]">
                {{ __('capell-theme-foundation::generic.features') }}
            </p>
            <h2
                class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>
            @if ($section->summary)
                <p class="mt-3 text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">{{ $section->summary }}</p>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($section->features as $feature)
                @php
                    $safeFeatureImage = PublicUrlSanitizer::sanitize($feature['image'] ?? null);
                @endphp
                <article
                    class="overflow-hidden rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)]"
                >
                    @if (! empty($safeFeatureImage))
                        <x-capell::image-source
                            :image="$safeFeatureImage"
                            alt="{{ $feature['image_alt'] ?? $feature['title'] }}"
                            width="800"
                            height="480"
                            loading="{{ $secondaryImageLoading ?? 'lazy' }}"
                            decoding="async"
                            class="aspect-[5/3] w-full object-cover"
                        />
                    @endif

                    <div class="p-5">
                        @if (! empty($feature['type']))
                            <p class="mb-3 text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)] uppercase">
                                {{ $feature['type'] }}
                            </p>
                        @endif

                        <h3
                            class="text-base font-semibold text-[var(--foundation-heading-fg)]"
                        >
                            {{ $feature['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--foundation-muted-fg)]">
                            {{ $feature['description'] ?? $feature['summary'] ?? '' }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
