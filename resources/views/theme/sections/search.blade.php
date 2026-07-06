@php
    $searchResults = is_array($section->results ?? null) ? $section->results : [];
@endphp

<section
    class="theme-search border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.search') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.search') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <form
            method="get"
            action="{{ $section->action ?? '' }}"
            class="flex flex-col gap-3 sm:flex-row"
            role="search"
        >
            <label
                for="theme-search-query"
                class="sr-only"
            >
                {{ __('capell-theme-foundation::generic.search') }}
            </label>
            <input
                type="search"
                id="theme-search-query"
                name="{{ $section->queryParameter ?? 'q' }}"
                value="{{ $section->query ?? '' }}"
                placeholder="{{ $section->placeholder ?? __('capell-theme-foundation::generic.search') }}"
                class="w-full rounded-[var(--theme-radius-value)] border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 placeholder:text-slate-400 focus:border-[var(--theme-primary)] focus:ring-2 focus:ring-[var(--theme-primary)] focus:outline-none"
            />
            <button
                type="submit"
                class="shrink-0 rounded-full bg-[var(--theme-primary)] px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
            >
                {{ __('capell-theme-foundation::generic.search') }}
            </button>
        </form>

        @if ($searchResults !== [])
            <ul class="mt-8 divide-y divide-slate-200/80">
                @foreach ($searchResults as $result)
                    <li class="py-5 first:pt-0 last:pb-0">
                        <a
                            href="{{ $result['url'] ?? '#' }}"
                            class="group widget block"
                        >
                            <span
                                class="widget block text-lg font-semibold text-slate-950 group-hover:underline"
                            >
                                {{ $result['title'] ?? '' }}
                            </span>
                            @if (! empty($result['summary']))
                                <span
                                    class="widget mt-1 block text-sm leading-6 text-slate-600"
                                >
                                    {{ $result['summary'] }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
