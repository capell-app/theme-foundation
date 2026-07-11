<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

it('reuses an authored composition for an explicitly named capture surface', function (): void {
    $definition = new ThemeDemoPageDefinition(
        surface: 'directory',
        name: 'Directory',
        title: 'Directory title',
        slug: 'theme-example-directory',
        content: '<p>Portable copy.</p>',
        renderData: ['summary' => 'Original summary', 'sections' => [['type' => 'listing']]],
        type: PageTypeEnum::Default,
        layout: LayoutEnum::Results,
        containers: [['key' => 'main']],
        widgets: [['method' => 'pageContent']],
    );

    $searchDefinition = $definition->forSurface(
        surface: 'search',
        name: 'Search results',
        title: 'Search results for editorial',
        slug: 'theme-example-search',
        renderDataOverrides: ['summary' => 'Populated search results.'],
    );

    expect($searchDefinition->surface)->toBe('search')
        ->and($searchDefinition->name)->toBe('Search results')
        ->and($searchDefinition->title)->toBe('Search results for editorial')
        ->and($searchDefinition->slug)->toBe('theme-example-search')
        ->and($searchDefinition->content)->toBe($definition->content)
        ->and($searchDefinition->renderData)->toBe([
            'summary' => 'Populated search results.',
            'sections' => [['type' => 'listing']],
        ])
        ->and($searchDefinition->layout)->toBe(LayoutEnum::Results)
        ->and($searchDefinition->containers)->toBe($definition->containers)
        ->and($searchDefinition->widgets)->toBe($definition->widgets);
});
