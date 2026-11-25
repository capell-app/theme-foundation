@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $tiers = array_values(array_map(
        static function (mixed $tier): array {
            $tier = is_array($tier) ? $tier : [];
            $ctaLabel = is_string($tier['ctaLabel'] ?? null) && trim($tier['ctaLabel']) !== '' ? $tier['ctaLabel'] : null;
            $ctaUrl = $ctaLabel === null ? null : PublicUrlSanitizer::sanitize($tier['ctaUrl'] ?? null);

            return [
                'label' => $tier['label'] ?? '',
                'price' => $tier['price'] ?? '',
                'features' => is_array($tier['features'] ?? null) ? array_values($tier['features']) : [],
                'ctaLabel' => $ctaUrl === null ? null : $ctaLabel,
                'ctaUrl' => $ctaUrl,
            ];
        },
        is_array($section->tiers ?? null) ? $section->tiers : [],
    ));
    $tierCount = count($tiers);
    $activeTier = $tiers[0] ?? null;
    $ctaTier = collect($tiers)->first(static fn (array $tier): bool => $tier['ctaUrl'] !== null);
    $activeTierHasCta = ($activeTier['ctaUrl'] ?? null) !== null;
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
    class="theme-pricing-value-spectrum border-b border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-section-bg)]"
    data-pricing-spectrum
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-8 max-w-2xl text-center">
            <p
                class="mb-3 text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]"
            >
                {{ __('capell-theme-foundation::generic.pricing_value_spectrum') }}
            </p>
            <h2
                class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.pricing_value_spectrum') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($tierCount > 0)
            <fieldset
                class="pricing-spectrum-fallback grid gap-2"
                style="--pricing-spectrum-tier-count: {{ $tierCount }}"
            >
                <legend class="sr-only">
                    {{ __('capell-theme-foundation::generic.pricing_tier_legend') }}
                </legend>
                @foreach ($tiers as $index => $tier)
                    <label
                        class="pricing-spectrum-tier-option flex cursor-pointer items-center justify-between rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] px-4 py-2 text-sm has-[:checked]:border-[var(--theme-primary)] has-[:checked]:bg-[var(--theme-primary)]/5"
                    >
                        <span class="flex items-center gap-3">
                            <input
                                type="radio"
                                name="pricing-spectrum-tier"
                                value="{{ $index }}"
                                @checked($index === 0)
                                class="pricing-spectrum-tier-radio"
                            />
                            <span
                                class="font-semibold text-[var(--foundation-heading-fg)]"
                            >
                                {{ $tier['label'] ?? '' }}
                            </span>
                        </span>
                        <span
                            class="font-mono text-[var(--foundation-muted-fg)]"
                        >
                            {{ $tier['price'] ?? '' }}
                        </span>
                    </label>
                @endforeach
            </fieldset>

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
                    class="pricing-spectrum-display mt-6 rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)] p-6 text-center"
                    data-pricing-spectrum-display
                >
                    <p
                        class="pricing-spectrum-tier-label text-sm font-semibold tracking-[0.12em] text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)] uppercase"
                        data-pricing-spectrum-label
                    >
                        {{ $activeTier['label'] ?? '' }}
                    </p>
                    <p
                        class="pricing-spectrum-tier-price mt-2 text-4xl font-bold text-[var(--foundation-heading-fg)]"
                        data-pricing-spectrum-price
                    >
                        {{ $activeTier['price'] ?? '' }}
                    </p>

                    <ul
                        class="pricing-spectrum-tier-features mx-auto mt-6 grid max-w-md gap-2 text-start text-sm text-[var(--foundation-body-fg)]"
                        data-pricing-spectrum-features
                    >
                        @foreach (($activeTier['features'] ?? []) as $feature)
                            <li class="flex items-center gap-2">
                                <span
                                    class="font-bold text-emerald-700 dark:text-emerald-400"
                                    aria-hidden="true"
                                    >&check;</span
                                >
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if ($ctaTier !== null)
                        {{-- Rendered whenever any tier links somewhere, so the slider can reveal it for later tiers. --}}
                        <a
                            @if ($activeTierHasCta)
                                href="{{ $activeTier['ctaUrl'] }}"
                            @else
                                hidden
                            @endif
                            class="pricing-spectrum-cta mt-6 inline-block rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                            data-pricing-spectrum-cta
                        >
                            {{ $activeTierHasCta ? $activeTier['ctaLabel'] : $ctaTier['ctaLabel'] }}
                        </a>
                    @endif
                </div>

                <template data-pricing-spectrum-tier-data>
                    {{ json_encode($tiers, JSON_THROW_ON_ERROR) }}
                </template>
            </div>
        @else
            <p class="text-center text-[var(--foundation-muted-fg)]">
                {{ __('capell-theme-foundation::generic.empty_pricing_plans') }}
            </p>
        @endif
    </div>
</section>
