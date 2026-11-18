<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Support\Themes\ActivePresetSectionVariants;
use Capell\Frontend\Contracts\FrontendContextReader;

function activePresetVariantSettings(string $theme, string $preset): ThemeRuntimeSettings
{
    return new readonly class($theme, $preset) implements ThemeRuntimeSettings
    {
        public function __construct(private string $theme, private string $preset) {}

        public function activeTheme(): string
        {
            return $this->theme;
        }

        public function activePreset(): string
        {
            return $this->preset;
        }

        public function brandProfile(): BrandProfileData
        {
            return new BrandProfileData;
        }

        public function themeOverrides(): array
        {
            return [];
        }
    };
}

beforeEach(function (): void {
    $registry = new ThemeRegistry;
    $registry->register(new ThemeDefinitionData(
        key: 'variant-fixture',
        name: 'Variant fixture',
        description: 'Variant fixture.',
        package: 'capell-app/theme-variant-fixture',
        previewImage: '/preview.jpg',
        tags: [],
        bestFit: [],
        presets: [
            new ThemePresetData('plain', 'Plain', 'Plain.', '/plain.jpg'),
            new ThemePresetData('dense', 'Dense', 'Dense.', '/dense.jpg', sectionVariants: ['hero' => 'compact']),
        ],
    ));
    app()->instance(ThemeRegistry::class, $registry);
    app()->forgetInstance(ActivePresetSectionVariants::class);
});

it('reads the section variant of the preset chosen in theme settings', function (): void {
    app()->instance(ThemeRuntimeSettings::class, activePresetVariantSettings('variant-fixture', 'dense'));
    app()->offsetUnset(FrontendContextReader::class);

    $variants = resolve(ActivePresetSectionVariants::class);

    expect($variants->for('hero'))->toBe('compact')
        ->and($variants->for('footer'))->toBeNull();
});

it('prefers the preset saved on the page theme over the settings preset', function (): void {
    app()->instance(ThemeRuntimeSettings::class, activePresetVariantSettings('variant-fixture', 'plain'));
    $theme = new Theme(['key' => 'variant-fixture', 'meta' => ['editor' => ['preset' => ['active' => 'dense']]]]);
    $context = Mockery::mock(FrontendContextReader::class);
    $context->allows('theme')->andReturn($theme);
    app()->instance(FrontendContextReader::class, $context);

    expect(resolve(ActivePresetSectionVariants::class)->for('hero'))->toBe('compact');
});

it('falls back to the first preset and returns nothing for an unknown theme', function (): void {
    app()->offsetUnset(FrontendContextReader::class);
    app()->instance(ThemeRuntimeSettings::class, activePresetVariantSettings('variant-fixture', 'missing'));

    expect(resolve(ActivePresetSectionVariants::class)->for('hero'))->toBeNull();

    app()->forgetInstance(ActivePresetSectionVariants::class);
    app()->instance(ThemeRuntimeSettings::class, activePresetVariantSettings('not-registered', 'dense'));

    expect(resolve(ActivePresetSectionVariants::class)->for('hero'))->toBeNull();
});
