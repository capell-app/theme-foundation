@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeFallbackUrl = PublicUrlSanitizer::sanitize(data_get($formEmbed->widgetData, 'fallback_url'));
@endphp
<div {{ $attributes }}>
    @if ($formEmbed->available)
        @livewire($formEmbed->componentName, ['handle' => $formEmbed->handle, 'widgetData' => $formEmbed->widgetData], key($formEmbed->instanceId))
    @else
        <div
            class="theme-form-fallback"
            role="note"
            aria-labelledby="{{ $formEmbed->instanceId }}-fallback-title"
            data-theme-form-fallback
        >
            <div
                class="theme-form-fallback__icon"
                aria-hidden="true"
            >
                @svg('heroicon-o-envelope', 'h-5 w-5')
            </div>

            <p
                id="{{ $formEmbed->instanceId }}-fallback-title"
                class="theme-form-fallback__message"
            >
                {{ data_get($formEmbed->widgetData, 'fallback_message', __('capell-theme-foundation::generic.form_unavailable')) }}
            </p>

            @if ($safeFallbackUrl !== null)
                <a
                    href="{{ $safeFallbackUrl }}"
                    class="theme-form-fallback__link"
                >
                    {{ data_get($formEmbed->widgetData, 'fallback_label', __('capell-theme-foundation::generic.form_contact_instead')) }}
                </a>
            @endif
        </div>
    @endif
</div>
