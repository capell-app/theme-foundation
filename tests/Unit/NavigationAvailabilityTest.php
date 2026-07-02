<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Support\NavigationAvailability;

it('does not treat composer-installed navigation classes as available when the extension is not installed', function (): void {
    CapellCore::forcePackageInstalled('capell-app/navigation', false);

    expect(NavigationAvailability::check())->toBeFalse();
});

it('treats navigation as available when the extension is installed', function (): void {
    CapellCore::forcePackageInstalled('capell-app/navigation');

    expect(NavigationAvailability::check())->toBeTrue();
});
