<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\FoundationTheme\Data\FoundationLayoutContainerPresentationData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;

final class FoundationLayoutContainerThemePresentationProjector implements LayoutContainerThemePresentationProjector
{
    public function themeKey(): string
    {
        return FoundationThemeServiceProvider::THEME_KEY;
    }

    public function project(array $state): LayoutContainerThemePresentationData
    {
        $surfaceTone = $state['surface_tone'] ?? null;

        return new FoundationLayoutContainerPresentationData(
            surfaceTone: is_string($surfaceTone) && in_array($surfaceTone, ['default', 'muted', 'contrast'], true)
                ? $surfaceTone
                : 'default',
        );
    }
}
