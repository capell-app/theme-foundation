<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Listeners;

use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;

class RunTailwindAssetsOnPackageChange
{
    public function __construct(private readonly TailwindAssetsGenerator $generator) {}

    public function handleInstalled(PackageInstalled $event): void
    {
        $this->generator->generate();
    }

    public function handleUninstalled(PackageUninstalled $event): void
    {
        $this->generator->generate();
    }
}
