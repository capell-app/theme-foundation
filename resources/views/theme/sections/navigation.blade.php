@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $safeCtaUrl = PublicUrlSanitizer::sanitize($section->ctaUrl ?? null);
@endphp
<nav
    class="theme-navigation sticky top-0 z-40 border-b border-[var(--foundation-border)] bg-[var(--theme-surface)]/92 dark:bg-[var(--foundation-header-bg)]/92 backdrop-blur"
    aria-label="{{ __('capell-theme-foundation::generic.main_navigation') }}"
>
    <div
        class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6"
    >
        <a
            href="/"
            class="inline-flex items-center gap-2.5 text-sm font-semibold text-[var(--foundation-heading-fg)]"
        >
            <span>{{ $section->brandName }}</span>
        </a>

        <div
            class="hidden items-center gap-5 text-sm text-[var(--foundation-muted-fg)] md:flex"
        >
            @foreach ($section->items as $item)
                @php
                    $safeItemUrl = PublicUrlSanitizer::sanitize($item['url'] ?? null);
                @endphp
                <{{ $safeItemUrl ? 'a' : 'span' }}
                    @if ($safeItemUrl) href="{{ $safeItemUrl }}" @endif
                    class="transition hover:text-[var(--foundation-heading-fg)]"
                >
                    {{ $item['label'] }}
                </{{ $safeItemUrl ? 'a' : 'span' }}>
            @endforeach
        </div>

        <details class="relative md:hidden">
            <summary
                class="cursor-pointer list-none rounded-md border border-[var(--foundation-border)] px-3 py-2 text-sm font-medium marker:hidden"
            >
                {{ __('capell-theme-foundation::generic.menu') }}
            </summary>
            <div
                class="absolute end-0 z-30 mt-3 grid min-w-48 gap-3 rounded-md border border-[var(--foundation-border)] bg-white dark:bg-[var(--foundation-card-bg)] p-4 text-sm text-[var(--foundation-muted-fg)] shadow-xl"
            >
                @foreach ($section->items as $item)
                    @php
                        $safeItemUrl = PublicUrlSanitizer::sanitize($item['url'] ?? null);
                    @endphp
                    <{{ $safeItemUrl ? 'a' : 'span' }}
                        @if ($safeItemUrl) href="{{ $safeItemUrl }}" @endif
                        class="transition hover:text-[var(--foundation-heading-fg)]"
                    >
                        {{ $item['label'] }}
                    </{{ $safeItemUrl ? 'a' : 'span' }}>
                @endforeach
            </div>
        </details>

        @if ($section->ctaLabel && $safeCtaUrl)
            <a
                href="{{ $safeCtaUrl }}"
                class="hidden rounded-full bg-[var(--theme-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 sm:inline-flex"
            >
                {{ $section->ctaLabel }}
            </a>
        @endif
    </div>
</nav>
<span
    id="main-content"
    tabindex="-1"
></span>
