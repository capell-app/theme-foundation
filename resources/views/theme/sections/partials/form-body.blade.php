{{--
    Wave 4c CAP-0233: the real `<form>` markup for the base `form` section's
    no-`form_handle` branch. This partial is never included from the main
    page render — theme.sections.form.blade.php renders a deferred-fragment
    placeholder instead (see FoundationSection::viewData()) so the baked
    `@csrf` token here never reaches the shared HTML cache. It is rendered
    only by FoundationSectionPublicLayoutWidgetPayloadContributor, once per
    visitor, when the browser fetches the placeholder's fragment URL.
--}}
<form
    method="post"
    action="{{ $section->action ?? '' }}"
    class="grid gap-5"
>
    @csrf
    @foreach ($formFields as $field)
        @php
        $fieldType = $field['type'] ?? 'text';
        $fieldName = $field['name'] ?? 'field';
        $fieldId = 'theme-form-' . $fieldName;
        $fieldLabel = $field['label'] ?? $fieldName;
        $fieldRequired = ! empty($field['required']);
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
                    ></textarea>
                    @break
                @case ('select')
                    <select
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                    >
                        @foreach (($field['options'] ?? []) as $option)
                            <option
                                value="{{ $option['value'] ?? $option }}"
                            >
                                {{ $option['label'] ?? $option }}
                            </option>
                        @endforeach
                    </select>
                    @break
                @case ('checkbox')
                    <label
                        for="{{ $fieldId }}"
                        class="flex items-center gap-2 text-sm text-slate-800"
                    >
                        <input
                            type="checkbox"
                            id="{{ $fieldId }}"
                            name="{{ $fieldName }}"
                            value="1"
                            @if ($fieldRequired) required @endif
                            class="h-4 w-4 rounded border-slate-300 text-[var(--theme-primary)] focus:ring-[var(--theme-primary)]"
                        />
                        {{ $fieldLabel }}
                        @if ($fieldRequired)
                            <span aria-hidden="true">*</span>
                        @endif
                    </label>
                    @break
                @case ('email')
                    <input
                        type="email"
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                    />
                    @break
                @default
                    <input
                        type="text"
                        id="{{ $fieldId }}"
                        name="{{ $fieldName }}"
                        @if ($fieldRequired) required @endif
                        class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                    />
            @endswitch
        </div>
    @endforeach

    <button
        type="submit"
        class="w-fit rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
    >
        {{ $section->submitLabel ?? __('capell-theme-foundation::generic.form_submit') }}
    </button>
</form>
