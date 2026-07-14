{{--
    Shared vertical-theme chrome: site footer.
    Rendered for every theme whose ServiceProvider points its 'footer'
    ViewSectionRenderer at `capell-theme-foundation::theme.chrome.footer`.
    Styled with the `theme-chrome-footer__*` plain-CSS classes shipped in
    `resources/css/theme/chrome.css`, driven by brand `--theme-*` properties.
    Data shape (FooterData): brandName, summary,
    columns[{heading,links[{label,url}]}], legal?.
--}}
@php
    $footerColumns = collect($section->columns ?? [])
        ->map(static function (mixed $column): array {
            $links = collect(data_get($column, 'links', []))
                ->filter(
                    static fn (mixed $link): bool => filled(data_get($link, 'label'))
                        && filled(data_get($link, 'url')),
                )
                ->values()
                ->all();

            return [
                'heading' => data_get($column, 'heading'),
                'links' => $links,
            ];
        })
        ->filter(
            static fn (array $column): bool => filled($column['heading'])
                && $column['links'] !== [],
        )
        ->values();
    $footerLegal = data_get($section, 'legal');
@endphp

<footer
    id="footer"
    class="theme-chrome-footer"
>
    <h2 class="theme-chrome-footer__sr">
        {{ __('capell-theme-foundation::generic.footer') }}
    </h2>

    <div class="theme-chrome-footer__inner">
        <div class="theme-chrome-footer__brand">
            <a
                href="/"
                class="theme-chrome-footer__brand-name"
                aria-label="{{ __('capell-theme-foundation::generic.home_for', ['name' => $section->brandName]) }}"
            >
                <span
                    class="theme-chrome-footer__brand-mark"
                    aria-hidden="true"
                ></span>
                <span>{{ $section->brandName }}</span>
            </a>

            @if (filled($section->summary ?? null))
                <p class="theme-chrome-footer__summary">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($footerColumns->isNotEmpty())
            <nav
                class="theme-chrome-footer__columns"
                aria-label="{{ __('capell-theme-foundation::generic.footer_navigation') }}"
            >
                @foreach ($footerColumns as $column)
                    <div class="theme-chrome-footer__column">
                        <h3 class="theme-chrome-footer__heading">
                            {{ $column['heading'] }}
                        </h3>
                        <ul class="theme-chrome-footer__links">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <a
                                        href="{{ data_get($link, 'url') }}"
                                        class="theme-chrome-footer__link"
                                        @if ((bool) data_get($link, 'active', false)) aria-current="page" @endif
                                    >
                                        {{ data_get($link, 'label') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        @endif
    </div>

    <div class="theme-chrome-footer__base">
        <p>
            {{
                filled($footerLegal)
                    ? $footerLegal
                    : __('capell-theme-foundation::generic.copyright_notice', [
                        'year' => date('Y'),
                        'name' => $section->brandName,
                    ])
            }}
        </p>
    </div>
</footer>
