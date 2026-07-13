@props ([
    'statValue' => null,
    'label' => null,
    'duration' => 1200,
    'locale' => null,
    'numberStyle' => 'decimal',
    'currency' => null,
    'prefix' => '',
    'suffix' => '',
    'decimals' => 0,
])

@php
    $resolvedValue = $statValue ?? $attributes->get('value', 0);
    $resolvedLabel = $label ?? $attributes->get('label', '');
    $attributes = $attributes->except(['value', 'label']);
    $decimalPlaces = max(0, (int) $decimals);
    $numericValue = is_numeric($resolvedValue) ? (float) $resolvedValue : null;
    $displayValue = $numericValue === null
        ? (string) $resolvedValue
        : number_format($numericValue, $decimalPlaces);
@endphp

{{--
    Shared Foundation display primitive (Wave 2.7): renders a stat number
    plus label and wires the `data-count-up*` attribute contract expected
    by `count-up.js` (Wave 2.6) — see that module's doc block for the full
    attribute list. Payload-driven only; the JS module (opt-in, imported by
    the theme) does the animation, this component only emits the markup.
--}}

<div
    {{ $attributes->merge(['class' => 'count-up-stat flex flex-col items-center text-center']) }}
>
    <span
        class="text-4xl font-bold"
        style="color: var(--foundation-body-fg)"
        data-count-up="{{ $resolvedValue }}"
        data-count-up-duration="{{ $duration }}"
        @if ($locale) data-count-up-locale="{{ $locale }}" @endif
        data-count-up-style="{{ $numberStyle }}"
        @if ($currency) data-count-up-currency="{{ $currency }}" @endif
        @if ($prefix !== '') data-count-up-prefix="{{ $prefix }}" @endif
        @if ($suffix !== '') data-count-up-suffix="{{ $suffix }}" @endif
        data-count-up-decimals="{{ $decimals }}"
        >{{ $prefix }}{{ $displayValue }}{{ $suffix }}</span
    >

    <span
        class="mt-2 text-sm"
        style="color: var(--foundation-border-strong)"
    >
        {{ $resolvedLabel }}
    </span>
</div>
