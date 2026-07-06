@props ([
    'aspectRatio' => null,
])

{{--
    Shared Foundation display primitive (Wave 2.7): a treatment layer that
    applies a per-theme duotone/tone-map skin over any wrapped media (an
    `<img>`, `<picture>`, or `art-directed-picture`) via CSS `filter` plus a
    `mix-blend-mode` tint overlay — the systematised version of the
    grayscale/duotone treatments themes like art-paper and off-grid already
    hand-roll (Wave 0.5 screenshot audit). Exists so themes sharing the
    `ThemeDemoMedia` stock photo pool can read as visually distinct without
    each needing bespoke photography or bespoke CSS.

    Every value is a `--foundation-photo-*` custom property with an inert
    fallback (`none` / `transparent` / `normal` / `0`), so the primitive is
    a no-op until a theme's own CSS sets its tokens — deterministic and
    token-driven, no per-instance PHP logic, no DB/facade calls.
--}}

<div
    {{ $attributes->except(['style'])->merge(['class' => 'photo-treatment-filter relative isolate overflow-hidden']) }}
    style="@if ($aspectRatio) aspect-ratio: {{ $aspectRatio }}; @endif {{ $attributes->get('style') }}"
>
    <div
        class="photo-treatment-filter-media h-full w-full"
        style="filter: var(--foundation-photo-filter, none)"
    >
        {{ $slot }}
    </div>

    <div
        class="photo-treatment-filter-tint pointer-events-none absolute inset-0"
        style="
            background-color: var(--foundation-photo-tint, transparent);
            mix-blend-mode: var(--foundation-photo-tint-blend, normal);
            opacity: var(--foundation-photo-tint-opacity, 0);
        "
        aria-hidden="true"
    ></div>
</div>
