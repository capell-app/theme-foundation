@php
    $addressLines = is_array($section->address_lines ?? null) ? $section->address_lines : [];
    $formHandle = $section->form_handle ?? null;
    $formInstanceId = (string) ($section->form_instance_id ?? 'theme-contact-form');
@endphp

<section class="border-b border-slate-200/80 bg-[var(--theme-surface)]">
    <div
        class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.6fr)] lg:py-16"
    >
        <div>
            <p class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase">
                {{ __('capell-theme-foundation::generic.contact') }}
            </p>
            <h1
                class="text-4xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-5xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.contact') }}
            </h1>
            @if (! empty($section->summary))
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-8">
                @if (is_string($formHandle) && $formHandle !== '')
                    <x-capell::form-embed
                        :handle="$formHandle"
                        :instance-id="$formInstanceId"
                        :fallback-message="$section->fallback_message ?? null"
                        :fallback-label="$section->fallback_label ?? null"
                        :fallback-url="$section->fallback_url ?? null"
                    />
                @else
                    <form
                        class="grid gap-4"
                        method="post"
                        action="{{ $section->action ?? '' }}"
                    >
                        <label
                            class="grid gap-2 text-sm font-semibold text-slate-800"
                        >
                            {{ __('capell-theme-foundation::generic.name') }}
                            <input
                                class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3"
                                name="name"
                                type="text"
                                autocomplete="name"
                                required
                            />
                        </label>
                        <label
                            class="grid gap-2 text-sm font-semibold text-slate-800"
                        >
                            {{ __('capell-theme-foundation::generic.email') }}
                            <input
                                class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                            />
                        </label>
                        <label
                            class="grid gap-2 text-sm font-semibold text-slate-800"
                        >
                            {{ __('capell-theme-foundation::generic.message') }}
                            <textarea
                                class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3"
                                name="message"
                                rows="5"
                                required
                            ></textarea>
                        </label>
                        <button
                            class="w-fit rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white"
                            type="submit"
                        >
                            {{ $section->submit_label ?? __('capell-theme-foundation::generic.form_submit') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <aside class="grid content-start gap-5">
            @if ($addressLines !== [])
                <address
                    class="rounded-[var(--theme-radius-value)] border border-slate-200 bg-white p-6 text-sm leading-7 text-slate-600 not-italic"
                >
                    @foreach ($addressLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </address>
            @endif
            <x-capell-theme-foundation::display.map-link
                :latitude="$section->latitude ?? null"
                :longitude="$section->longitude ?? null"
                :label="$section->map_label ?? null"
            />
        </aside>
    </div>
</section>
