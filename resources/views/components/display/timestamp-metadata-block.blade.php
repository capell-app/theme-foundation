@props ([
    'datetime',
    'label',
])

{{--
    Shared Foundation display primitive (Wave 2.7): a `<time>` element plus
    a human-readable label rendered entirely server-side from the payload.
    Deliberately has no client-side relative-time JS and never calls
    `new Date()` at render — the caller (Blade view model / demo content)
    is responsible for formatting `label` (e.g. "5 min read" or
    "3 July 2026") ahead of time, so this component's HTML is identical for
    every visitor and stays html-cache-safe.
--}}

<time
    datetime="{{ $datetime }}"
    {{ $attributes->merge(['class' => 'text-sm']) }}
    style="color: var(--foundation-border-strong)"
>
    {{ $label }}
</time>
