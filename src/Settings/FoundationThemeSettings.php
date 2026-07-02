<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Spatie\LaravelSettings\Settings;

class FoundationThemeSettings extends Settings implements SettingsContract
{
    public const array SECTION_SPACING_OPTIONS = [
        'comfortable' => 'clamp(2.75rem, 5vw, 4.75rem)',
        'relaxed' => 'clamp(3.5rem, 6vw, 6rem)',
        'spacious' => 'clamp(4.5rem, 7vw, 7.25rem)',
    ];

    public const array WIDGET_GAP_OPTIONS = [
        'compact' => 'clamp(1rem, 2vw, 1.5rem)',
        'balanced' => 'clamp(1.25rem, 2.5vw, 2rem)',
        'airy' => 'clamp(1.75rem, 3vw, 2.75rem)',
    ];

    public const array HEADING_SCALE_OPTIONS = [
        'compact' => [
            'h1' => 'clamp(2rem, 4vw, 3.25rem)',
            'h2' => 'clamp(1.65rem, 3vw, 2.35rem)',
            'h3' => 'clamp(1.35rem, 2vw, 1.65rem)',
            'lineHeight' => '1.12',
        ],
        'balanced' => [
            'h1' => 'clamp(2.25rem, 5vw, 4rem)',
            'h2' => 'clamp(1.85rem, 3.5vw, 2.75rem)',
            'h3' => 'clamp(1.45rem, 2.25vw, 1.85rem)',
            'lineHeight' => '1.1',
        ],
        'expressive' => [
            'h1' => 'clamp(2.6rem, 6vw, 4.75rem)',
            'h2' => 'clamp(2rem, 4vw, 3.25rem)',
            'h3' => 'clamp(1.55rem, 2.5vw, 2rem)',
            'lineHeight' => '1.06',
        ],
    ];

    public bool $enable_lazy_loading = true;

    public bool $minify_assets = true;

    public string $page_background_color = '#faf9f7';

    public string $surface_background_color = '#ffffff';

    public string $muted_background_color = '#f4f3f1';

    public string $header_background_color = '#fbfaf7';

    public string $border_color = '#e1e5eb';

    public string $border_strong_color = '#c7ced8';

    public string $card_background_color = '#ffffff';

    public string $primary_action_color = '#315f8f';

    public string $band_background_color = '#faf9f7';

    public string $band_alternate_background_color = '#f4f3f1';

    public string $band_accent_background_color = '#f4f3f1';

    public string $band_border_color = '#e1e5eb';

    public string $image_border_color = '#e1e5eb';

    public string $dark_page_background_color = '#0f172a';

    public string $dark_surface_background_color = '#111827';

    public string $dark_muted_background_color = '#1f2937';

    public string $dark_header_background_color = '#111827';

    public string $dark_border_color = '#334155';

    public string $dark_border_strong_color = '#475569';

    public string $dark_card_background_color = '#111827';

    public string $dark_primary_action_color = '#93c5fd';

    public string $dark_band_background_color = '#0f172a';

    public string $dark_band_alternate_background_color = '#111827';

    public string $dark_band_accent_background_color = '#1e293b';

    public string $dark_band_border_color = '#334155';

    public string $dark_image_border_color = '#334155';

    public string $image_radius = '0.5rem';

    public string $section_spacing = 'relaxed';

    public string $widget_gap = 'balanced';

    public string $heading_scale = 'balanced';

    public static function group(): string
    {
        return 'theme_foundation';
    }

    public static function schema(): string
    {
        return FoundationThemeSettingsSchema::class;
    }

    public static function sectionSpacingCssValueFor(?string $sectionSpacing): string
    {
        return self::SECTION_SPACING_OPTIONS[$sectionSpacing ?? 'relaxed']
            ?? self::SECTION_SPACING_OPTIONS['relaxed'];
    }

    public static function widgetGapCssValueFor(?string $widgetGap): string
    {
        return self::WIDGET_GAP_OPTIONS[$widgetGap ?? 'balanced']
            ?? self::WIDGET_GAP_OPTIONS['balanced'];
    }

    /**
     * @return array{h1: string, h2: string, h3: string, lineHeight: string}
     */
    public static function headingScaleCssValuesFor(?string $headingScale): array
    {
        return self::HEADING_SCALE_OPTIONS[$headingScale ?? 'balanced']
            ?? self::HEADING_SCALE_OPTIONS['balanced'];
    }

    public function sectionSpacingCssValue(): string
    {
        return self::sectionSpacingCssValueFor($this->section_spacing);
    }

    public function widgetGapCssValue(): string
    {
        return self::widgetGapCssValueFor($this->widget_gap);
    }

    /**
     * @return array{h1: string, h2: string, h3: string, lineHeight: string}
     */
    public function headingScaleCssValues(): array
    {
        return self::headingScaleCssValuesFor($this->heading_scale);
    }
}
