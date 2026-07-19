<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

final class ThemeDemoPageInstallerSharedLayoutFixtureProvider implements ProvidesThemeDemoContent
{
    /** @return array<int, ThemeDemoPageDefinition> */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'detail',
                name: $themeName . ' Detail',
                title: $themeName . ' Detail - Browser title suffix',
                slug: 'detail',
                content: '<p>Detail content.</p>',
                renderData: [
                    'summary' => 'Detail render data that should be dropped once containers take over.',
                    'sections' => [['type' => 'hero', 'heading' => 'Legacy detail section pipeline']],
                ],
                containers: ['main' => ['widgets' => [
                    ['widget_key' => 'page-content', 'occurrence' => 1],
                    ['widget_key' => 'detail-widget', 'occurrence' => 1],
                ]]],
                widgets: [
                    ['method' => 'pageContentWidget'],
                    ['method' => 'bespokeContentWidget', 'args' => [
                        'detail-widget', 'Detail widget', 'capell.widget.page-content', ['heading' => 'Detail widget'],
                    ]],
                ],
            ),
            new ThemeDemoPageDefinition(
                surface: 'cta',
                name: $themeName . ' CTA',
                title: $themeName . ' CTA - Browser title suffix',
                slug: 'cta',
                content: '<p>CTA content.</p>',
                renderData: [
                    'summary' => 'CTA render data that should be dropped once containers take over.',
                    'sections' => [['type' => 'cta', 'heading' => 'Legacy cta section pipeline']],
                ],
                containers: ['main' => ['widgets' => [
                    ['widget_key' => 'page-content', 'occurrence' => 1],
                    ['widget_key' => 'cta-widget', 'occurrence' => 1],
                ]]],
                widgets: [
                    ['method' => 'pageContentWidget'],
                    ['method' => 'bespokeContentWidget', 'args' => [
                        'cta-widget', 'CTA widget', 'capell.widget.page-content', ['heading' => 'CTA widget'],
                    ]],
                ],
            ),
        ];
    }
}
