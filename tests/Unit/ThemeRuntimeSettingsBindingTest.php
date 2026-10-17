<?php

declare(strict_types=1);

use Capell\Admin\Support\Extensions\ExtensionManagementSurfaceRegistry;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;

it('reuses the theme runtime settings instance within a request', function (): void {
    $first = resolve(ThemeRuntimeSettings::class);
    $second = resolve(ThemeRuntimeSettings::class);

    expect($second)->toBe($first);
});

it('registers foundation theme settings and extension settings surface', function (): void {
    InstalledRuntimeState::run(function (): void {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true, sourceRoot: dirname(__DIR__, 2));

        $settingsRegistry = resolve(SettingsSchemaRegistry::class);

        expect($settingsRegistry->getSettingsClass('theme_foundation'))->toBe(FoundationThemeSettings::class)
            ->and($settingsRegistry->getSchemas('theme_foundation'))->toContain(FoundationThemeSettingsSchema::class);

        $surfaces = resolve(ExtensionManagementSurfaceRegistry::class)
            ->surfacesForPackage(FoundationThemeServiceProvider::$packageName);

        expect($surfaces[0]->settingsGroup ?? null)->toBe('theme_foundation');
    });
});
