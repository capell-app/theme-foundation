<div {{ $attributes }}>
    @if ($formEmbed->available)
        @livewire ($formEmbed->componentName, ['handle' => $formEmbed->handle, 'widgetData' => $formEmbed->widgetData], key($formEmbed->instanceId))
    @else
        <div
            class="theme-form-fallback grid gap-3"
            role="status"
        >
            <p>
                {{ data_get($formEmbed->widgetData, 'fallback_message', __('capell-theme-foundation::generic.form_unavailable')) }}
            </p>

            @if (data_get($formEmbed->widgetData, 'fallback_url'))
                <a
                    href="{{ data_get($formEmbed->widgetData, 'fallback_url') }}"
                    class="theme-form-fallback-link w-fit rounded-full bg-[var(--theme-primary)] px-5 py-3 text-sm font-semibold text-white"
                >
                    {{ data_get($formEmbed->widgetData, 'fallback_label', __('capell-theme-foundation::generic.form_contact_instead')) }}
                </a>
            @endif
        </div>
    @endif
</div>
