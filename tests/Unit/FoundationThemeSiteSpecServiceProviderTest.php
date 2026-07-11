<?php

declare(strict_types=1);

use Capell\Core\Support\Themes\ThemeInstallDefaultsRegistry;
use Capell\FoundationTheme\Providers\FoundationThemeSiteSpecServiceProvider;

it('registers Foundation site spec defaults with the core extension point', function (): void {
    app()->register(FoundationThemeSiteSpecServiceProvider::class);

    $registry = resolve(ThemeInstallDefaultsRegistry::class);

    expect($registry->has('default'))->toBeTrue()
        ->and($registry->has('unknown'))->toBeFalse();
});
