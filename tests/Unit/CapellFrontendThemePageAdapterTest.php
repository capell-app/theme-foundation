<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Core\ThemeStudio\Data\ContentListingSectionData;
use Capell\Core\ThemeStudio\Data\FeatureSectionData;
use Capell\Core\ThemeStudio\Data\FooterData;
use Capell\Core\ThemeStudio\Data\HeroSectionData;
use Capell\Core\ThemeStudio\Data\NavigationData;
use Capell\Core\ThemeStudio\Data\ProofSectionData;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Frontend\ThemeStudio\Adapters\CapellFrontendThemePageAdapter;

function capellFrontendThemeNavigation(?NavigationData $navigation): NavigationData
{
    throw_unless($navigation instanceof NavigationData, RuntimeException::class, 'Expected theme page navigation data.');

    return $navigation;
}

function capellFrontendThemeFooter(?FooterData $footer): FooterData
{
    throw_unless($footer instanceof FooterData, RuntimeException::class, 'Expected theme page footer data.');

    return $footer;
}

it('builds portable fallback theme data for the current frontend page', function (): void {
    $page = (new CapellFrontendThemePageAdapter)->currentPage();
    $heroSection = $page->sections[0];
    $navigation = capellFrontendThemeNavigation($page->navigation);
    $footer = capellFrontendThemeFooter($page->footer);

    expect($page->title)->toBe('Untitled page')
        ->and($page->sections)->toHaveCount(1)
        ->and($heroSection)->toBeInstanceOf(HeroSectionData::class)
        ->and($page->navigation)->toBeInstanceOf(NavigationData::class)
        ->and($navigation->brandName)->toBe('Site')
        ->and($page->footer)->toBeInstanceOf(FooterData::class)
        ->and($footer->brandName)->toBe('Site');

    expect($heroSection->toViewData()['section'])->toBe($heroSection);
});

it('keeps default navigation usable when no package navigation is available', function (): void {
    $navigation = (new CapellFrontendThemePageAdapter)->currentPage()->navigation;
    $navigation = capellFrontendThemeNavigation($navigation);

    expect($navigation)->toBeInstanceOf(NavigationData::class)
        ->and($navigation->items)->toBe([
            ['label' => 'Home', 'url' => '/'],
        ]);
});

it('builds gallery carousel and proof sections from theme demo render data', function (): void {
    $page = new Page([
        'name' => 'Theme demo page',
        'meta' => [
            'theme_demo' => [
                'render_data' => [
                    'features_heading' => 'Theme features',
                    'features' => [
                        ['title' => 'Feature card', 'summary' => 'Feature copy', 'imageUrl' => 'https://example.com/feature.jpg', 'type' => 'Feature'],
                    ],
                    'gallery' => [
                        'heading' => 'Theme gallery',
                        'summary' => 'Gallery copy',
                        'items' => [
                            ['title' => 'Gallery card', 'summary' => 'Gallery copy', 'image' => 'https://example.com/gallery.jpg'],
                        ],
                        'variant' => 'gallery',
                    ],
                    'proof' => [
                        'heading' => 'Theme proof',
                        'items' => [
                            ['metric' => '7 surfaces', 'name' => 'Preview proof', 'quote' => 'Proof copy'],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $page->setRelation('translation', new Translation(['title' => 'Theme demo page']));

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext((new FrontendState)->withPage($page)));

    $themePage = (new CapellFrontendThemePageAdapter)->currentPage();

    expect($themePage->sections)->toHaveCount(3)
        ->and($themePage->sections[0])->toBeInstanceOf(FeatureSectionData::class)
        ->and($themePage->sections[1])->toBeInstanceOf(ContentListingSectionData::class)
        ->and((string) ($themePage->sections[1]->toViewData()['variant'] ?? ''))->toBe('gallery')
        ->and($themePage->sections[2])->toBeInstanceOf(ProofSectionData::class);
});

it('builds spotlight tab sections from theme demo render data', function (): void {
    $page = new Page([
        'name' => 'Theme demo page',
        'meta' => [
            'theme_demo' => [
                'render_data' => [
                    'spotlight' => [
                        'heading' => 'Theme spotlight',
                        'summary' => 'Spotlight copy',
                        'variant' => 'spotlight',
                        'items' => [
                            ['title' => 'First panel', 'summary' => 'First panel copy', 'image' => 'https://example.com/first.jpg'],
                            ['title' => 'Second panel', 'summary' => 'Second panel copy', 'image' => 'https://example.com/second.jpg'],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $page->setRelation('translation', new Translation(['title' => 'Theme demo page']));

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext((new FrontendState)->withPage($page)));

    $themePage = (new CapellFrontendThemePageAdapter)->currentPage();

    expect($themePage->sections)->toHaveCount(1)
        ->and($themePage->sections[0])->toBeInstanceOf(ContentListingSectionData::class)
        ->and((string) ($themePage->sections[0]->toViewData()['variant'] ?? ''))->toBe('spotlight')
        ->and((string) ($themePage->sections[0]->toViewData()['heading'] ?? ''))->toBe('Theme spotlight');
});

it('renders gallery content listings with swiper controls', function (): void {
    $section = ContentListingSectionData::from([
        'heading' => 'Theme gallery',
        'summary' => 'Gallery copy',
        'variant' => 'gallery',
        'items' => [
            ['title' => 'First gallery card', 'summary' => 'First copy', 'image' => 'https://example.com/first.jpg'],
            ['title' => 'Second gallery card', 'summary' => 'Second copy', 'image' => 'https://example.com/second.jpg'],
        ],
    ]);

    $html = view('capell-theme-foundation::theme.sections.content-listing', ['section' => $section])->render();

    expect($html)
        ->toContain('class="swiper overflow-visible"')
        ->toContain('data-carousel-align="start"')
        ->toContain('data-carousel-autoplay="true"')
        ->toContain('data-carousel-controls=')
        ->toContain('.theme-content-gallery .swiper-controls .swiper-pagination')
        ->toContain('position: relative')
        ->toContain(__('capell-theme-foundation::generic.gallery'));
});

it('renders spotlight content listings as keyboard-friendly tabs', function (): void {
    $section = ContentListingSectionData::from([
        'heading' => 'Theme spotlight',
        'summary' => 'Spotlight copy',
        'variant' => 'spotlight',
        'items' => [
            ['title' => 'First panel', 'summary' => 'First panel copy', 'image' => 'https://example.com/first.jpg', 'type' => 'Moment'],
            ['title' => 'Second panel', 'summary' => 'Second panel copy', 'image' => 'https://example.com/second.jpg', 'type' => 'Depth'],
        ],
    ]);

    $html = view('capell-theme-foundation::theme.sections.content-listing', ['section' => $section])->render();

    expect($html)
        ->toContain('data-theme-spotlight')
        ->toContain('role="tablist"')
        ->toContain('data-spotlight-tab')
        ->toContain('data-spotlight-panel')
        ->toContain(__('capell-theme-foundation::generic.spotlight'));
});

it('renders pathway content listings as interactive disclosure panels', function (): void {
    $section = ContentListingSectionData::from([
        'heading' => 'Launch paths',
        'summary' => 'Pathway copy',
        'variant' => 'pathways',
        'items' => [
            ['title' => 'Homepage path', 'summary' => 'Start with the first screen.', 'type' => 'Launch'],
            ['title' => 'Directory path', 'summary' => 'Add browsable content.', 'type' => 'Browse'],
        ],
    ]);

    $html = view('capell-theme-foundation::theme.sections.content-listing', ['section' => $section])->render();

    expect($html)
        ->toContain('data-theme-pathways')
        ->toContain('data-pathway-panel')
        ->toContain('<details')
        ->toContain('<summary')
        ->toContain(__('capell-theme-foundation::generic.pathways'))
        ->toContain('Homepage path');
});
