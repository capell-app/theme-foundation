<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Data\FoundationThemeTokensData;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveFoundationThemeTokensAction
{
    use AsAction;

    public function handle(
        ?Theme $theme = null,
        ?Site $site = null,
        ?FoundationThemeSettings $settings = null,
        bool $resolveSettings = true,
    ): FoundationThemeTokensData {
        $theme ??= Frontend::theme();
        $site ??= Frontend::site();
        $settings ??= $resolveSettings ? $this->resolveSettings() : null;

        $brandColorMeta = $site instanceof Site ? $site->getMeta('brand_color') : null;
        $linkColorMeta = $theme instanceof Theme ? $theme->getMeta('link_color') : null;
        $linkColorActiveMeta = $theme instanceof Theme ? $theme->getMeta('link_color_active') : null;
        $dividerColorMeta = $theme instanceof Theme ? $theme->getMeta('divider_color') : null;

        $headingScale = $settings instanceof FoundationThemeSettings
            ? $settings->headingScaleCssValues()
            : FoundationThemeSettings::headingScaleCssValuesFor(null);

        $motionIntensityKey = $settings instanceof FoundationThemeSettings
            ? $settings->motionIntensityKey()
            : 'subtle';

        $motion = $settings instanceof FoundationThemeSettings
            ? $settings->motionIntensityCssValues()
            : FoundationThemeSettings::motionIntensityCssValuesFor(null);

        return new FoundationThemeTokensData(
            paletteColors: $this->paletteColors($theme)->values()->all(),
            brandColor: $this->safeColor($brandColorMeta, '#111827'),
            linkColor: $this->safeColor($linkColorMeta, '#1d4ed8'),
            linkColorActive: $this->safeColor($linkColorActiveMeta, $this->resolveColorToken($linkColorMeta, '#1e40af')),
            dividerColor: $this->safeColor($dividerColorMeta, '#e5e7eb'),
            pageBackground: $this->settingColor($settings, 'page_background_color', '#faf9f7'),
            surfaceBackground: $this->settingColor($settings, 'surface_background_color', '#ffffff'),
            mutedBackground: $this->settingColor($settings, 'muted_background_color', '#f4f3f1'),
            headerBackground: $this->settingColor($settings, 'header_background_color', '#ffffff'),
            borderColor: $this->settingColor($settings, 'border_color', '#e1e5eb'),
            borderStrongColor: $this->settingColor($settings, 'border_strong_color', '#c7ced8'),
            cardBackground: $this->settingColor($settings, 'card_background_color', '#ffffff'),
            primaryAction: $this->settingColor($settings, 'primary_action_color', '#3b5998'),
            bandBackground: $this->settingColor($settings, 'band_background_color', '#faf9f7'),
            bandAlternateBackground: $this->settingColor($settings, 'band_alternate_background_color', '#f4f3f1'),
            bandAccentBackground: $this->settingColor($settings, 'band_accent_background_color', '#f4f3f1'),
            bandBorder: $this->settingColor($settings, 'band_border_color', '#e1e5eb'),
            imageBorder: $this->settingColor($settings, 'image_border_color', '#e1e5eb'),
            darkPageBackground: $this->settingColor($settings, 'dark_page_background_color', '#0f172a'),
            darkSurfaceBackground: $this->settingColor($settings, 'dark_surface_background_color', '#111827'),
            darkMutedBackground: $this->settingColor($settings, 'dark_muted_background_color', '#1f2937'),
            darkHeaderBackground: $this->settingColor($settings, 'dark_header_background_color', '#111827'),
            darkBorderColor: $this->settingColor($settings, 'dark_border_color', '#334155'),
            darkBorderStrongColor: $this->settingColor($settings, 'dark_border_strong_color', '#475569'),
            darkCardBackground: $this->settingColor($settings, 'dark_card_background_color', '#111827'),
            darkPrimaryAction: $this->settingColor($settings, 'dark_primary_action_color', '#93c5fd'),
            darkBandBackground: $this->settingColor($settings, 'dark_band_background_color', '#0f172a'),
            darkBandAlternateBackground: $this->settingColor($settings, 'dark_band_alternate_background_color', '#111827'),
            darkBandAccentBackground: $this->settingColor($settings, 'dark_band_accent_background_color', '#1e293b'),
            darkBandBorder: $this->settingColor($settings, 'dark_band_border_color', '#334155'),
            darkImageBorder: $this->settingColor($settings, 'dark_image_border_color', '#334155'),
            imageRadius: $settings instanceof FoundationThemeSettings && in_array($settings->image_radius, ['0rem', '0.25rem', '0.5rem'], true)
                ? $settings->image_radius
                : '0.5rem',
            sectionSpacing: $settings instanceof FoundationThemeSettings
                ? $settings->sectionSpacingCssValue()
                : FoundationThemeSettings::sectionSpacingCssValueFor(null),
            widgetGap: $settings instanceof FoundationThemeSettings
                ? $settings->widgetGapCssValue()
                : FoundationThemeSettings::widgetGapCssValueFor(null),
            headingSizeH1: $headingScale['h1'],
            headingSizeH2: $headingScale['h2'],
            headingSizeH3: $headingScale['h3'],
            headingLineHeight: $headingScale['lineHeight'],
            motionIntensity: $motionIntensityKey,
            motionDuration: $motion['duration'],
            motionEase: $motion['ease'],
            motionStagger: $motion['stagger'],
            motionDistance: $motion['distance'],
        );
    }

    /**
     * @return Collection<int, array{name: string, value: string}>
     */
    /**
     * @return Collection<int, array{name: string, value: string}>
     */
    public function defaultPaletteColors(): Collection
    {
        return $this->paletteColors(null)->values();
    }

    /**
     * @return Collection<string, array{name: string, value: string}>
     */
    public function paletteColors(?Theme $theme): Collection
    {
        return collect(DefaultColorEnum::getKeyValues())
            ->merge($theme instanceof Theme && is_array($theme->colors) ? $theme->colors : [])
            ->map(function (mixed $value, string $name): ?array {
                if (! is_string($value) || preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $name) !== 1) {
                    return null;
                }

                $convertedValue = ResolveSafeCssColorTokenAction::run($value, '');

                if ($convertedValue === '') {
                    return null;
                }

                return ['name' => $name, 'value' => $convertedValue];
            })
            ->filter();
    }

    private function resolveSettings(): ?FoundationThemeSettings
    {
        try {
            return resolve(FoundationThemeSettings::class);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveColorToken(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function safeColor(mixed $value, string $fallback): string
    {
        return ResolveSafeCssColorTokenAction::run($this->resolveColorToken($value, $fallback), $fallback);
    }

    private function settingColor(?FoundationThemeSettings $settings, string $property, string $fallback): string
    {
        $value = $settings instanceof FoundationThemeSettings ? $settings->{$property} : null;

        try {
            return $this->safeColor($value, $fallback);
        } catch (Throwable) {
            return ResolveSafeCssColorTokenAction::run($fallback, $fallback);
        }
    }
}
