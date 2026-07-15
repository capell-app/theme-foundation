<?php

declare(strict_types=1);

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Console\Commands\GenerateTailwindAssetsCommand;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

it('owns the frontend Tailwind command and generates conditioned theme CSS', function (): void {
    $filesystem = new Filesystem;
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-command-registration');
    $frontendPath = $targetDirectory . '/frontend.css';
    $themePath = $targetDirectory . '/themes/default.css';
    $packageName = 'vendor/conditioned-foundation-theme';

    $filesystem->deleteDirectory($targetDirectory);

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $targetDirectory . '/themes',
    ]);

    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(new VendorAssetData(
        type: VendorAssetEnum::TailwindImport,
        value: 'resources/css/default-theme.css',
        packageName: $packageName,
        condition: 'theme-css:default',
    ));

    $command = Artisan::all()['capell:frontend-tailwind-assets'] ?? null;

    expect($command)->toBeInstanceOf(GenerateTailwindAssetsCommand::class);

    $this->artisan('capell:frontend-tailwind-assets', ['--output-path' => $frontendPath])
        ->assertSuccessful()
        ->expectsOutputToContain('Generated Tailwind assets at ' . $frontendPath)
        ->expectsOutputToContain('Generated Tailwind assets at ' . $themePath);

    expect($filesystem->exists($frontendPath))->toBeTrue()
        ->and($filesystem->exists($themePath))->toBeTrue()
        ->and($filesystem->get($frontendPath))->not->toContain('default-theme.css')
        ->and($filesystem->get($themePath))->toContain('default-theme.css');
});
