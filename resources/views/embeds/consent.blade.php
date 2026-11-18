@if ($permitted || $fallbackHref !== '')
    <figure
        class="capell-consent-embed"
        @if ($permitted) data-capell-consent-embed @endif
    >
        @if ($permitted)
            <div
                class="capell-consent-embed__placeholder"
                data-capell-consent-embed-placeholder
            >
                <p>
                    {{ __('capell-theme-foundation::generic.embed_consent_notice', ['provider' => $provider]) }}
                </p>
                <button
                    type="button"
                    class="capell-consent-embed__load"
                    data-capell-consent-embed-load
                >
                    {{ __('capell-theme-foundation::generic.embed_consent_load', ['title' => $title]) }}
                </button>
            </div>
            <template data-capell-consent-embed-template>
                <iframe
                    src="{{ $src }}"
                    title="{{ $title }}"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen
                ></iframe>
            </template>
        @endif

        @if ($fallbackHref !== '')
            <p class="capell-consent-embed__fallback">
                <a
                    href="{{ $fallbackHref }}"
                    rel="noopener"
                    data-capell-consent-embed-fallback
                >
                    {{ $fallbackLabel !== '' ? $fallbackLabel : __('capell-theme-foundation::generic.embed_open_elsewhere', ['title' => $title]) }}
                </a>
            </p>
        @endif

        @if ($caption !== '')
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
