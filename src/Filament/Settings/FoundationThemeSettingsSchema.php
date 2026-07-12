<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FoundationThemeSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Section::make(self::translate('capell-theme-foundation::form.performance'))
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            HelperText::apply(
                                Checkbox::make('enable_lazy_loading')
                                    ->label(self::translate('capell-frontend::form.enable_lazy_loading')),
                                static fn (): string => self::translate('capell-frontend::form.enable_lazy_loading_helper'),
                            ),
                            HelperText::apply(
                                Checkbox::make('minify_assets')
                                    ->label(self::translate('capell-frontend::form.minify_assets')),
                                static fn (): string => self::translate('capell-frontend::form.minify_assets_helper'),
                            ),
                        ]),
                ]),
            Section::make(self::translate('capell-theme-foundation::form.design_tokens'))
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            ColorPicker::make('page_background_color')
                                ->label(self::translate('capell-theme-foundation::form.page_background_color'))
                                ->required(),
                            ColorPicker::make('surface_background_color')
                                ->label(self::translate('capell-theme-foundation::form.surface_background_color'))
                                ->required(),
                            ColorPicker::make('muted_background_color')
                                ->label(self::translate('capell-theme-foundation::form.muted_background_color'))
                                ->required(),
                            ColorPicker::make('header_background_color')
                                ->label(self::translate('capell-theme-foundation::form.header_background_color'))
                                ->required(),
                            ColorPicker::make('border_color')
                                ->label(self::translate('capell-theme-foundation::form.border_color'))
                                ->required(),
                            ColorPicker::make('border_strong_color')
                                ->label(self::translate('capell-theme-foundation::form.border_strong_color'))
                                ->required(),
                            ColorPicker::make('card_background_color')
                                ->label(self::translate('capell-theme-foundation::form.card_background_color'))
                                ->required(),
                            ColorPicker::make('primary_action_color')
                                ->label(self::translate('capell-theme-foundation::form.primary_action_color'))
                                ->required(),
                            ColorPicker::make('band_background_color')
                                ->label(self::translate('capell-theme-foundation::form.band_background_color'))
                                ->required(),
                            ColorPicker::make('band_alternate_background_color')
                                ->label(self::translate('capell-theme-foundation::form.band_alternate_background_color'))
                                ->required(),
                            ColorPicker::make('band_accent_background_color')
                                ->label(self::translate('capell-theme-foundation::form.band_accent_background_color'))
                                ->required(),
                            ColorPicker::make('band_border_color')
                                ->label(self::translate('capell-theme-foundation::form.band_border_color'))
                                ->required(),
                            ColorPicker::make('image_border_color')
                                ->label(self::translate('capell-theme-foundation::form.image_border_color'))
                                ->required(),
                            Select::make('image_radius')
                                ->label(self::translate('capell-theme-foundation::form.image_radius'))
                                ->options([
                                    '0rem' => self::translate('capell-theme-foundation::form.image_radius_options.none'),
                                    '0.25rem' => self::translate('capell-theme-foundation::form.image_radius_options.subtle'),
                                    '0.5rem' => self::translate('capell-theme-foundation::form.image_radius_options.soft'),
                                ])
                                ->default('0.5rem')
                                ->in(['0rem', '0.25rem', '0.5rem'])
                                ->required(),
                            Select::make('section_spacing')
                                ->label(self::translate('capell-theme-foundation::form.section_spacing'))
                                ->options([
                                    'comfortable' => self::translate('capell-theme-foundation::form.section_spacing_options.comfortable'),
                                    'relaxed' => self::translate('capell-theme-foundation::form.section_spacing_options.relaxed'),
                                    'spacious' => self::translate('capell-theme-foundation::form.section_spacing_options.spacious'),
                                ])
                                ->default('relaxed')
                                ->in(array_keys(FoundationThemeSettings::SECTION_SPACING_OPTIONS))
                                ->required(),
                            Select::make('widget_gap')
                                ->label(self::translate('capell-theme-foundation::form.widget_gap'))
                                ->options([
                                    'compact' => self::translate('capell-theme-foundation::form.widget_gap_options.compact'),
                                    'balanced' => self::translate('capell-theme-foundation::form.widget_gap_options.balanced'),
                                    'airy' => self::translate('capell-theme-foundation::form.widget_gap_options.airy'),
                                ])
                                ->default('balanced')
                                ->in(array_keys(FoundationThemeSettings::WIDGET_GAP_OPTIONS))
                                ->required(),
                            Select::make('heading_scale')
                                ->label(self::translate('capell-theme-foundation::form.heading_scale'))
                                ->options([
                                    'compact' => self::translate('capell-theme-foundation::form.heading_scale_options.compact'),
                                    'balanced' => self::translate('capell-theme-foundation::form.heading_scale_options.balanced'),
                                    'expressive' => self::translate('capell-theme-foundation::form.heading_scale_options.expressive'),
                                ])
                                ->default('balanced')
                                ->in(array_keys(FoundationThemeSettings::HEADING_SCALE_OPTIONS))
                                ->required(),
                            Select::make('motion_intensity')
                                ->label(self::translate('capell-theme-foundation::form.motion_intensity'))
                                ->options([
                                    'none' => self::translate('capell-theme-foundation::form.motion_intensity_options.none'),
                                    'minimal' => self::translate('capell-theme-foundation::form.motion_intensity_options.minimal'),
                                    'subtle' => self::translate('capell-theme-foundation::form.motion_intensity_options.subtle'),
                                    'energetic' => self::translate('capell-theme-foundation::form.motion_intensity_options.energetic'),
                                ])
                                ->default('subtle')
                                ->in(array_keys(FoundationThemeSettings::MOTION_INTENSITY_OPTIONS))
                                ->required(),
                        ]),
                ]),
            Section::make(self::translate('capell-theme-foundation::form.dark_design_tokens'))
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            ColorPicker::make('dark_page_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_page_background_color'))
                                ->required(),
                            ColorPicker::make('dark_surface_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_surface_background_color'))
                                ->required(),
                            ColorPicker::make('dark_muted_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_muted_background_color'))
                                ->required(),
                            ColorPicker::make('dark_header_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_header_background_color'))
                                ->required(),
                            ColorPicker::make('dark_border_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_border_color'))
                                ->required(),
                            ColorPicker::make('dark_border_strong_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_border_strong_color'))
                                ->required(),
                            ColorPicker::make('dark_card_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_card_background_color'))
                                ->required(),
                            ColorPicker::make('dark_primary_action_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_primary_action_color'))
                                ->required(),
                            ColorPicker::make('dark_band_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_band_background_color'))
                                ->required(),
                            ColorPicker::make('dark_band_alternate_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_band_alternate_background_color'))
                                ->required(),
                            ColorPicker::make('dark_band_accent_background_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_band_accent_background_color'))
                                ->required(),
                            ColorPicker::make('dark_band_border_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_band_border_color'))
                                ->required(),
                            ColorPicker::make('dark_image_border_color')
                                ->label(self::translate('capell-theme-foundation::form.dark_image_border_color'))
                                ->required(),
                        ]),
                ]),
        ];
    }

    private static function translate(string $key): string
    {
        return app()->bound('translator') ? __($key) : $key;
    }
}
