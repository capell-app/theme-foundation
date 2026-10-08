<?php

declare(strict_types=1);

use Capell\Core\Data\PackageData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

it('regenerates Tailwind assets when the running Artisan application predates the theme command', function (): void {
    $filesystem = new Filesystem;
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-late-artisan');
    $target = $targetDirectory . '/frontend.css';
    $filesystem->deleteDirectory($targetDirectory);

    config(['capell-theme-foundation.tailwind.output_css' => $target]);
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);

    // The first `capell:install` run requires this package through a Composer
    // child process. Its provider is registered after the running Artisan
    // application was built, so commands it queues never reach that application.
    ConsoleApplication::forgetBootstrappers();
    resolve(Kernel::class)->setArtisan(new ConsoleApplication(app(), app('events'), app()->version()));

    expect(Artisan::all())->not->toHaveKey('capell:frontend-tailwind-assets');

    try {
        resolve(RunTailwindAssetsOnPackageChange::class)->handleInstalled(new PackageInstalled(new PackageData(
            name: FoundationThemeServiceProvider::$packageName,
            type: PackageTypeEnum::Theme,
            serviceProviderClass: FoundationThemeServiceProvider::class,
            path: __DIR__,
        )));

        expect($filesystem->exists($target))->toBeTrue()
            ->and(Str::contains($filesystem->get($target), [
                TailwindAssetsGenerator::SPLIT_GENERATION_MARKER,
                TailwindAssetsGenerator::COMBINED_GENERATION_MARKER,
            ]))->toBeTrue();
    } finally {
        $filesystem->deleteDirectory($targetDirectory);
    }
});
