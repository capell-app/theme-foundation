<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\HasThemeIntegrationEvidenceAction;

it('recognises shared and theme-owned optional extension integrations', function (): void {
    $packages = dirname(__DIR__, 4) . '/packages';

    expect(HasThemeIntegrationEvidenceAction::run($packages . '/theme-agency', 'capell-app/form-builder'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-agency', 'capell-app/newsletter'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-business', 'capell-app/blog'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-business', 'capell-app/bookings'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-almanac', 'capell-app/events'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-almanac', 'capell-app/search'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-almanac', 'capell-app/newsletter'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-almanac', 'capell-app/comments'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-concierge', 'capell-app/widget-countdown'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-concierge', 'capell-app/widget-location-map'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-paperdesk', 'capell-app/comments'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-paperdesk', 'capell-app/events'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-paperdesk', 'capell-app/search'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-paperdesk', 'capell-app/widget-slideshow'))->toBeTrue();
});

it('rejects optional extension claims without source evidence', function (): void {
    $themeDirectory = dirname(__DIR__, 4) . '/packages/theme-events';

    expect(HasThemeIntegrationEvidenceAction::run($themeDirectory, 'capell-app/newsletter'))->toBeFalse()
        ->and(HasThemeIntegrationEvidenceAction::run($themeDirectory, 'capell-app/shopify-commerce'))->toBeFalse()
        ->and(HasThemeIntegrationEvidenceAction::run($themeDirectory, 'capell-app/form-builder'))->toBeFalse();
});
