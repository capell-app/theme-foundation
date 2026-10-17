<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\Contracts\RegistersTailwindAssets;
use Capell\Core\Support\Tailwind\TailwindAssetsRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

final class UnsafeTailwindAssetsFixtureProvider extends ServiceProvider implements RegistersTailwindAssets
{
    #[Override]
    public function registerTailwindAssets(TailwindAssetsRegistry $registry): void
    {
        $registry->registerThemeColor('safe-provider', '#ffffff');
        $registry->registerThemeColor('bad;color', '#000000');
        $registry->registerThemeColor('remote', 'url(https://example.test/color.svg)');
        $registry->registerThemeColor('injected', 'red; background: black');
    }
}
