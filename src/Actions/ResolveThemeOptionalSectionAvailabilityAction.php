<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Facades\CapellCore;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(string $sectionType, array<string, string> $packageBySection)
 */
final class ResolveThemeOptionalSectionAvailabilityAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, string>  $packageBySection
     */
    public function handle(string $sectionType, array $packageBySection): bool
    {
        $packageName = $packageBySection[$sectionType] ?? null;

        return $packageName === null || CapellCore::isPackageAvailable($packageName);
    }
}
