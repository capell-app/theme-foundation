@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;

    $paginationLinks = data_get($section, 'resolvedResults.pagination.links', []);
@endphp

@if (count($paginationLinks) > 0)
    <nav
        class="mt-8"
        aria-label="{{ __('capell-theme-foundation::generic.page_navigation') }}"
    >
        <ul class="flex flex-wrap items-center gap-2">
            @foreach ($paginationLinks as $link)
                @php
                    $safePageUrl = PublicUrlSanitizer::sanitize(data_get($link, 'url'));
                @endphp
                <li>
                    @if ($safePageUrl !== null)
                        <a
                            href="{{ $safePageUrl }}"
                            @if (data_get($link, 'current')) aria-current="page" @endif
                            @if (data_get($link, 'relation')) rel="{{ data_get($link, 'relation') }}" @endif
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-[var(--foundation-border)] px-3 text-sm font-semibold text-[var(--foundation-body-fg)] aria-[current=page]:bg-[var(--foundation-card-bg)]"
                            >{{ data_get($link, 'label') }}</a
                        >
                    @else
                        <span
                            class="inline-flex min-h-11 min-w-11 items-center justify-center px-3 text-sm text-[var(--foundation-muted-fg)]"
                            >{{ data_get($link, 'label') }}</span
                        >
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
