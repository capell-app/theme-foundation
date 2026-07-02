<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetComponentEnum;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\FoundationTheme\Data\AssetBannerItemData;
use Capell\FoundationTheme\Data\BannerImageRenderData;
use Capell\FoundationTheme\Data\LayoutNeighborLinksData;
use Capell\FoundationTheme\Data\PageContentRenderData;
use Capell\FoundationTheme\Data\WidgetAssetRenderData;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Health\FoundationThemeHealthCheck;
use Capell\FoundationTheme\Support\View\FoundationThemeViewName;

it('carries foundation theme render data objects', function (): void {
    $pageContent = new PageContentRenderData(
        image: null,
        content: '<p>Portable content.</p>',
        contentStructure: ContentStructure::Html,
        hasContent: true,
        hasTitle: true,
        title: 'About Capell',
    );
    $banner = new BannerImageRenderData(
        backgroundImage: 'hero.jpg',
        actions: [['label' => 'Start']],
        hasContent: true,
        imageRoundedClass: 'rounded-lg',
    );
    $item = new AssetBannerItemData(
        image: 'card.jpg',
        alt: 'Card image',
        title: 'Card title',
        content: 'Card content',
        url: '/card',
        linkText: 'Read more',
    );
    $asset = new WidgetAssetRenderData(
        asset: 'asset-model',
        image: 'asset.jpg',
        linkedPage: null,
        translation: null,
        meta: ['eyebrow' => 'Featured'],
        alt: 'Asset image',
        actions: [['label' => 'Open']],
        accent: 'blue',
        caption: 'Caption',
        content: 'Content',
        contentStructure: ContentStructure::Blocks,
        cropPreset: '16:9',
        headingSize: 'lg',
        headingWeight: 'bold',
        icon: 'heroicon-o-star',
        linkText: 'Open',
        linkUrl: '/open',
        position: 'left',
        role: 'Author',
        social: ['x' => 'https://x.test/capell'],
        status: 'active',
        tags: ['CMS'],
        textAlign: 'left',
        title: 'Asset title',
    );

    expect($pageContent->hasContent)->toBeTrue()
        ->and($pageContent->contentStructure)->toBe(ContentStructure::Html)
        ->and($banner->actions)->toBe([['label' => 'Start']])
        ->and($item->linkText)->toBe('Read more')
        ->and($asset->meta)->toBe(['eyebrow' => 'Featured'])
        ->and($asset->contentStructure)->toBe(ContentStructure::Blocks)
        ->and($asset->tags)->toBe(['CMS']);
});

it('knows when neighboring page links should render', function (): void {
    expect((new LayoutNeighborLinksData(null, null))->shouldRender())->toBeFalse();

    $page = Page::factory()->create();

    expect((new LayoutNeighborLinksData($page, null))->shouldRender())->toBeTrue()
        ->and((new LayoutNeighborLinksData(null, $page))->shouldRender())->toBeTrue();
});

it('defines foundation theme metadata and canonical view names', function (): void {
    expect(FoundationThemeHealthCheck::compatibleCapellApiVersion())->toBe('^4.0')
        ->and(FoundationThemeAssetEnum::Page->getAsset())->toBe(AssetEnum::Page)
        ->and(FoundationThemeAssetEnum::Page->getComponent())->toBe(AssetComponentEnum::Page->value)
        ->and(FoundationThemeViewName::canonical('capell-layout-builder::components.widget.hero'))->toBe('capell-theme-foundation::components.widget.hero')
        ->and(FoundationThemeViewName::canonical('capell-layout-builder::layout.main'))->toBe('capell-theme-foundation::components.layout.main')
        ->and(FoundationThemeViewName::canonical('components.actions.buttons'))->toBe('capell-theme-foundation::components.actions.buttons')
        ->and(FoundationThemeViewName::canonical('custom-package::view'))->toBe('custom-package::view');
});
