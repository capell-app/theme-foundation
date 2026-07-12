<footer class="theme-footer mt-auto bg-slate-950 text-white">
    <h2 class="sr-only">{{ __('capell-theme-foundation::generic.footer') }}</h2>
    <div
        class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.2fr_2fr]"
    >
        <div>
            <p class="text-sm font-semibold">{{ $section->brandName }}</p>
            @if ($section->summary)
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-400">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ($section->columns as $column)
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ $column['heading'] }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-400">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="transition hover:text-white"
                                >
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</footer>
