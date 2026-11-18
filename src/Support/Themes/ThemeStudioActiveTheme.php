<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

use Capell\Core\ThemeStudio\Settings\ThemeStudioSettings;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

/**
 * Reads and writes the Theme Studio's installation-wide active theme, which a
 * retired theme migration must move along with sites and layouts. An
 * installation without stored Theme Studio settings has nothing to move.
 */
final class ThemeStudioActiveTheme
{
    /**
     * @return array{theme: string, preset: string}|null
     */
    public function current(): ?array
    {
        try {
            $settings = resolve(ThemeStudioSettings::class);

            return ['theme' => $settings->activeTheme, 'preset' => $settings->activePreset];
        } catch (MissingSettings) {
            return null;
        }
    }

    public function switchTo(string $themeKey, string $presetKey): void
    {
        $settings = resolve(ThemeStudioSettings::class);
        $settings->activeTheme = $themeKey;
        $settings->activePreset = $presetKey;
        $settings->save();
    }
}
