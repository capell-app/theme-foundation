<?php

declare(strict_types=1);

use Capell\Core\Models\Translation;
use Capell\FoundationTheme\Data\AssetBannerItemData;
use Capell\FoundationTheme\Data\WidgetAssetRenderData;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| Author-supplied rich text must never reach public HTML raw
|--------------------------------------------------------------------------
|
| The testimonial and banner asset widgets render author rich text with
| `{!! !!}`. Both now interpolate the sanitised accessor on their render
| data, so the hostile-input assertions below cover the exact strings the
| public Blade emits.
*/

const WIDGET_ASSET_HOSTILE_CONTENT = <<<'HTML'
<p>Genuine <strong>praise</strong> from a customer.</p>
<ul><li>Fast <em>support</em></li></ul>
<p><a href="/case-studies">Read the case study</a></p>
<script>steal(document.cookie)</script>
<img src="x" onerror="steal()">
<p><a href="javascript:alert(1)">Click me</a></p>
<iframe src="https://evil.example"></iframe>
<style>body{display:none}</style>
<div onclick="steal()">Hover me</div>
HTML;

function assertWidgetAssetSanitisedRichText(string $html): void
{
    expect($html)
        ->not->toContain('<script')
        ->not->toContain('steal(')
        ->not->toContain('onerror')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('<iframe')
        ->not->toContain('<style')
        ->and($html)->toContain('<strong>praise</strong>')
        ->and($html)->toContain('<em>support</em>')
        ->and($html)->toContain('<li>')
        ->and($html)->toContain('href="/case-studies"')
        ->and($html)->toContain('Genuine');
}

function widgetAssetSanitisingRenderData(?string $content): WidgetAssetRenderData
{
    return new WidgetAssetRenderData(
        asset: null,
        image: null,
        linkedPage: null,
        translation: null,
        meta: [],
        alt: '',
        actions: [],
        accent: null,
        caption: null,
        content: $content,
        contentStructure: null,
        cropPreset: null,
        headingSize: 'h3',
        headingWeight: 'medium',
        hasTranslations: true,
        icon: null,
        linkText: null,
        linkUrl: null,
        position: null,
        role: null,
        social: [],
        status: null,
        tags: [],
        textAlign: null,
        title: 'Ada Lovelace',
    );
}

it('sanitises testimonial rich text before it reaches public HTML', function (): void {
    assertWidgetAssetSanitisedRichText(
        widgetAssetSanitisingRenderData(WIDGET_ASSET_HOSTILE_CONTENT)->safeContentHtml(),
    );
});

it('sanitises banner rich text before it reaches public HTML', function (): void {
    $banner = new AssetBannerItemData(
        image: null,
        alt: '',
        title: 'Spring release',
        content: WIDGET_ASSET_HOSTILE_CONTENT,
        url: '/spring',
        linkText: 'Read more',
    );

    assertWidgetAssetSanitisedRichText($banner->safeContentHtml());
});

it('returns an empty string for missing rich text', function (): void {
    expect(widgetAssetSanitisingRenderData(null)->safeContentHtml())->toBe('')
        ->and(widgetAssetSanitisingRenderData('   ')->safeContentHtml())->toBe('');
});

require_once __DIR__ . '/../Support/WidgetViewFixture.php';

it('renders sanitised rich text in the testimonial and banner views', function (string $view): void {
    $widget = foundationWidgetViewFixture(count: 1);
    $asset = foundationWidgetAssets($widget)->first();
    throw_unless($asset !== null, RuntimeException::class, 'Missing widget asset.');
    $page = $asset->getRelation('asset');
    throw_unless($page instanceof Model, RuntimeException::class, 'Missing asset.');
    $page->setRelation('translation', (object) [
        'title' => 'Ada Lovelace', 'content' => WIDGET_ASSET_HOSTILE_CONTENT, 'link_text' => null,
    ]);
    $items = collect([[
        'widgetAsset' => $asset,
        'renderData' => widgetAssetSanitisingRenderData(WIDGET_ASSET_HOSTILE_CONTENT),
    ]]);
    $html = renderFoundationWidgetFixture($view, $widget, ['assetRenderDataItems' => $items]);

    assertWidgetAssetSanitisedRichText($html);
})->with(['testimonials', 'banners']);

it('sanitises authored empty-listing copy in the rendered page view', function (): void {
    config()->set('capell-layout-builder.widget.skip_render_empty', false);
    $widget = foundationWidgetViewFixture(count: 0);
    $widget->setRelation('translation', new Translation([
        'meta' => ['no_results' => WIDGET_ASSET_HOSTILE_CONTENT],
    ]));
    $html = renderFoundationWidgetFixture('pages', $widget, [
        'pages' => collect(), 'widgetData' => [], 'showTitle' => false,
        'showContent' => false, 'index' => 0,
    ]);

    assertWidgetAssetSanitisedRichText($html);
});
