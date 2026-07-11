<form
    method="{{ $form->method }}"
    action="{{ $form->action }}"
    {{ $attributes }}
>
    @if ($form->wired)
        <input
            type="hidden"
            name="source"
            value="{{ $form->source }}"
        />
    @endif

    {{ $slot }}
</form>
