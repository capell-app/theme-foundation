@props([
    'variant' => 'bordered',
    'hoverEffect' => 'none',
])

{{--
    Shared Foundation display primitive (Wave 2.7): wraps arbitrary card
    content with a `variant` (`bordered|elevated|flat`) and a `hoverEffect`,
    both resolved to token-driven classes only — every colour/border/shadow
    value below reads a `--foundation-*` custom property already defined by
    the base theme, so a child theme skinning those tokens re-skins this
    wrapper for free. No per-variant hardcoded colours, no DB/facade calls.
--}}

@php
    $variantClasses = match ($variant) {
        'elevated' => 'border border-[var(--foundation-border-strong)] shadow-lg',
        'flat' => '',
        default => 'border border-[var(--foundation-border)] ring-1',
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'card-frame-wrapper card-frame-wrapper-' . $variant . ' card-frame-wrapper-hover-' . $hoverEffect . ' rounded-[var(--foundation-radius)] bg-[var(--foundation-card-bg)] ' . $variantClasses]) }}
>
    {{ $slot }}
</div>
