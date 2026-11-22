<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Listeners;

use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Support\Facades\Log;

class RunTailwindAssetsOnPackageChange
{
    public function __construct(private readonly TailwindAssetsGenerator $generator) {}

    public function handleInstalled(PackageInstalled $event): void
    {
        if (! $this->shouldRespondTo($event->package->name)) {
            return;
        }

        $this->generateAssets();
    }

    public function handleUninstalled(PackageUninstalled $event): void
    {
        if (! $this->shouldRespondTo($event->package->name)) {
            return;
        }

        $this->generateAssets();
    }

    /**
     * Core records the new lifecycle state before it dispatches the package event, so
     * Foundation Theme's own uninstall arrives when the package no longer counts as enabled
     * and must still regenerate the assets. Other packages' events are ignored while it is disabled.
     */
    private function shouldRespondTo(string $changedPackageName): bool
    {
        // Kept on the cached check, not Core package admission: the Tailwind build is a long
        // process, and admission would hold the lifecycle lock (blocking disable) for its whole run.
        return $changedPackageName === FoundationThemeServiceProvider::$packageName
            || CapellCore::isPackageEnabled(FoundationThemeServiceProvider::$packageName);
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
