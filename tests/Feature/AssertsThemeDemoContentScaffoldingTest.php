<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
use Capell\FoundationTheme\Testing\AssertsThemeDemoContentScaffolding;

/*
|--------------------------------------------------------------------------
| AssertsThemeDemoContentScaffolding (Wave 2.8)
|--------------------------------------------------------------------------
|
| Verifies the shared Pest helper trait intended for capell:make-theme's
| scaffolded test stubs behaves correctly for both a complete (all 7
| surfaces) fixture and an incomplete one, using local fixture classes so
| this suite has no dependency on any sibling theme package.
*/

uses(AssertsThemeDemoContentScaffolding::class);

it('passes both assertions for a demo content provider covering all 7 surfaces', function (): void {
    $provider = new class implements ProvidesThemeDemoContent
    {
        public function definitions(string $themeKey, string $themeName, string $baseUrl): array
        {
            return array_map(
                static fn (string $surface): ThemeDemoPageDefinition => new ThemeDemoPageDefinition(
                    surface: $surface,
                    name: ucfirst($surface),
                    title: ucfirst($surface) . ' — ' . $themeName,
                    slug: $surface,
                    content: '<p>Fixture content for ' . $surface . '.</p>',
                    renderData: [],
                    type: PageTypeEnum::Default,
                    layout: LayoutEnum::Default,
                ),
                ['homepage', 'directory', 'detail', 'contact', 'empty', 'not-found', 'cta'],
            );
        }
    };

    $this->assertThemeDemoContentRendersWithoutThrowing($provider, 'fixture-theme', 'Fixture Theme');
    $this->assertAllDemoSurfacesArePresent($provider, 'fixture-theme', 'Fixture Theme');
});

it('fails the surface-coverage assertion when a required surface is missing', function (): void {
    $incompleteProvider = new class implements ProvidesThemeDemoContent
    {
        public function definitions(string $themeKey, string $themeName, string $baseUrl): array
        {
            return [
                new ThemeDemoPageDefinition(
                    surface: 'homepage',
                    name: 'Homepage',
                    title: 'Homepage — ' . $themeName,
                    slug: 'homepage',
                    content: '<p>Fixture homepage only.</p>',
                    renderData: [],
                ),
            ];
        }
    };

    expect(fn (): mixed => $this->assertAllDemoSurfacesArePresent($incompleteProvider, 'incomplete-theme', 'Incomplete Theme'))
        ->toThrow(Exception::class);
});

it('fails the render-without-throwing assertion when no definitions are returned', function (): void {
    $emptyProvider = new class implements ProvidesThemeDemoContent
    {
        public function definitions(string $themeKey, string $themeName, string $baseUrl): array
        {
            return [];
        }
    };

    expect(fn (): mixed => $this->assertThemeDemoContentRendersWithoutThrowing($emptyProvider, 'empty-theme', 'Empty Theme'))
        ->toThrow(Exception::class);
});
