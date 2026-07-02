<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

/**
 * Supplies a theme's own complete demo content.
 *
 * A theme implements this when its demo surfaces should render as an individual,
 * vertical-authentic site rather than the shared generic skeleton. The returned
 * definitions carry per-surface `render_data['sections']` (ordered, typed +
 * signature sections) which the page adapter renders in order.
 */
interface ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array;
}
