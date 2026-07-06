@php
    $entries = is_array($section->entries ?? null) ? $section->entries : [];
@endphp

{{--
    Grid variant: same changelog payload rendered as cards rather than a
    timeline, for themes/pages that want updates presented alongside other
    grid-based content sections.
--}}
<section
    class="theme-changelog-stream theme-changelog-stream--grid border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8 max-w-2xl">
            <p
                class="mb-3 text-xs font-semibold tracking-[0.16em] text-[var(--theme-primary)] uppercase"
            >
                {{ __('capell-theme-foundation::generic.changelog_stream') }}
            </p>
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.changelog_stream') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if (count($entries) > 0)
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($entries as $entry)
                    <article
                        class="changelog-stream-entry rounded-[var(--theme-radius-value)] border border-slate-200 bg-white p-5"
                    >
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if (! empty($entry['version']))
                                <span
                                    class="font-mono text-sm font-semibold text-slate-900"
                                >
                                    {{ $entry['version'] }}
                                </span>
                            @endif

                            @if (! empty($entry['tag']))
                                <span
                                    class="rounded-full bg-[var(--theme-primary)]/10 px-2 py-0.5 text-xs font-semibold text-[var(--theme-primary)]"
                                >
                                    {{ $entry['tag'] }}
                                </span>
                            @endif
                        </div>

                        <h3 class="text-base font-semibold text-slate-950">
                            {{ $entry['title'] ?? '' }}
                        </h3>

                        @if (! empty($entry['summary']))
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $entry['summary'] }}
                            </p>
                        @endif

                        @if (! empty($entry['date']))
                            <time
                                class="mt-3 block text-xs text-slate-500"
                                datetime="{{ $entry['date'] }}"
                            >
                                {{ $entry['date'] }}
                            </time>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-slate-500">
                {{ __('capell-theme-foundation::generic.empty_changelog') }}
            </p>
        @endif
    </div>
</section>
