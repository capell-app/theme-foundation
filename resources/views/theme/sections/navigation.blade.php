<nav
    class="theme-navigation sticky top-0 z-40 border-b border-slate-200/80 bg-[var(--theme-surface)]/92 backdrop-blur"
    aria-label="{{ __('capell-theme-foundation::generic.main_navigation') }}"
>
    <div
        class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6"
    >
        <a
            href="/"
            class="inline-flex items-center gap-2.5 text-sm font-semibold text-slate-950"
        >
            <span>{{ $section->brandName }}</span>
        </a>

        <div class="hidden items-center gap-5 text-sm text-slate-600 md:flex">
            @foreach ($section->items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="transition hover:text-slate-950"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <details class="relative md:hidden">
            <summary
                class="cursor-pointer list-none rounded-md border border-slate-300 px-3 py-2 text-sm font-medium marker:hidden"
            >
                {{ __('capell-theme-foundation::generic.menu') }}
            </summary>
            <div
                class="absolute end-0 z-30 mt-3 grid min-w-48 gap-3 rounded-md border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-xl"
            >
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="transition hover:text-slate-950"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </details>

        @if ($section->ctaLabel && $section->ctaUrl)
            <a
                href="{{ $section->ctaUrl }}"
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
