<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\Frontend\Contracts\FrontendContextReader;
use Illuminate\Contracts\Container\Container;

/**
 * Reads the section variant the active theme preset chooses for a section
 * type. Resolved once per request (scoped binding) so every section on a page
 * shares one preset lookup.
 */
final class ActivePresetSectionVariants
{
    private bool $resolved = false;

    private ?ThemePresetData $preset = null;

    public function __construct(private readonly Container $container) {}

    public function for(string $sectionType): ?string
    {
        return $this->preset()?->sectionVariant($sectionType);
    }

    private function preset(): ?ThemePresetData
    {
        if ($this->resolved) {
            return $this->preset;
        }

        $this->resolved = true;

        if (! $this->container->bound(ThemeRuntimeSettings::class)) {
            return null;
        }

        $settings = $this->container->make(ThemeRuntimeSettings::class);
        $theme = $this->container->bound(FrontendContextReader::class)
            ? $this->container->make(FrontendContextReader::class)->theme()
            : null;
        $themeKey = $theme instanceof Theme ? $theme->key : $settings->activeTheme();
        $presetKey = $theme instanceof Theme ? data_get($theme->meta, 'editor.preset.active') : null;

        if (! is_string($presetKey) || $presetKey === '') {
            $presetKey = $settings->activePreset();
        }

        $registry = $this->container->make(ThemeRegistry::class);

        if (! is_string($themeKey) || ! $registry->has($themeKey)) {
            return null;
        }

        $definition = $registry->definition($themeKey);

        return $this->preset = $definition->preset($presetKey) ?? $definition->presets[0] ?? null;
    }
}
