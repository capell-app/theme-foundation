{{--
    Wave 4c CAP-0233: the real `<form>` markup for the `contact-split`
    section's no-`form_handle` branch. Never included from the main page
    render — theme.sections.contact-split.blade.php renders a
    deferred-fragment placeholder instead (see FoundationSection::viewData())
    so the baked `@csrf` token here never reaches the shared HTML cache. It
    is rendered only by FoundationSectionPublicLayoutWidgetPayloadContributor,
    once per visitor, when the browser fetches the placeholder's fragment
    URL.
--}}
@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeAction = PublicUrlSanitizer::sanitize($section->action ?? '') ?? '';
@endphp
<form
    class="grid gap-4"
    method="post"
    action="{{ $safeAction }}"
>
    @csrf
    <label class="grid gap-2 text-sm font-semibold text-slate-800">
        {{ __('capell-theme-foundation::generic.name') }}
        <input
            class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3"
            name="name"
            type="text"
            autocomplete="name"
            required
        />
    </label>
    <label class="grid gap-2 text-sm font-semibold text-slate-800">
        {{ __('capell-theme-foundation::generic.email') }}
        <input
            class="rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3"
            name="email"
            type="email"
            autocomplete="email"
            required
        />
    </label>
    <label class="grid gap-2 text-sm font-semibold text-slate-800">
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
