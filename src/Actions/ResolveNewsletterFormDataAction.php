<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\NewsletterFormData;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveNewsletterFormDataAction
{
    use AsFake;
    use AsObject;

    private const string SUBSCRIBE_ROUTE = 'capell-newsletter.subscribe';

    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(
        string $fallbackAction = '#newsletter',
        string $source = 'public_newsletter',
    ): NewsletterFormData {
        $source = $this->normalizeSource($source);

        if (! $this->router->has(self::SUBSCRIBE_ROUTE)) {
            return new NewsletterFormData(
                action: $this->normalizeFallbackAction($fallbackAction),
                method: 'get',
                source: $source,
                wired: false,
            );
        }

        return new NewsletterFormData(
            action: $this->urlGenerator->route(self::SUBSCRIBE_ROUTE, [], false),
            method: 'post',
            source: $source,
            wired: true,
        );
    }

    private function normalizeFallbackAction(string $fallbackAction): string
    {
        $fallbackAction = trim($fallbackAction);

        if ($fallbackAction === '') {
            return '#newsletter';
        }

        if (str_starts_with($fallbackAction, '#') || str_starts_with($fallbackAction, '/')) {
            return $fallbackAction;
        }

        $scheme = parse_url($fallbackAction, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true)
            ? $fallbackAction
            : '#newsletter';
    }

    private function normalizeSource(string $source): string
    {
        $source = trim($source);

        if ($source === '' || mb_strlen($source) > 120) {
            return 'public_newsletter';
        }

        return preg_match('/^[a-zA-Z0-9._:-]+$/', $source) === 1
            ? $source
            : 'public_newsletter';
    }
}
