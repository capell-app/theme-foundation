@php
    $formFields = is_array($section->fields ?? null) ? $section->fields : [];
    $formHandle = $section->form_handle ?? $section->formHandle ?? null;
    $formInstanceId = (string) ($section->form_instance_id ?? 'theme-contact-form');
    $fallbackMessage = (string) ($section->fallback_message ?? '');
    $fallbackLabel = (string) ($section->fallback_label ?? '');
    $fallbackUrl = (string) ($section->fallback_url ?? '');
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
        @else
            <form
                method="post"
                action="{{ $section->action ?? '' }}"
                class="grid gap-5"
            >
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
        @endif
    </div>
</section>
