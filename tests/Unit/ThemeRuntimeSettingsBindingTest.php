<?php

declare(strict_types=1);

use Capell\Admin\Support\Extensions\ExtensionManagementSurfaceRegistry;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;

it('reuses the theme runtime settings instance within a request', function (): void {
    $first = resolve(ThemeRuntimeSettings::class);
    $second = resolve(ThemeRuntimeSettings::class);

    expect($second)->toBe($first);
});

it('registers foundation theme settings and extension settings surface', function (): void {
    $reflection = new ReflectionMethod(FoundationThemeServiceProvider::class, 'registerSettingsSchemas');
    $reflection->invoke(new FoundationThemeServiceProvider(app()));

    $settingsRegistry = resolve(SettingsSchemaRegistry::class);

    expect($settingsRegistry->getSettingsClass('theme_foundation'))->toBe(FoundationThemeSettings::class)
        ->and($settingsRegistry->getSchemas('theme_foundation'))->toContain(FoundationThemeSettingsSchema::class);

    $surfaces = resolve(ExtensionManagementSurfaceRegistry::class)
        ->surfacesForPackage(FoundationThemeServiceProvider::$packageName);

    expect($surfaces[0]->settingsGroup ?? null)->toBe('theme_foundation');
});
