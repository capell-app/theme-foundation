<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\HasThemeIntegrationEvidenceAction;

it('recognises shared and theme-owned optional extension integrations', function (): void {
    $packages = dirname(__DIR__, 4) . '/packages';

    expect(HasThemeIntegrationEvidenceAction::run($packages . '/theme-agency', 'capell-app/form-builder'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-agency', 'capell-app/newsletter'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-business', 'capell-app/blog'))->toBeTrue()
        ->and(HasThemeIntegrationEvidenceAction::run($packages . '/theme-business', 'capell-app/bookings'))->toBeTrue();
});

it('rejects optional extension claims without source evidence', function (): void {
    $themeDirectory = dirname(__DIR__, 4) . '/packages/theme-events';

    expect(HasThemeIntegrationEvidenceAction::run($themeDirectory, 'capell-app/newsletter'))->toBeFalse()
        ->and(HasThemeIntegrationEvidenceAction::run($themeDirectory, 'capell-app/shopify-commerce'))->toBeFalse()
        ->and(HasThemeIntegrationEvidenceAction::run(dirname(__DIR__, 4) . '/packages/theme-awards', 'capell-app/form-builder'))->toBeFalse();
});
