<?php

declare(strict_types=1);

it('owns the opinionated public head behavior', function (): void {
    $component = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/custom.blade.php');
    $tokens = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php');
    $tokenAction = file_get_contents(dirname(__DIR__, 2) . '/src/Actions/ResolveFoundationThemeTokensAction.php');

    expect($component)->toContain('localStorage.theme')
        ->and($component)->toContain('updateHeaderSticky')
        ->and($component)->toContain('<x-capell::app.head.tokens />')
        ->and($tokens)->toContain('Frontend::getFrontendData(\'foundation.theme.tokens\')')
        ->and($tokens)->toContain('ResolveFoundationThemeTokensAction::run(resolveSettings: false)')
        ->and($tokens)->toContain('--color-brand')
        ->and($tokenAction)->toContain('DefaultColorEnum::getKeyValues()')
        ->and($tokenAction)->toContain('->merge($theme instanceof Theme && is_array($theme->colors) ? $theme->colors : [])')
        ->and($tokenAction)->toContain('$linkColorActiveMeta = $theme instanceof Theme ? $theme->getMeta(\'link_color_active\') : null')
        ->and($tokenAction)->toContain('resolve(FoundationThemeSettings::class)')
        ->and($tokens)->toContain('--foundation-page-bg')
        ->and($tokens)->toContain('--foundation-body-fg')
        ->and($tokens)->toContain('--foundation-section-bg')
        ->and($tokens)->toContain('--foundation-section-muted-bg')
        ->and($tokens)->toContain('--foundation-heading-fg')
        ->and($tokens)->toContain('--foundation-muted-fg')
        ->and($tokens)->toContain('--foundation-inverse-bg')
        ->and($tokens)->toContain('--foundation-focus-ring')
        ->and($tokens)->toContain('--foundation-border')
        ->and($tokens)->toContain('--foundation-section-spacing')
        ->and($tokens)->toContain('--foundation-band-bg')
        ->and($tokens)->toContain('--foundation-image-border')
        ->and($tokens)->toContain('--foundation-image-radius')
        ->and($tokens)->toContain('--foundation-widget-gap')
        ->and($tokens)->toContain('--foundation-heading-size-h1')
        ->and($tokens)->toContain('--foundation-heading-line-height');
});

it('maps foundation design settings into public CSS hooks', function (): void {
    $settings = file_get_contents(dirname(__DIR__, 2) . '/src/Settings/FoundationThemeSettings.php');
    $schema = file_get_contents(dirname(__DIR__, 2) . '/src/Filament/Settings/FoundationThemeSettingsSchema.php');
    $styles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');
    $tokens = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php');
    $tokenAction = file_get_contents(dirname(__DIR__, 2) . '/src/Actions/ResolveFoundationThemeTokensAction.php');

    expect($settings)->toContain('public string $page_background_color')
        ->and($settings)->toContain('public string $border_color')
        ->and($settings)->toContain('public string $band_background_color')
        ->and($settings)->toContain('public string $image_border_color')
        ->and($settings)->toContain('public string $dark_page_background_color')
        ->and($settings)->toContain('public string $dark_primary_action_color')
        ->and($settings)->toContain('public string $dark_image_border_color')
        ->and($settings)->toContain('public string $image_radius')
        ->and($settings)->toContain('SECTION_SPACING_OPTIONS')
        ->and($settings)->toContain('WIDGET_GAP_OPTIONS')
        ->and($settings)->toContain('HEADING_SCALE_OPTIONS')
        ->and($settings)->toContain('public string $heading_scale')
        ->and($settings)->toContain('public string $responsive_repeatable_layout')
        ->and($schema)->toContain("ColorPicker::make('page_background_color')")
        ->and($schema)->toContain("ColorPicker::make('border_color')")
        ->and($schema)->toContain("ColorPicker::make('band_background_color')")
        ->and($schema)->toContain("ColorPicker::make('image_border_color')")
        ->and($schema)->toContain("ColorPicker::make('dark_page_background_color')")
        ->and($schema)->toContain("ColorPicker::make('dark_primary_action_color')")
        ->and($schema)->toContain("ColorPicker::make('dark_image_border_color')")
        ->and($schema)->toContain("Select::make('image_radius')")
        ->and($schema)->toContain("Select::make('section_spacing')")
        ->and($schema)->toContain("Select::make('widget_gap')")
        ->and($schema)->toContain("Select::make('heading_scale')")
        ->and($schema)->toContain("Select::make('responsive_repeatable_layout')")
        ->and($tokenAction)->toContain('sectionSpacingCssValue()')
        ->and($tokenAction)->toContain('headingScaleCssValues()')
        ->and($tokenAction)->toContain("darkPageBackground: \$this->settingColor(\$settings, 'dark_page_background_color', '#0b1716')")
        ->and($tokenAction)->toContain("darkPrimaryAction: \$this->settingColor(\$settings, 'dark_primary_action_color', '#79d7c2')")
        ->and($tokenAction)->toContain('widgetGapCssValue()')
        ->and($tokenAction)->toContain('responsiveRepeatableLayoutKey()')
        ->and($tokens)->toContain('.dark:root')
        ->and($tokens)->toContain('--foundation-body-fg: #f8fafc')
        ->and($tokens)->toContain('--foundation-page-bg: {{ $tokens->darkPageBackground }}')
        ->and($tokens)->toContain('--foundation-band-alt-bg')
        ->and($tokens)->toContain('--foundation-widget-gap')
        ->and($tokens)->toContain('--foundation-heading-size-h1')
        ->and($tokens)->toContain('--foundation-heading-size-h2')
        ->and($tokens)->toContain('--foundation-heading-size-h3')
        ->and($tokens)->toContain('--foundation-heading-line-height')
        ->and($styles)->toContain('background: var(--foundation-page-bg)')
        ->and($styles)->toContain('color: var(--foundation-body-fg)')
        ->and($styles)->toContain('var(--foundation-section-spacing)')
        ->and($styles)->toContain('var(--foundation-heading-size-h1)')
        ->and($styles)->toContain('var(--foundation-card-bg)')
        ->and($styles)->toContain('var(--foundation-primary-action)')
        ->and($styles)->toContain('var(--foundation-image-border)')
        ->and($styles)->toContain('var(--foundation-image-radius)');
});

it('ships an inherited theme-wide responsive repeatable item policy', function (): void {
    $migration = file_get_contents(dirname(__DIR__, 2) . '/database/settings/2026_07_15_210000_add_theme_foundation_responsive_repeatable_layout.php');
    $migrationProvider = file_get_contents(dirname(__DIR__, 2) . '/src/Settings/FoundationThemeSettingsMigrationProvider.php');
    $assetView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/asset/index.blade.php');

    expect($migration)
        ->toContain('theme_foundation.responsive_repeatable_layout')
        ->toContain('desktop-grid-mobile-carousel')
        ->and($migrationProvider)->toContain('2026_07_15_210000_add_theme_foundation_responsive_repeatable_layout')
        ->and($assetView)->toContain('responsiveRepeatableLayout');
});

it('ships additive settings defaults for the dark foundation token layer', function (): void {
    $migration = file_get_contents(dirname(__DIR__, 2) . '/database/settings/2026_06_07_000001_add_theme_foundation_dark_design_tokens.php');
    $translations = file_get_contents(dirname(__DIR__, 2) . '/resources/lang/en/form.php');
    $tokenData = file_get_contents(dirname(__DIR__, 2) . '/src/Data/FoundationThemeTokensData.php');

    expect($migration)
        ->toContain('theme_foundation.dark_page_background_color')
        ->toContain('theme_foundation.dark_surface_background_color')
        ->toContain('theme_foundation.dark_card_background_color')
        ->toContain('theme_foundation.dark_primary_action_color')
        ->toContain('theme_foundation.dark_image_border_color')
        ->toContain('$this->migrator->exists($key)')
        ->and($translations)->toContain('dark_design_tokens')
        ->and($translations)->toContain('Dark page background colour')
        ->and($tokenData)->toContain('public string $darkPageBackground')
        ->and($tokenData)->toContain('public string $darkPrimaryAction')
        ->and($tokenData)->toContain('public string $darkImageBorder');
});

it('ships additive settings defaults for the foundation typography token layer', function (): void {
    $migration = file_get_contents(dirname(__DIR__, 2) . '/database/settings/2026_06_07_000002_add_theme_foundation_typography_tokens.php');
    $migrationProvider = file_get_contents(dirname(__DIR__, 2) . '/src/Settings/FoundationThemeSettingsMigrationProvider.php');
    $translations = file_get_contents(dirname(__DIR__, 2) . '/resources/lang/en/form.php');
    $tokenData = file_get_contents(dirname(__DIR__, 2) . '/src/Data/FoundationThemeTokensData.php');

    expect($migration)
        ->toContain('theme_foundation.heading_scale')
        ->toContain('$this->migrator->exists')
        ->and($migrationProvider)->toContain('2026_06_07_000002_add_theme_foundation_typography_tokens')
        ->and($translations)->toContain('heading_scale')
        ->and($translations)->toContain('Expressive')
        ->and($tokenData)->toContain('public string $headingSizeH1')
        ->and($tokenData)->toContain('public string $headingLineHeight');
});

it('emits document direction and logical shell utilities for RTL support', function (): void {
    $appShell = file_get_contents(dirname(__DIR__, 2) . '/resources/views/app.blade.php');
    $themePage = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/page.blade.php');
    $navigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/sections/navigation.blade.php');
    $contentListing = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/sections/content-listing.blade.php');

    expect($appShell)
        ->toContain('Frontend::language()')
        ->toContain('$textDirection')
        ->toContain('dir="{{ $textDirection }}"')
        ->and($themePage)->toContain('focus:start-4')
        ->and($navigation)->toContain('end-0')
        ->and($navigation)->not->toContain('right-0')
        ->and($contentListing)->toContain('text-start')
        ->and($contentListing)->toContain('pe-4')
        ->and($contentListing)->not->toContain('text-left')
        ->and($contentListing)->not->toContain('pr-4');
});
