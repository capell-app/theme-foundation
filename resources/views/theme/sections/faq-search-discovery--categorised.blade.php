@php
    $faqs = is_array($section->items ?? null) ? $section->items : [];
    $categories = collect($faqs)
        ->map(fn (array $faq): mixed => $faq['category'] ?? null)
        ->filter(fn (mixed $category): bool => is_string($category) && $category !== '')
        ->unique()
        ->values()
        ->all();
    $hasCategories = count($categories) > 0;
@endphp

{{--
    Categorised variant: adds category pills above the same search-filtered
    list for larger FAQ payloads that benefit from a first coarse pass
    before free-text search narrows further.
--}}
<section
    class="theme-faq-search-discovery theme-faq-search-discovery--categorised border-b border-slate-200/80 bg-white"
    data-faq-search-discovery
>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mx-auto mb-8 max-w-2xl text-center">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.faq_search_discovery') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.faq_search_discovery') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <div class="relative mb-4">
            <label
                for="faq-search-discovery-categorised-input"
                class="sr-only"
            >
                {{ __('capell-theme-foundation::generic.faq_search_placeholder') }}
            </label>
            <input
                type="search"
                id="faq-search-discovery-categorised-input"
                placeholder="{{ __('capell-theme-foundation::generic.faq_search_placeholder') }}"
                class="w-full rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
                data-faq-search-input
                autocomplete="off"
            />
            <p
                class="mt-2 text-xs text-slate-500"
                role="status"
                aria-live="polite"
                data-faq-search-status
            ></p>
        </div>

        @if ($hasCategories)
            <div
                class="mb-6 flex flex-wrap gap-2"
                data-faq-search-categories
            >
                <button
                    type="button"
                    class="faq-search-category-tab rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                    data-category="all"
                    data-faq-search-category-tab
                >
                    {{ __('capell-theme-foundation::generic.faq_search_all_category') }}
                </button>

                @foreach ($categories as $category)
                    <button
                        type="button"
                        class="faq-search-category-tab rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                        data-category="{{ $category }}"
                        data-faq-search-category-tab
                    >
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        @endif

        <div
            class="faq-search-container grid gap-3"
            data-faq-search-list
        >
            @forelse ($faqs as $faq)
                <details
                    class="faq-search-item group rounded-xl border border-slate-200 bg-white"
                    data-faq-search-item
                    data-faq-search-question="{{ $faq['question'] ?? '' }}"
                    data-faq-search-answer="{{ strip_tags($faq['answer'] ?? '') }}"
                    data-category="{{ $faq['category'] ?? 'uncategorised' }}"
                >
                    <summary
                        class="flex cursor-pointer items-center justify-between p-5 text-base font-semibold text-slate-950 select-none"
                    >
                        <span
                            data-faq-search-question-text
                            >{{ $faq['question'] ?? '' }}</span
                        >
                        <span
                            class="ml-4 flex-shrink-0 text-xl text-slate-500 transition-transform group-open:rotate-45"
                            aria-hidden="true"
                        >
                            +
                        </span>
                    </summary>

                    @if (! empty($faq['answer']))
                        <div
                            class="border-t border-slate-100 px-5 pt-4 pb-5 leading-relaxed text-slate-600"
                            data-faq-search-answer-text
                        >
                            {{ strip_tags($faq['answer']) }}
                        </div>
                    @endif
                </details>
            @empty
                <p class="text-center text-slate-500">
                    {{ __('capell-theme-foundation::generic.empty_faqs') }}
                </p>
            @endforelse
        </div>

        <p
            class="hidden py-8 text-center text-slate-500"
            data-faq-search-empty
        >
            {{ __('capell-theme-foundation::generic.faq_search_no_results') }}
        </p>
    </div>
</section>
