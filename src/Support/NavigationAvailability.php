<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;

final class NavigationAvailability
{
    private const string PackageName = 'capell-app/navigation';

    public static function check(): bool
    {
        return CapellCore::isPackageInstalled(self::PackageName)
            && class_exists(NavigationLoader::class)
            && class_exists(NavigationHandle::class)
            && class_exists(Navigation::class)
            && class_exists(BuildNavigationRenderModelAction::class)
            && class_exists(NavigationRenderContextData::class);
    }
}
