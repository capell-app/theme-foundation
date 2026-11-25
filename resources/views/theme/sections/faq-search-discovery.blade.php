@php
    $faqs = is_array($section->items ?? null) ? $section->items : [];
@endphp

{{--
    Wave 4c §D: type-to-filter FAQ search. Plain JS substring matching only
    (no fuzzy-search library per §0.4) — the frontend runtime hides
    non-matching items and wraps the matched substring in `<mark>` for
    visual highlight. Degrades to a plain list of `<details>` accordions
    with JS disabled, since the search input is additive filtering, not the
    only way to browse.
--}}
<section
    class="theme-faq-search-discovery border-b border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-section-bg)]"
    data-faq-search-discovery
>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-8 max-w-2xl text-center">
            <p class="mb-3 text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]">
                {{ __('capell-theme-foundation::generic.faq_search_discovery') }}
            </p>
            <h2
                class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.faq_search_discovery') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">{{ $section->summary }}</p>
            @endif
        </div>

        <div class="relative mb-6">
            <label
                for="faq-search-discovery-input"
                class="sr-only"
            >
                {{ __('capell-theme-foundation::generic.faq_search_placeholder') }}
            </label>
            <input
                type="search"
                id="faq-search-discovery-input"
                placeholder="{{ __('capell-theme-foundation::generic.faq_search_placeholder') }}"
                class="w-full rounded-[var(--theme-radius-value)] border border-[var(--foundation-control-border)] bg-white dark:bg-[var(--foundation-card-bg)] px-4 py-3 text-sm text-[var(--foundation-heading-fg)] focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                data-faq-search-input
                autocomplete="off"
            />
            <p class="mt-2 text-xs text-[var(--foundation-muted-fg)]" role="status" aria-live="polite" data-faq-search-status></p>
        </div>

        <div
            class="faq-search-container grid gap-3"
            data-faq-search-list
        >
            @forelse ($faqs as $faq)
                <details
                    class="faq-search-item group rounded-xl border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)]"
                    data-faq-search-item
                    data-faq-search-question="{{ $faq['question'] ?? '' }}"
                    data-faq-search-answer="{{ strip_tags($faq['answer'] ?? '') }}"
                >
                    <summary
                        class="flex cursor-pointer items-center justify-between p-5 text-base font-semibold text-[var(--foundation-heading-fg)] select-none"
                    >
                        <span
                            data-faq-search-question-text
                            >{{ $faq['question'] ?? '' }}</span
                        >
                        <span
                            class="ms-4 flex-shrink-0 text-xl text-[var(--foundation-muted-fg)] transition-transform group-open:rotate-45"
                            aria-hidden="true"
                        >
                            +
                        </span>
                    </summary>

                    @if (! empty($faq['answer']))
                        <div
                            class="border-t border-[var(--foundation-border)] px-5 pt-4 pb-5 leading-relaxed text-[var(--foundation-muted-fg)]"
                            data-faq-search-answer-text
                        >
                            {{ strip_tags($faq['answer']) }}
                        </div>
                    @endif
                </details>
            @empty
                <p class="text-center text-[var(--foundation-muted-fg)]">{{ __('capell-theme-foundation::generic.empty_faqs') }}</p>
            @endforelse
        </div>

        <p class="hidden py-8 text-center text-[var(--foundation-muted-fg)]" data-faq-search-empty>
            {{ __('capell-theme-foundation::generic.faq_search_no_results') }}
        </p>
    </div>
</section>
