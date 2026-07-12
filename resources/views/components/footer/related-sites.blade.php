@props ([
    'relatedSites' => collect(),
])

@if ($relatedSites->isNotEmpty())
    <div
        {{ $attributes->class(['border-t border-[var(--border-color-footer)] pt-8']) }}
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($relatedSites as $relatedSite)
                <a
                    class="group grid gap-2 rounded-lg border border-[var(--border-color-footer)] bg-[var(--bg-color-footer-panel)] p-4 text-[var(--color-footer)] transition-colors hover:border-[var(--color-footer-muted)] focus-visible:border-[var(--color-footer-muted)] focus-visible:ring-2 focus-visible:ring-[var(--color-footer-muted)] focus-visible:outline-none"
                    href="{{ $relatedSite['url'] }}"
                    @wireNavigate
                >
                    <span
                        class="text-base leading-tight font-semibold text-[var(--color-footer-link)] underline underline-offset-4 transition-colors group-hover:text-[var(--color-footer-heading)]"
                        style="{{ $relatedSite['primaryColor'] ? 'color:' . $relatedSite['primaryColor'] : '' }}"
                    >
                        {{ $relatedSite['title'] }}
                    </span>
                    @if ($relatedSite['description'])
                        <span
                            class="text-sm leading-5 text-[var(--color-footer-muted)]"
                        >
                            {{ $relatedSite['description'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
