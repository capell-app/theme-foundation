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
    Presentational scaffolding only: renders field markup from the payload
    array and never wires to a real form-processing backend or package —
    submission handling is a theme/integration concern for a dedicated
    form-processing package, mirroring how the "features" section renders
    from payload without calling any live services.
--}}
<section
    class="theme-form border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.form') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.form') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
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
                CAP-0233: a real `<form>@csrf</form>` here would be a
                literal, session-bound CSRF token baked into HTML that the
                shared full-page HTML cache can serve to every later
                visitor. This section renders synchronously as part of the
                normal page response and has no delivery-mode veto, so the
                form is instead delivered dynamically: this placeholder is
                the same contract layout-builder's own generic widget
                wrapper emits for a lazy fragment, and the real form
                (including a fresh, per-visitor @csrf token) is served by
                FoundationSectionPublicLayoutWidgetPayloadContributor when
                the browser fetches the fragment URL.
            --}}
            <div
                data-deferred-fragment
                data-deferred-fragment-url="{{ $formDeliveryFragmentUrl }}"
                class="deferred-fragment"
            ></div>
        @endif
    </div>
</section>
