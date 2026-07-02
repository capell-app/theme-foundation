<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use Capell\FoundationTheme\Data\ThemeDemoInstallData;

interface InstallsThemeDemo
{
    public function handle(ThemeDemoInstallData $data): int;
}
