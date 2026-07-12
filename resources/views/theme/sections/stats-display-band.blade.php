@php
    $stats = is_array($section->stats ?? null) ? $section->stats : [];
@endphp

{{--
    Wave 4c §D: reuses the shared Wave 2.7 `count-up-stat` display primitive
    (see `resources/views/components/display/count-up-stat.blade.php`)
    rather than reinventing a stat counter. The `count-up.js` module (Wave
    2.6, already imported by the runtime) does the animation purely off the
    `data-count-up*` attribute contract the primitive emits.
--}}
<section
    class="theme-stats-display-band border-b border-slate-200/80 bg-slate-950"
>
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-10 max-w-2xl text-center">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-amber-200 uppercase"
            >
                {{ __('capell-theme-foundation::generic.stats_display_band') }}
            </p>
            @if (! empty($section->heading))
                <h2
                    class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-white sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
            @endif
        </div>

        @if (count($stats) > 0)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <x-capell-theme-foundation::display.count-up-stat
                        :value="$stat['value'] ?? 0"
                        :label="$stat['label'] ?? ''"
                        :prefix="$stat['prefix'] ?? ''"
                        :suffix="$stat['suffix'] ?? ''"
                        :decimals="$stat['decimals'] ?? 0"
                        class="count-up-stat text-white"
                    />
                @endforeach
            </div>
        @else
            <p class="text-center text-slate-300">
                {{ __('capell-theme-foundation::generic.empty_stats') }}
            </p>
        @endif
    </div>
</section>
