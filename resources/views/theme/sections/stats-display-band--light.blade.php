@php
    $stats = is_array($section->stats ?? null) ? $section->stats : [];
@endphp

{{--
    Light variant: same count-up-stat primitive, on a light surface for
    placement inside content-heavy pages rather than as a bold dark band.
--}}
<section
    class="theme-stats-display-band theme-stats-display-band--light border-b border-slate-200/80 bg-white"
>
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-10 max-w-2xl text-center">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.stats_display_band') }}
            </p>
            @if (! empty($section->heading))
                <h2
                    class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
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
                        class="count-up-stat min-w-[8rem] text-slate-950"
                    />
                @endforeach
            </div>
        @else
            <p class="text-center text-slate-500">
                {{ __('capell-theme-foundation::generic.empty_stats') }}
            </p>
        @endif
    </div>
</section>
