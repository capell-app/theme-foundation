{{--
    Shared vertical-theme chrome: site footer.
    Rendered for every theme whose ServiceProvider points its 'footer'
    ViewSectionRenderer at `capell-theme-foundation::theme.chrome.footer`.
    Styled with the `theme-chrome-footer__*` plain-CSS classes shipped in
    `resources/css/theme/chrome.css`, driven by brand `--theme-*` properties.
    Data shape (FooterData): brandName, summary, columns[{heading,links[{label,url}]}].
--}}
<footer
    id="footer"
    class="theme-chrome-footer"
>
    <h2 class="theme-chrome-footer__sr">
        {{ __('capell-theme-foundation::generic.footer') }}
    </h2>

    <div class="theme-chrome-footer__inner">
        <div class="theme-chrome-footer__brand">
            <p class="theme-chrome-footer__brand-name">
                {{ $section->brandName }}
            </p>

            @if ($section->summary)
                <p class="theme-chrome-footer__summary">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($section->columns !== [])
            <nav
                class="theme-chrome-footer__columns"
                aria-label="{{ __('capell-theme-foundation::generic.footer_navigation') }}"
            >
                @foreach ($section->columns as $column)
                    <div class="theme-chrome-footer__column">
                        <h3 class="theme-chrome-footer__heading">
                            {{ $column['heading'] }}
                        </h3>
                        <ul class="theme-chrome-footer__links">
                            @foreach ($column['links'] as $link)
                                @if (! empty($link['url']) && ! empty($link['label']))
                                    <li>
                                        <a
                                            href="{{ $link['url'] }}"
                                            class="theme-chrome-footer__link"
                                        >
                                            {{ $link['label'] }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        @endif
    </div>
</footer>
