<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Listeners;

use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Support\Facades\Log;

class RunTailwindAssetsOnPackageChange
{
    public function __construct(private readonly TailwindAssetsGenerator $generator) {}

    public function handleInstalled(PackageInstalled $event): void
    {
        $this->generateAssets();
    }

    public function handleUninstalled(PackageUninstalled $event): void
    {
        $this->generateAssets();
    }

    /**
     * Production releases are read-only builds, so source CSS is never patched there.
     */
    private function generateAssets(): void
    {
        if (app()->environment('production')) {
            Log::notice('Skipping Tailwind asset generation after a package change in production.');

            return;
        }

        $unwritablePath = $this->generator->unwritableOutputPath();

        if ($unwritablePath !== null) {
            Log::notice('Skipping Tailwind asset generation after a package change because an output path is not writable.', [
                'path' => $unwritablePath,
            ]);

            return;
        }

        $this->generator->generate();
    }
}
