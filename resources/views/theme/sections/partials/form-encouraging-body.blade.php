{{--
    Wave 4c CAP-0233: the real `<form>` markup for the `form--encouraging`
    variant's no-`form_handle` branch. Never included from the main page
    render — theme.sections.form--encouraging.blade.php renders a
    deferred-fragment placeholder instead (see FoundationSection::viewData())
    so the baked `@csrf` token here never reaches the shared HTML cache. It
    is rendered only by FoundationSectionPublicLayoutWidgetPayloadContributor,
    once per visitor, when the browser fetches the placeholder's fragment
    URL.

    (`form--encouraging` is not yet reachable through FoundationSection's
    SECTION_VIEWS map — see the CAP-0233 handoff notes — so this partial is
    prepared, matching the base `form` treatment, for when that wiring lands.)
--}}
<form
    method="post"
    action="{{ $section->action ?? '' }}"
    class="grid gap-5"
    novalidate
>
    @csrf
    @foreach ($formFields as $field)
        @php
            $fieldType = $field['type'] ?? 'text';
            $fieldName = $field['name'] ?? 'field';
            $fieldId = 'theme-form-encouraging-' . $fieldName;
            $fieldLabel = $field['label'] ?? $fieldName;
            $fieldRequired = ! empty($field['required']);
            $fieldHint = $field['encouragement'] ?? null;
        @endphp

        <div class="grid gap-2">
            @if ($fieldType !== 'checkbox')
                <label
                    for="{{ $fieldId }}"
                    class="text-sm font-semibold text-slate-800"
                >
                    {{ $fieldLabel }}
                    @if ($fieldRequired)
                        <span aria-hidden="true">*</span>
                    @endif
                </label>
            @endif

            @switch ($fieldType)
                @case ('textarea')
                    <textarea
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        rows="5"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                        data-form-hint-field
                        data-form-hint-encouragement="{{ $fieldHint }}"
                    ></textarea>
                    @break
                @case ('email')
                    <input
                        type="email"
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                        data-form-hint-field
                        data-form-hint-encouragement="{{ $fieldHint }}"
                    />
                    @break
                @default
                    <input
                        type="text"
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                        data-form-hint-field
                        data-form-hint-encouragement="{{ $fieldHint }}"
                    />
            @endswitch

            <p
                class="form-hint-message text-xs text-emerald-700"
                role="status"
                aria-live="polite"
                data-form-hint-message
                data-form-hint-message-for="{{ $fieldId }}"
            ></p>
        </div>
    @endforeach

    <button
        type="submit"
        class="w-fit rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
    >
        {{ $section->submitLabel ?? __('capell-theme-foundation::generic.form_submit') }}
    </button>
</form>
