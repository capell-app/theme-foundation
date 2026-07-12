@php
    $tiers = is_array($section->tiers ?? null) ? $section->tiers : [];
    $tierCount = count($tiers);
    $activeTier = $tiers[0] ?? null;
@endphp

{{--
    Compact variant: same slider-driven progressive-unlock mechanic as the
    base view, condensed into a single-row banner layout for placement
    beneath a hero rather than as a full standalone section.
--}}
<section
    class="theme-pricing-value-spectrum theme-pricing-value-spectrum--compact border-b border-slate-200/80 bg-[var(--theme-surface)]"
    data-pricing-spectrum
>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @if ($tierCount > 0)
            <div
                class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex-1">
                    <p
                        class="mb-2 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
                    >
                        {{ $section->heading ?? __('capell-theme-foundation::generic.pricing_value_spectrum') }}
                    </p>

                    <input
                        type="range"
                        min="0"
                        max="{{ max($tierCount - 1, 0) }}"
                        value="0"
                        step="1"
                        class="pricing-spectrum-slider w-full"
                        aria-label="{{ __('capell-theme-foundation::generic.pricing_value_spectrum') }}"
                        data-pricing-spectrum-slider
                        data-pricing-spectrum-tier-count="{{ $tierCount }}"
                    />

                    <div
                        class="pricing-spectrum-fallback mt-3 flex flex-wrap gap-2"
                    >
                        @foreach ($tiers as $index => $tier)
                            <label
                                class="pricing-spectrum-tier-option cursor-pointer rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 has-[:checked]:border-[var(--theme-primary)] has-[:checked]:bg-[var(--theme-primary)] has-[:checked]:text-white"
                            >
                                <input
                                    type="radio"
                                    name="pricing-spectrum-tier-compact"
                                    value="{{ $index }}"
                                    @checked ($index === 0)
                                    class="pricing-spectrum-tier-radio sr-only"
                                />
                                {{ $tier['label'] ?? '' }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div
                    class="pricing-spectrum-display shrink-0 rounded-[var(--theme-radius-value)] border border-slate-200 bg-white px-6 py-4 text-center"
                    data-pricing-spectrum-display
                >
                    <p
                        class="pricing-spectrum-tier-label text-xs font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase"
                        data-pricing-spectrum-label
                    >
                        {{ $activeTier['label'] ?? '' }}
                    </p>
                    <p
                        class="pricing-spectrum-tier-price mt-1 text-3xl font-bold text-slate-950"
                        data-pricing-spectrum-price
                    >
                        {{ $activeTier['price'] ?? '' }}
                    </p>
                </div>

                <template data-pricing-spectrum-tier-data>
                    {{ json_encode($tiers, JSON_THROW_ON_ERROR) }}
                </template>
            </div>
        @else
            <p class="text-center text-slate-500">
                {{ __('capell-theme-foundation::generic.empty_pricing_plans') }}
            </p>
        @endif
    </div>
</section>
