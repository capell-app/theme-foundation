<?php

declare(strict_types=1);

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Illuminate\Support\ServiceProvider;

it('ships a publishable homepage preview for every declared theme image', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $providerFiles = array_merge(
        glob($packagesRoot . '/theme-*/src/*ThemeServiceProvider.php') ?: [],
        glob($packagesRoot . '/theme-*/src/Providers/*ThemeServiceProvider.php') ?: [],
    );

    foreach ($providerFiles as $providerFile) {
        $source = file_get_contents($providerFile);

        if (! is_string($source) || ! preg_match("/previewImage:\s*'\/vendor\/capell\/themes\/([^']+\.png)'/", $source, $matches)) {
            continue;
        }

        $themeKey = pathinfo($matches[1], PATHINFO_FILENAME);
        $previewSource = $packagesRoot . '/theme-' . $themeKey . '/docs/screenshots/' . $themeKey . '-homepage.png';

        expect(is_file($previewSource))->toBeTrue(
            "Theme [{$themeKey}] declares a PNG preview but does not ship its homepage capture.",
        );
    }
});

it('registers the fleet preview images for vendor publishing', function (): void {
    $publishPaths = ServiceProvider::pathsToPublish(
        FoundationThemeServiceProvider::class,
        'capell-theme-preview-images',
    );

    expect($publishPaths)->not->toBeEmpty()
        ->and($publishPaths)->toHaveKey(
            dirname(__DIR__, 3) . '/theme-foundation/docs/screenshots/foundation-homepage.png',
        );

    foreach ($publishPaths as $source => $destination) {
        expect(is_file($source))->toBeTrue()
            ->and($destination)->toEndWith('.png');
    }
});
