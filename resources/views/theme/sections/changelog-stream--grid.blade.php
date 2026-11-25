@php
    $entries = is_array($section->entries ?? null) ? $section->entries : [];
@endphp

{{--
    Grid variant: same changelog payload rendered as cards rather than a
    timeline, for themes/pages that want updates presented alongside other
    grid-based content sections.
--}}
<section
    class="theme-changelog-stream theme-changelog-stream--grid border-b border-[var(--foundation-border)] bg-[var(--theme-surface)] dark:bg-[var(--foundation-section-bg)]"
>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="mb-8 max-w-2xl">
            <p class="mb-3 text-base font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]">
                {{ __('capell-theme-foundation::generic.changelog_stream') }}
            </p>
            <h2
                class="text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-[var(--foundation-heading-fg)] sm:text-4xl"
            >
                {{ $section->heading ?? __('capell-theme-foundation::generic.changelog_stream') }}
            </h2>
            @if (! empty($section->summary))
                <p class="mt-3 text-sm leading-7 text-[var(--foundation-muted-fg)] sm:text-base">{{ $section->summary }}</p>
            @endif
        </div>

        @if (count($entries) > 0)
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($entries as $entry)
                    <article
                        class="changelog-stream-entry rounded-[var(--theme-radius-value)] border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)] p-5"
                    >
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if (! empty($entry['version']))
                                <span
                                    class="font-mono text-sm font-semibold text-[var(--foundation-heading-fg)]"
                                >
                                    {{ $entry['version'] }}
                                </span>
                            @endif

                            @if (! empty($entry['tag']))
                                <span
                                    class="rounded-full bg-[var(--theme-primary)]/10 px-2 py-0.5 text-xs font-semibold text-[var(--theme-primary)] dark:text-[var(--foundation-primary-action)]"
                                >
                                    {{ $entry['tag'] }}
                                </span>
                            @endif
                        </div>

                        <h3
                            class="text-base font-semibold text-[var(--foundation-heading-fg)]"
                        >
                            {{ $entry['title'] ?? '' }}
                        </h3>

                        @if (! empty($entry['summary']))
                            <p class="mt-2 text-sm leading-6 text-[var(--foundation-muted-fg)]">{{ $entry['summary'] }}</p>
                        @endif

                        @if (! empty($entry['date']))
                            <time
                                class="mt-3 block text-xs text-[var(--foundation-muted-fg)]"
                                datetime="{{ $entry['date'] }}"
                            >
                                {{ $entry['date'] }}
                            </time>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-[var(--foundation-muted-fg)]">{{ __('capell-theme-foundation::generic.empty_changelog') }}</p>
        @endif
    </div>
</section>
