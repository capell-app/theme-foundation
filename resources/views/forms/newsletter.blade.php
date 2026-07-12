@if ($form->wired)
    <form
        method="{{ $form->method }}"
        action="{{ $form->action }}"
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
        {{ $attributes }}
    >
        {{ $slot }}
    </div>
@endif
