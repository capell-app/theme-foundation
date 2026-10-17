<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

it('ships a publishable homepage preview for every declared theme image', function (): void {
    $publishPaths = ServiceProvider::pathsToPublish(FoundationThemeServiceProvider::class, 'capell-theme-preview-images');
    $checked = 0;
    foreach (glob(dirname(__DIR__, 3) . '/theme-*/capell.json') ?: [] as $manifestPath) {
        $manifest = capell_json_file_array($manifestPath);
        $providers = data_get($manifest, 'providers.metadata', []);
        throw_unless(is_array($providers), RuntimeException::class, 'Theme metadata providers must be a list.');
        foreach ($providers as $provider) {
            if (! is_string($provider) || ! is_callable([$provider, 'definition'])) {
                continue;
            }
            $definition = $provider::definition();
            throw_unless($definition instanceof ThemeDefinitionData, RuntimeException::class, 'Expected a public theme definition.');
            $preview = $definition->previewImage;
            if (pathinfo($preview, PATHINFO_EXTENSION) !== 'webp') {
                continue;
            }
            $source = array_search(public_path(ltrim($preview, '/')), $publishPaths, true);
            expect($source)->toBeString($definition->key);
            throw_unless(is_string($source), RuntimeException::class, 'The declared preview must be publishable.');
            $dimensions = @getimagesize($source);
            expect($dimensions)->toBeArray($definition->key);
            throw_unless(is_array($dimensions), RuntimeException::class, 'The preview must be a valid image.');
            expect($dimensions[0])->toBeGreaterThan(0)
                ->and($dimensions[1])->toBeGreaterThan(0)
                ->and($dimensions['mime'])->toBe('image/webp');
            $checked++;
        }
    }
    expect($checked)->toBeGreaterThan(0);
});

it('publishes the fleet previews at the URLs declared to theme consumers', function (): void {
    $directory = sys_get_temp_dir() . '/theme-previews-' . bin2hex(random_bytes(8));
    $paths = ServiceProvider::pathsToPublish(FoundationThemeServiceProvider::class, 'capell-theme-preview-images');
    $originalPublicPath = public_path();
    app()->usePublicPath($directory);
    try {
        app()->register(FoundationThemeServiceProvider::class, true);
        $this->artisan('vendor:publish', ['--provider' => FoundationThemeServiceProvider::class, '--tag' => 'capell-theme-preview-images', '--force' => true])->assertSuccessful();
        expect($paths)->not->toBeEmpty();
        foreach ($paths as $source => $destination) {
            $published = $directory . substr($destination, strlen($originalPublicPath));
            expect($published)->toBeFile()
                ->and(hash_file('sha256', $published))->toBe(hash_file('sha256', $source));
        }
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
});
