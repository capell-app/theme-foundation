@props ([
    'actionLabel' => null,
    'actionUrl' => null,
    'description' => null,
    'title' => null,
])

@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeActionUrl = PublicUrlSanitizer::sanitize($actionUrl);
@endphp

<div
    {{ $attributes->class(['capell-no-results no-results']) }}
    role="status"
>
    <div
        class="capell-no-results__icon"
        aria-hidden="true"
    >
        @svg ('heroicon-o-magnifying-glass', 'h-6 w-6')
    </div>

    <div class="capell-no-results__content">
        <p class="capell-no-results__title">
            {{ $title ?: __('capell-frontend::generic.no_results') }}
        </p>

        @if ($slot->isNotEmpty())
            <div class="capell-no-results__description">{{ $slot }}</div>
        @elseif (filled($description))
            <p class="capell-no-results__description">{{ $description }}</p>
        @endif
    </div>

    @if ($safeActionUrl !== null && filled($actionLabel))
        <a
            href="{{ $safeActionUrl }}"
            class="capell-no-results__action"
        >
            {{ $actionLabel }}
        </a>
    @endif
</div>
