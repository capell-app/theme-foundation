@php
    $currentPage = (int) ($section->currentPage ?? 1);
    $totalPages = (int) ($section->totalPages ?? 1);
    $baseUrl = (string) ($section->baseUrl ?? '');
    $pageUrl = static fn (int $page): string => $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $page;
@endphp

<section
    class="theme-pagination border-b border-[var(--foundation-border)] bg-[var(--foundation-section-muted-bg)]"
>
    <nav
        class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-8 sm:px-6"
        aria-label="{{ __('capell-theme-foundation::generic.page_navigation') }}"
    >
        @if ($currentPage > 1)
            <a
                href="{{ $pageUrl($currentPage - 1) }}"
                rel="prev"
                class="rounded-full border border-[var(--foundation-border-strong)] px-4 py-2 text-sm font-semibold text-[var(--foundation-body-fg)] transition hover:bg-[var(--foundation-card-bg)]"
            >
                {{ __('capell-theme-foundation::generic.previous') }}
            </a>
        @else
            <span
                class="rounded-full border border-[var(--foundation-border)] px-4 py-2 text-sm font-semibold text-[var(--foundation-muted-fg)]"
            >
                {{ __('capell-theme-foundation::generic.previous') }}
            </span>
        @endif

        <ul class="flex flex-wrap items-center gap-1">
            @for ($page = 1; $page <= $totalPages; $page++)
                <li>
                    <a
                        href="{{ $pageUrl($page) }}"
                        @if ($page === $currentPage) aria-current="page" @endif
                        class="{{ $page === $currentPage ? 'bg-[var(--theme-primary)] text-white' : 'text-[var(--foundation-body-fg)] hover:bg-[var(--foundation-card-bg)]' }} flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold transition"
                    >
                        {{ $page }}
                    </a>
                </li>
            @endfor
        </ul>

        @if ($currentPage < $totalPages)
            <a
                href="{{ $pageUrl($currentPage + 1) }}"
                rel="next"
                class="rounded-full border border-[var(--foundation-border-strong)] px-4 py-2 text-sm font-semibold text-[var(--foundation-body-fg)] transition hover:bg-[var(--foundation-card-bg)]"
            >
                {{ __('capell-theme-foundation::generic.next') }}
            </a>
        @else
            <span
                class="rounded-full border border-[var(--foundation-border)] px-4 py-2 text-sm font-semibold text-[var(--foundation-muted-fg)]"
            >
                {{ __('capell-theme-foundation::generic.next') }}
            </span>
        @endif
    </nav>
</section>
