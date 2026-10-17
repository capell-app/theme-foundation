<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageInstaller;

it('adds premium demo page surfaces for every prompt-built theme', function (string $themeKey, string $themeName): void {
    expect(new ThemeDemoPageInstaller()->handle(new ThemeDemoInstallData(['Premium surfaces'], ['en'], 'https://example.test'), $themeKey, $themeName))->toBe(0);
    $pages = Page::query()->with('translation')->get();
    $surfaces = $pages->map(fn (Page $page): mixed => data_get($page->meta, 'theme_demo.surface'))->all();
    $premiumPages = $pages->filter(fn (Page $page): bool => ! in_array(data_get($page->meta, 'theme_demo.surface'), [
        'homepage', 'directory', 'detail', 'contact', 'empty', 'not-found', 'cta',
    ], true));

    expect($pages)->toHaveCount(10)
        ->and($premiumPages)->toHaveCount(3)
        ->and($surfaces)->not->toContain('premium-1', 'premium-2', 'premium-3');

    foreach ($premiumPages as $page) {
        $renderData = data_get($page->meta, 'theme_demo.render_data');
        throw_unless(is_array($renderData), RuntimeException::class);
        $heroHeading = data_get($renderData, 'hero.heading');

        expect($renderData['sections'] ?? null)
            ->toBeArray()
            ->toHaveCount(3)
            ->and($renderData['items'] ?? null)
            ->toBeArray()
            ->toHaveCount(3)
            ->and($heroHeading)
            ->toBeString()
            ->and($page->translation?->title)
            ->toEndWith(is_string($heroHeading) && $heroHeading !== '' ? $heroHeading : 'x');
    }
})->with([
    ['ai-lab', 'AI Lab'],
    ['api-platform', 'API Platform'],
    ['ai-agent', 'AI Agent'],
    ['aeo-analytics', 'AEO Analytics'],
    ['fintech-trust', 'Fintech Trust'],
    ['crypto-defi', 'Crypto DeFi'],
    ['quant-trading', 'Quant Trading'],
    ['devtool-oss', 'Devtool OSS'],
    ['robotics-hardware', 'Robotics Hardware'],
    ['manufacturing', 'Manufacturing'],
    ['packaging-supplier', 'Packaging Supplier'],
    ['conference-event', 'Conference Event'],
    ['podcast-show', 'Podcast Show'],
    ['newsroom-magazine', 'Newsroom Magazine'],
    ['design-studio', 'Design Studio'],
    ['product-studio', 'Product Studio'],
    ['personal-dev', 'Personal Dev'],
    ['creator-newsletter', 'Creator Newsletter'],
    ['law-firm', 'Law Firm'],
    ['financial-advisory', 'Financial Advisory'],
    ['construction-trades', 'Construction Trades'],
    ['fitness-wellness', 'Fitness Wellness'],
    ['beauty-spa', 'Beauty & Spa'],
    ['travel-tourism', 'Travel Tourism'],
    ['automotive-dealer', 'Automotive Dealer'],
    ['property-developer', 'Property Developer'],
    ['recruitment-jobs', 'Recruitment Jobs'],
    ['blog', 'Blog'],
]);

it('attaches object-form navigation and footer chrome to every base demo surface', function (): void {
    expect(new ThemeDemoPageInstaller()->handle(new ThemeDemoInstallData(['Base chrome'], ['en'], 'https://example.test'), 'api-platform', 'API Platform'))->toBe(0);
    $pages = Page::query()->with('translation')->get();

    $brandName = 'API Platform Demo';
    $baseSurfaces = ['homepage', 'directory', 'detail', 'contact', 'empty', 'not-found', 'cta'];

    foreach ($baseSurfaces as $surface) {
        $page = $pages->first(fn (Page $page): bool => data_get($page->meta, 'theme_demo.surface') === $surface);
        throw_unless($page instanceof Page, RuntimeException::class);

        expect($page)->not->toBeNull("missing base surface: {$surface}");

        // Object form (assoc, not list) is what the adapter's navigationFrom()/footerFrom()
        // resolve into a branded nav + footer; a list form or absent key collapses to the
        // barren defaultNavigation()/defaultFooter() fallback.
        $navigation = data_get($page->meta, 'theme_demo.render_data.navigation');
        throw_unless(is_array($navigation), RuntimeException::class, "{$surface} navigation missing.");

        expect($navigation)->toBeArray("{$surface} navigation missing")
            ->and(array_is_list($navigation))->toBeFalse("{$surface} navigation must be object form")
            ->and($navigation['brandName'] ?? null)->toBe($brandName)
            ->and($navigation['items'] ?? null)->toBeArray();

        $footer = data_get($page->meta, 'theme_demo.render_data.footer');
        throw_unless(is_array($footer), RuntimeException::class, "{$surface} footer missing.");

        expect($footer)->toBeArray("{$surface} footer missing")
            ->and(array_is_list($footer))->toBeFalse("{$surface} footer must be object form")
            ->and($footer['brandName'] ?? null)->toBe($brandName)
            ->and($footer['columns'] ?? null)->toBeArray();
    }
});
