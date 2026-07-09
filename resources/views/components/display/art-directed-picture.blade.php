@props ([
    'sources' => [],
    'src',
    'alt' => '',
    'aspectRatio' => '16/9',
    'focalX' => '50%',
    'focalY' => '50%',
    'loading' => 'lazy',
])

{{--
    Shared Foundation display primitive (Wave 2.7): a `<picture>` wrapper
    supporting art direction per breakpoint via `sources`, a focal-point
    CSS custom property pair driving `object-position`, and an
    aspect-ratio token applied inline (payload-driven, not hardcoded).

    `sources` is a list of `['media' => '(min-width: 768px)', 'srcset' => '...']`
    pairs rendered as `<source>` elements in order before the fallback `<img>`.
    Purely presentational: no DB/facade calls, token-skinned via
    `--focal-x`/`--focal-y` consumed by `object-position` and the theme's
    own `--foundation-*` custom properties for border/radius styling.
--}}

<picture
    {{ $attributes->except(['style'])->merge(['class' => 'block overflow-hidden']) }}
    style="aspect-ratio: {{ $aspectRatio }}; {{ $attributes->get('style') }}"
>
    @foreach ($sources as $source)
        <source
            @if (($source['media'] ?? null) !== null) media="{{ $source['media'] }}" @endif
            srcset="{{ $source['srcset'] ?? '' }}"
            @if (($source['type'] ?? null) !== null) type="{{ $source['type'] }}" @endif
        />
    @endforeach

    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        loading="{{ $loading }}"
        class="h-full w-full object-cover"
        style="--focal-x: {{ $focalX }}; --focal-y: {{ $focalY }}; object-position: var(--focal-x) var(--focal-y);"
    />
</picture>
