<?php

declare(strict_types=1);

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Filesystem\Filesystem;

/*
 * Guards Wave 6.1/6.2: theme-css:<key> conditioned imports must compile into
 * their own file when capell-theme-foundation.tailwind.split_theme_css is
 * enabled, and must be indistinguishable from an unconditioned import when
 * the flag is off (the default) — so shipping this option changes nothing
 * until a host app opts in.
 */

it('keeps conditioned imports in the shared bundle when the split flag is off', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-split-off');
    $targetPath = $targetDirectory . '/frontend.css';
    $packageName = 'vendor/split-flag-off-theme';

    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(new VendorAssetData(
        type: VendorAssetEnum::TailwindImport,
        value: 'resources/css/theme-split-flag-off.css',
        packageName: $packageName,
        condition: 'theme-css:split-flag-off',
    ));

    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);

    expect($paths)->toBe([$targetPath])
        ->and((new Filesystem)->get($targetPath))->toContain('theme-split-flag-off.css');
});

it('splits conditioned imports into their own file when the split flag is on', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-split-on');
    $targetPath = $targetDirectory . '/frontend.css';
    $packageName = 'vendor/split-flag-on-theme';

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $targetDirectory . '/themes',
    ]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(new VendorAssetData(
        type: VendorAssetEnum::TailwindImport,
        value: 'resources/css/theme-split-flag-on.css',
        packageName: $packageName,
        condition: 'theme-css:split-flag-on',
    ));

    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);

    $themePath = $targetDirectory . '/themes/split-flag-on.css';

    expect($paths)->toBe([$targetPath, $themePath])
        ->and((new Filesystem)->get($targetPath))
        ->toContain(TailwindAssetsGenerator::SPLIT_GENERATION_MARKER)
        ->toStartWith('/* ' . TailwindAssetsGenerator::SPLIT_GENERATION_MARKER . ' */' . PHP_EOL)
        ->toContain('@import "tailwindcss";' . PHP_EOL)
        ->not->toContain('theme-split-flag-on.css')
        ->and((new Filesystem)->get($themePath))
        ->not->toContain('@import "tailwindcss";')
        ->toContain('theme-split-flag-on.css');
});

it('marks a combined regeneration so rollback does not retain a stale split stylesheet', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-split-transition');
    $targetPath = $targetDirectory . '/frontend.css';
    $packageName = 'vendor/split-transition-theme';

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $targetDirectory . '/themes',
    ]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(new VendorAssetData(
        type: VendorAssetEnum::TailwindImport,
        value: 'resources/css/theme-split-transition.css',
        packageName: $packageName,
        condition: 'theme-css:split-transition',
    ));

    $generator = new TailwindAssetsGenerator(new Filesystem);
    $generator->generate($targetPath);

    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);
    $paths = $generator->generate($targetPath);
    $sharedCss = (new Filesystem)->get($targetPath);

    expect($paths)->toBe([$targetPath])
        ->and($sharedCss)->toContain(TailwindAssetsGenerator::COMBINED_GENERATION_MARKER)
        ->and($sharedCss)->toContain('theme-split-transition.css');
});

it('keeps Tailwind core in the shared entrypoint so split themes retain cascade order without duplicate layers', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-split-cascade');
    $targetPath = $targetDirectory . '/frontend.css';
    $packageName = 'vendor/split-cascade-theme';

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $targetDirectory . '/themes',
    ]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(new VendorAssetData(
        type: VendorAssetEnum::TailwindImport,
        value: 'resources/css/theme-split-cascade.css',
        packageName: $packageName,
        condition: 'theme-css:split-cascade',
    ));

    (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);

    $filesystem = new Filesystem;
    $sharedCss = $filesystem->get($targetPath);
    $themeCss = $filesystem->get($targetDirectory . '/themes/split-cascade.css');

    expect(substr_count($sharedCss, '@import "tailwindcss";'))->toBe(1)
        ->and($themeCss)->toStartWith('@import "')
        ->and($themeCss)->toContain('theme-split-cascade.css')
        ->and(substr_count($themeCss, '@import "tailwindcss";'))->toBe(0);
});

it('leaves the base bundle untouched by unconditioned imports either way', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-split-unconditioned');
    $targetPath = $targetDirectory . '/frontend.css';
    $packageName = 'vendor/split-unconditioned-theme';

    config(['capell-theme-foundation.tailwind.split_theme_css' => true]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(VendorAssetData::tailwindImport('resources/css/unconditioned.css', $packageName));

    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);

    expect($paths)->toBe([$targetPath])
        ->and((new Filesystem)->get($targetPath))->toContain('unconditioned.css');
});
