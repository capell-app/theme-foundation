@php
    $formFields = is_array($section->fields ?? null) ? $section->fields : [];
    $formHandle = $section->form_handle ?? $section->formHandle ?? null;
    $formInstanceId = (string) ($section->form_instance_id ?? 'theme-contact-form');
    $fallbackMessage = (string) ($section->fallback_message ?? '');
    $fallbackLabel = (string) ($section->fallback_label ?? '');
    $fallbackUrl = (string) ($section->fallback_url ?? '');
    $formDeliveryFragmentUrl ??= null;
@endphp

{{--
    Wave 4c §D "helpful-form-hints": same presentational-only form
    scaffolding as the base `form` section, plus a per-field encouragement
    message region wired to `aria-live="polite"` so screen readers announce
    positive-reinforcement text as the visitor types (not just error
    states). The frontend runtime (data-form-hint-field contract) supplies
    the as-you-type copy; this view only renders the announcement region
    and never validates or submits anything itself.
--}}
<section
    class="theme-form theme-form--encouraging border-b border-slate-200/80 bg-[var(--theme-surface)]"
    data-form-encouraging
>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8">
            <p class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase">
                {{ __('capell-theme-foundation::generic.form') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.form') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">{{ $section->summary }}</p>
            @endif
        </div>

        @if (is_int($formHandle) || (is_string($formHandle) && $formHandle !== ''))
            <x-capell::form-embed
                :handle="$formHandle"
                :instance-id="$formInstanceId"
                :fallback-message="$fallbackMessage"
                :fallback-label="$fallbackLabel"
                :fallback-url="$fallbackUrl"
                class="grid gap-5"
            />
        @elseif ($formDeliveryFragmentUrl)
            {{--
                CAP-0233: see theme.sections.form.blade.php for why the real
                @csrf-bearing <form> is delivered via this deferred-fragment
                placeholder instead of rendering synchronously into the
                cached page response.
            --}}
            <div
                data-deferred-fragment
                data-deferred-fragment-url="{{ $formDeliveryFragmentUrl }}"
                class="deferred-fragment"
            ></div>
        @endif
    </div>
</section>
