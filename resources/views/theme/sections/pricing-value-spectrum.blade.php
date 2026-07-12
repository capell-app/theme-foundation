@php
    $tiers = is_array($section->tiers ?? null) ? $section->tiers : [];
    $tierCount = count($tiers);
    $activeTier = $tiers[0] ?? null;
@endphp

{{--
    Wave 4c §D: range slider unlocks pricing tiers progressively as the
    visitor drags — the display only reads the payload-driven `tiers` list
    (each carrying label/price/features), never a live pricing service.
    Discrete-tier fallback is pure CSS via `:has(:checked)` on the radio
    group beneath the slider, so browsers without full range-input support
    (or JS disabled) still land on a legible, selectable tier picker.
--}}
<section
    class="theme-pricing-value-spectrum border-b border-slate-200/80 bg-white"
    data-pricing-spectrum
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-8 max-w-2xl text-center">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.pricing_value_spectrum') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.pricing_value_spectrum') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($tierCount > 0)
            <div
                class="pricing-spectrum-fallback grid gap-2"
                style="--pricing-spectrum-tier-count: {{ $tierCount }}"
            >
                @foreach ($tiers as $index => $tier)
                    <label
                        class="pricing-spectrum-tier-option flex cursor-pointer items-center justify-between rounded-[var(--theme-radius-value)] border border-slate-200 px-4 py-2 text-sm has-[:checked]:border-[var(--theme-primary)] has-[:checked]:bg-[var(--theme-primary)]/5"
                    >
                        <span class="flex items-center gap-3">
                            <input
                                type="radio"
                                name="pricing-spectrum-tier"
                                value="{{ $index }}"
                                @checked ($index === 0)
                                class="pricing-spectrum-tier-radio"
                            />
                            <span class="font-semibold text-slate-900">
                                {{ $tier['label'] ?? '' }}
                            </span>
                        </span>
                        <span class="font-mono text-slate-600">
                            {{ $tier['price'] ?? '' }}
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="pricing-spectrum-slider-panel mt-8">
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
                    class="pricing-spectrum-display mt-6 rounded-[var(--theme-radius-value)] border border-slate-200 bg-[var(--theme-surface)] p-6 text-center"
                    data-pricing-spectrum-display
                >
                    <p
                        class="pricing-spectrum-tier-label text-sm font-semibold tracking-[0.12em] text-[var(--theme-primary)] uppercase"
                        data-pricing-spectrum-label
                    >
                        {{ $activeTier['label'] ?? '' }}
                    </p>
                    <p
                        class="pricing-spectrum-tier-price mt-2 text-4xl font-bold text-slate-950"
                        data-pricing-spectrum-price
                    >
                        {{ $activeTier['price'] ?? '' }}
                    </p>

                    <ul
                        class="pricing-spectrum-tier-features mx-auto mt-6 grid max-w-md gap-2 text-left text-sm text-slate-700"
                        data-pricing-spectrum-features
                    >
                        @foreach (($activeTier['features'] ?? []) as $feature)
                            <li class="flex items-center gap-2">
                                <span
                                    class="font-bold text-emerald-600"
                                    aria-hidden="true"
                                    >&check;</span
                                >
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if (! empty($activeTier['ctaLabel']))
                        <a
                            href="{{ $activeTier['ctaUrl'] ?? '#' }}"
                            class="pricing-spectrum-cta mt-6 inline-block rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                            data-pricing-spectrum-cta
                        >
                            {{ $activeTier['ctaLabel'] }}
                        </a>
                    @endif
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
