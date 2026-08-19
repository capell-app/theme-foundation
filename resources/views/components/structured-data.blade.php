@props([
    'schema' => [],
])

@if (is_array($schema) && $schema !== [])
    <script
        type="application/ld+json"
    >{!! json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
@endif
