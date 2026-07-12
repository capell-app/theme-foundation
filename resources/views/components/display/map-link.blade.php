@props ([
    'latitude' => null,
    'longitude' => null,
    'label' => null,
])

@php
    $latitude = is_numeric($latitude) ? (float) $latitude : null;
    $longitude = is_numeric($longitude) ? (float) $longitude : null;
    $hasCoordinates = $latitude !== null
        && $longitude !== null
        && $latitude >= -90.0
        && $latitude <= 90.0
        && $longitude >= -180.0
        && $longitude <= 180.0;
    $mapLabel = is_string($label) && $label !== ''
        ? $label
        : __('capell-theme-foundation::generic.open_map');
    $mapUrl = $hasCoordinates
        ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($latitude . ',' . $longitude)
        : null;
@endphp

@if ($mapUrl !== null)
    <a
        href="{{ $mapUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        {{ $attributes->merge(['class' => 'group block overflow-hidden rounded-[var(--theme-radius-value)] border border-slate-200 bg-white']) }}
    >
        <svg
            viewBox="0 0 640 360"
            role="img"
            aria-label="{{ $mapLabel }}"
            class="block aspect-video w-full"
        >
            <defs>
                <pattern
                    id="theme-map-grid"
                    width="48"
                    height="48"
                    patternUnits="userSpaceOnUse"
                >
                    <path
                        d="M 48 0 L 0 0 0 48"
                        fill="none"
                        stroke="currentColor"
                        stroke-opacity="0.12"
                    />
                </pattern>
            </defs>
            <rect width="640" height="360" fill="var(--theme-surface, Canvas)" />
            <rect width="640" height="360" fill="url(#theme-map-grid)" class="text-slate-900" />
            <circle cx="320" cy="180" r="20" fill="var(--theme-primary, LinkText)" />
            <circle cx="320" cy="180" r="8" fill="Canvas" />
        </svg>
        <span class="sr-only">{{ $mapLabel }}</span>
    </a>
@endif
