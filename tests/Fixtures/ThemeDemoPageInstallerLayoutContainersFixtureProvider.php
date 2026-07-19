<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

final class ThemeDemoPageInstallerLayoutContainersFixtureProvider implements ProvidesThemeDemoContent
{
    /** @return array<int, ThemeDemoPageDefinition> */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [new ThemeDemoPageDefinition(
            surface: 'homepage',
            name: $themeName . ' Home',
            title: $themeName . ' Homepage',
            slug: 'theme-' . $themeKey,
            content: '<p>Layout builder demo homepage.</p>',
            renderData: [
                'summary' => 'Homepage render data that should be dropped once containers take over.',
                'sections' => [['type' => 'hero', 'heading' => 'Legacy section pipeline']],
            ],
            type: PageTypeEnum::Home,
            layout: LayoutEnum::Home,
            containers: ['main' => [
                'meta' => ['colspan' => 12],
                'widgets' => [
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'navigation-widget', 'occurrence' => 1],
                    ['widget_key' => 'footer-widget', 'occurrence' => 1],
                ],
            ]],
            widgets: [
                ['method' => 'pageContentWidget'],
                ['method' => 'bespokeContentWidget', 'args' => [
                    'navigation-widget', 'Navigation widget', 'capell.widget.fixture.navigation', ['type' => 'navigation'],
                ]],
                ['method' => 'bespokeContentWidget', 'args' => [
                    'footer-widget', 'Footer widget', 'capell.widget.fixture.footer', ['type' => 'footer'],
                ]],
            ],
        )];
    }
}
