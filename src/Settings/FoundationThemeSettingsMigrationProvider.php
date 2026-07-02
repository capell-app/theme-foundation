<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Settings;

use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;

class FoundationThemeSettingsMigrationProvider implements SettingsMigrationProviderInterface
{
    public function getSettingMigrations(): array
    {
        return [
            '2026_05_10_190850_01_create_theme_foundation_settings',
            '2026_05_23_160819_add_theme_foundation_design_tokens',
            '2026_05_23_161002_refresh_theme_foundation_design_token_defaults',
            '2026_05_23_170001_add_theme_foundation_composition_tokens',
            '2026_05_23_171201_quiet_theme_foundation_composition_palette',
            '2026_05_23_180101_add_theme_foundation_image_tokens',
            '2026_06_07_000001_add_theme_foundation_dark_design_tokens',
            '2026_06_07_000002_add_theme_foundation_typography_tokens',
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function migrations(): array
    {
        return $this->getSettingMigrations();
    }
}
