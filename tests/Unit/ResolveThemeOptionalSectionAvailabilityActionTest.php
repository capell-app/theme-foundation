<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Actions\ResolveThemeOptionalSectionAvailabilityAction;

it('allows ordinary sections and gates optional sections through package availability', function (): void {
    CapellCore::forcePackageInstalled('capell-app/newsletter', false);

    $packageBySection = ['newsletter' => 'capell-app/newsletter'];

    expect(ResolveThemeOptionalSectionAvailabilityAction::run('hero', $packageBySection))->toBeTrue()
        ->and(ResolveThemeOptionalSectionAvailabilityAction::run('newsletter', $packageBySection))->toBeFalse();
});
