@php
    $stats = is_array($section->stats ?? null) ? $section->stats : [];
@endphp

{{--
    Light variant: same count-up-stat primitive, on a light surface for
    placement inside content-heavy pages rather than as a bold dark band.
--}}
<section
    class="theme-stats-display-band theme-stats-display-band--light border-b border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-section-bg)]"
>
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-10 max-w-2xl text-center">
            <p class="mb-3 text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]">
                {{ __('capell-theme-foundation::generic.stats_display_band') }}
            </p>
            @if (! empty($section->heading))
                <h2
                    class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
            @endif
        </div>

        @if (count($stats) > 0)
            <div class="flex flex-wrap justify-center gap-8">
                @foreach ($stats as $stat)
                    <x-capell-theme-foundation::display.count-up-stat
                        :stat-value="$stat['value'] ?? 0"
                        :label="$stat['label'] ?? ''"
                        :prefix="$stat['prefix'] ?? ''"
                        :suffix="$stat['suffix'] ?? ''"
                        :decimals="$stat['decimals'] ?? 0"
                        class="count-up-stat min-w-[8rem] text-[var(--foundation-heading-fg)]"
                    />
                @endforeach
            </div>
        @else
            <p class="text-center text-[var(--foundation-muted-fg)]">{{ __('capell-theme-foundation::generic.empty_stats') }}</p>
        @endif
    </div>
</section>
