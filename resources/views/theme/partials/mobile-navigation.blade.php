@php
    $mobileLinks = collect($links ?? [])
        ->filter(fn (mixed $link): bool => filled(data_get($link, 'label', data_get($link, 'title'))))
        ->values();
    $mobileCtaLabel = $ctaLabel ?? null;
    $mobileCtaUrl = $ctaUrl ?? null;
@endphp

@if ($mobileLinks->isNotEmpty() || filled($mobileCtaLabel))
    <details class="capell-mobile-nav">
        <summary class="capell-mobile-nav__toggle">
            {{ $menuLabel ?? __('capell-theme-foundation::generic.menu') }}
        </summary>

        <div class="capell-mobile-nav__panel">
            @foreach ($mobileLinks as $link)
                <a
                    href="{{ data_get($link, 'url', data_get($link, 'href', '/')) }}"
                >
                    {{ data_get($link, 'label', data_get($link, 'title', '')) }}
                </a>
            @endforeach

            @if (filled($mobileCtaLabel))
                <a
                    class="capell-mobile-nav__cta"
                    href="{{ $mobileCtaUrl ?? '/' }}"
                >
                    {{ $mobileCtaLabel }}
                </a>
            @endif
        </div>
    </details>
@endif
