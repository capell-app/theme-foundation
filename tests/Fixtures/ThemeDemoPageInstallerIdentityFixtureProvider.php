<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\Enums\LayoutEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
use Override;

final class ThemeDemoPageInstallerIdentityFixtureProvider implements ProvidesThemeDemoContent
{
    public function __construct(
        private readonly string $name,
        private readonly LayoutEnum $layout = LayoutEnum::Default,
        private readonly string $surface = 'detail',
        private readonly bool $withContainers = false,
        private readonly ?string $slug = null,
    ) {}

    /** @return array<int, ThemeDemoPageDefinition> */
    #[Override]
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [new ThemeDemoPageDefinition(
            surface: $this->surface,
            name: $this->name,
            title: $this->name,
            slug: $this->slug ?? 'theme-' . $themeKey . '-' . $this->surface,
            content: '<p>Stable demo identity.</p>',
            renderData: [],
            layout: $this->layout,
            containers: $this->withContainers ? ['main' => ['meta' => ['colspan' => 12], 'widgets' => [['widget_key' => 'page-content']]]] : null,
            widgets: $this->withContainers ? [['method' => 'pageContentWidget']] : null,
        )];
    }
}
