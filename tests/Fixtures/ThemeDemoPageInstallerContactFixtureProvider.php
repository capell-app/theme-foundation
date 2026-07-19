<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\Enums\LayoutEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

final class ThemeDemoPageInstallerContactFixtureProvider implements ProvidesThemeDemoContent
{
    /** @return array<int, ThemeDemoPageDefinition> */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [new ThemeDemoPageDefinition(
            surface: 'contact',
            name: $themeName . ' Contact',
            title: $themeName . ' Contact',
            slug: 'contact',
            content: '<h2>Contact hero</h2><p>Contact content.</p>',
            renderData: [
                'summary' => 'Contact render data without a provider-defined form.',
                'sections' => [['type' => 'hero', 'heading' => 'Contact hero']],
            ],
            layout: LayoutEnum::System,
            containers: ['main' => ['widgets' => [['widget_key' => 'page-content']]]],
            widgets: [['method' => 'pageContentWidget']],
        )];
    }
}
