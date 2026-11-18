<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components;

use Capell\FoundationTheme\Support\Themes\DeclaredEmbedOrigins;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The one Foundation consent control for third-party embeds. The iframe is
 * held in an inert template and only inserted after the visitor presses the
 * load button, and only when the source origin is declared for the widget in
 * the active theme's manifest. Visitors who never consent keep the fallback
 * link, which needs no consent.
 */
final class ConsentEmbed extends Component
{
    public readonly bool $permitted;

    public readonly string $provider;

    public readonly string $fallbackHref;

    public function __construct(
        DeclaredEmbedOrigins $origins,
        public readonly string $widget,
        public readonly string $src,
        public readonly string $title,
        string $fallbackUrl = '',
        public readonly string $fallbackLabel = '',
        public readonly string $caption = '',
    ) {
        $this->permitted = $origins->permits($widget, $src);
        $origin = DeclaredEmbedOrigins::originOf($src);
        $this->provider = $origin === null ? '' : (string) parse_url($origin, PHP_URL_HOST);
        $this->fallbackHref = preg_match('#^https?://#i', $fallbackUrl) === 1 ? $fallbackUrl : '';
    }

    public function render(): View
    {
        return view('capell-theme-foundation::embeds.consent');
    }
}
