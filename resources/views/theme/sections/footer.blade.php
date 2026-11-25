@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
<footer
    class="theme-footer mt-auto bg-[var(--foundation-inverse-bg)] text-white"
>
    <h2 class="sr-only">{{ __('capell-theme-foundation::generic.footer') }}</h2>
    <div
        class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.2fr_2fr]"
    >
        <div>
            <p class="text-sm font-semibold">{{ $section->brandName }}</p>
            @if ($section->summary)
                <p class="mt-3 max-w-sm text-sm leading-6 text-white/70">{{ $section->summary }}</p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ($section->columns as $column)
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ $column['heading'] }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @foreach ($column['links'] as $link)
                            @php
                                $safeLinkUrl = PublicUrlSanitizer::sanitize($link['url'] ?? null);
                            @endphp
                            <li>
                                <{{ $safeLinkUrl ? 'a' : 'span' }}
                                    @if ($safeLinkUrl) href="{{ $safeLinkUrl }}" @endif
                                    class="transition hover:text-white"
                                >
                                    {{ $link['label'] }}
                                </{{ $safeLinkUrl ? 'a' : 'span' }}>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</footer>
