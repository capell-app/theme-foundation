<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

final class ThemeDemoPageInstallerEmptySearchFixtureProvider implements ProvidesThemeDemoContent
{
    /** @return array<int, ThemeDemoPageDefinition> */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [new ThemeDemoPageDefinition(
            surface: 'empty',
            name: $themeName . ' Empty',
            title: $themeName . ' Empty',
            slug: 'empty',
            content: '<p>Empty content.</p>',
            renderData: [
                'summary' => 'Empty render data without a provider-defined search section.',
                'sections' => [
                    ['type' => 'hero', 'heading' => 'No results'],
                    ['type' => 'cta', 'heading' => 'Browse instead'],
                ],
            ],
        )];
    }
}
