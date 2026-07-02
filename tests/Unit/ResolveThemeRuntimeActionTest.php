<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Manifest\CapellManifestData;
use Capell\Core\ThemeStudio\Actions\ResolveThemeRuntimeAction;
use Capell\Core\ThemeStudio\Assets\ThemeTokenStore;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Exceptions\ThemePresetNotFoundException;
use Capell\Core\ThemeStudio\Preview\ThemePreviewContext;
use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;

it('layers parent defaults before child defaults and applies database overrides last', function (): void {
    CapellCore::registerManifestPackage(CapellManifestData::fromArray([
        'manifest-version' => 3,
        'name' => 'vendor/base-theme',
        'slug' => 'base-theme',
        'displayName' => 'Base Theme',
        'kind' => 'theme',
        'capellApiVersion' => '^4.0',
        'version' => '1.0.0',
        'product' => [
            'group' => 'Theme',
            'tier' => 'free',
        ],
        'performance' => [
            'cacheSafety' => [
                'cacheable' => true,
                'variesBy' => [],
                'sensitiveOutput' => false,
                'invalidationSources' => [],
                'queueInvalidation' => false,
            ],
        ],
        'surfaces' => ['frontend'],
        'themeKey' => 'base',
    ]));
    CapellCore::registerManifestPackage(CapellManifestData::fromArray([
        'manifest-version' => 3,
        'name' => 'vendor/child-theme',
        'slug' => 'child-theme',
        'displayName' => 'Child Theme',
        'kind' => 'theme',
        'capellApiVersion' => '^4.0',
        'version' => '1.0.0',
        'product' => [
            'group' => 'Theme',
            'tier' => 'free',
        ],
        'performance' => [
            'cacheSafety' => [
                'cacheable' => true,
                'variesBy' => [],
                'sensitiveOutput' => false,
                'invalidationSources' => [],
                'queueInvalidation' => false,
            ],
        ],
        'surfaces' => ['frontend'],
        'dependencies' => [
            'requires' => ['vendor/base-theme'],
        ],
        'extends' => 'vendor/base-theme',
        'themeKey' => 'child',
    ]));

    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);
    $renderer = new BladeThemeRenderer('child', 'missing-view', []);

    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'base',
            name: 'Base',
            description: 'Base theme',
            package: 'vendor/base-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'base-default',
                    name: 'Base default',
                    description: '',
                    previewImage: '',
                    values: [
                        'primaryColor' => '#111111',
                        'accentColor' => '#222222',
                        'cardStyle' => 'bordered',
                    ],
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );
    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'child',
            name: 'Child',
            description: 'Child theme',
            package: 'vendor/child-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'launch',
                    name: 'Launch',
                    description: '',
                    previewImage: '',
                    values: [
                        'accentColor' => '#333333',
                        'headingFont' => 'sora',
                    ],
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );

    app()->instance(ThemeTokenStore::class, new ThemeTokenStore(sys_get_temp_dir() . '/capell-theme-runtime-test'));

    $runtime = ResolveThemeRuntimeAction::run(
        activeTheme: 'child',
        activePreset: 'launch',
        brand: new BrandProfileData,
        themeOverrides: [
            'base' => ['primaryColor' => '#444444'],
            'child' => ['accentColor' => '#555555'],
        ],
    );

    expect($runtime->brand->primaryColor)->toBe('#444444')
        ->and($runtime->brand->accentColor)->toBe('#555555')
        ->and($runtime->brand->cardStyle)->toBe('bordered')
        ->and($runtime->brand->headingFont)->toBe('sora');
});

it('falls back to the theme default preset when saved theme studio settings are stale', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);
    $renderer = new BladeThemeRenderer('saas', 'missing-view', []);

    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'saas',
            name: 'SaaS',
            description: 'SaaS theme',
            package: 'vendor/saas-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'saas',
                    name: 'SaaS',
                    description: '',
                    previewImage: '',
                    values: [
                        'primaryColor' => '#2563eb',
                    ],
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );

    app()->instance(ThemeTokenStore::class, new ThemeTokenStore(sys_get_temp_dir() . '/capell-theme-runtime-stale-preset-test'));

    $runtime = ResolveThemeRuntimeAction::run(
        activeTheme: 'saas',
        activePreset: 'boardroom',
        brand: new BrandProfileData,
    );

    expect($runtime->presetKey)->toBe('saas')
        ->and($runtime->preset->key)->toBe('saas')
        ->and($runtime->brand->primaryColor)->toBe('#2563eb');
});

it('keeps invalid preview preset links explicit', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);
    $renderer = new BladeThemeRenderer('saas', 'missing-view', []);

    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'saas',
            name: 'SaaS',
            description: 'SaaS theme',
            package: 'vendor/saas-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'saas',
                    name: 'SaaS',
                    description: '',
                    previewImage: '',
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );

    app()->instance(ThemeTokenStore::class, new ThemeTokenStore(sys_get_temp_dir() . '/capell-theme-runtime-preview-preset-test'));

    ResolveThemeRuntimeAction::run(
        activeTheme: 'saas',
        activePreset: 'saas',
        brand: new BrandProfileData,
        previewContext: new ThemePreviewContext(themeKey: 'saas', presetKey: 'missing', previewing: true),
    );
})->throws(ThemePresetNotFoundException::class);
