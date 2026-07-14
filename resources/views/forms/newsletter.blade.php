@if ($form->wired)
    <form
        method="{{ $form->method }}"
        action="{{ $form->action }}"
        data-newsletter-form
        {{ $attributes }}
    >
        <input
            type="hidden"
            name="source"
            value="{{ $form->source }}"
        />

        {{ $slot }}
    </form>
@else
    <div
        data-newsletter-unavailable
        role="note"
        {{ $attributes }}
    >
        <p class="theme-newsletter-unavailable__message">
            {{ __('capell-theme-foundation::generic.newsletter_unavailable') }}
        </p>

        <fieldset
            class="theme-newsletter-unavailable__controls"
            disabled
            aria-disabled="true"
        >
            {{ $slot }}
        </fieldset>
    </div>
@endif
