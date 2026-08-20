<?php

declare(strict_types=1);

use Capell\FoundationTheme\Data\AssetBannerItemData;
use Capell\FoundationTheme\Data\WidgetAssetRenderData;

/*
|--------------------------------------------------------------------------
| CAP-0232: author-supplied rich text must never reach public HTML raw
|--------------------------------------------------------------------------
|
| The testimonial and banner asset widgets render author rich text with
| `{!! !!}`. Both now interpolate the sanitised accessor on their render
| data, so the hostile-input assertions below cover the exact strings the
| public Blade emits.
*/

const CAP0232_HOSTILE_CONTENT = <<<'HTML'
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

function cap0232AssertSanitisedRichText(string $html): void
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

function cap0232WidgetAssetRenderData(?string $content): WidgetAssetRenderData
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
    cap0232AssertSanitisedRichText(
        cap0232WidgetAssetRenderData(CAP0232_HOSTILE_CONTENT)->safeContentHtml(),
    );
});

it('sanitises banner rich text before it reaches public HTML', function (): void {
    $banner = new AssetBannerItemData(
        image: null,
        alt: '',
        title: 'Spring release',
        content: CAP0232_HOSTILE_CONTENT,
        url: '/spring',
        linkText: 'Read more',
    );

    cap0232AssertSanitisedRichText($banner->safeContentHtml());
});

it('returns an empty string for missing rich text', function (): void {
    expect(cap0232WidgetAssetRenderData(null)->safeContentHtml())->toBe('')
        ->and(cap0232WidgetAssetRenderData('   ')->safeContentHtml())->toBe('');
});

it('keeps the raw rich-text call sites routed through the sanitiser', function (): void {
    $views = dirname(__DIR__, 2) . '/resources/views/components/widget/asset/';

    $testimonials = file_get_contents($views . 'testimonials.blade.php');
    $banners = file_get_contents($views . 'banners.blade.php');
    $pages = file_get_contents($views . 'pages.blade.php');

    expect($testimonials)->toBeString()
        ->and($testimonials)->toContain('$assetRenderData->safeContentHtml()')
        ->and($testimonials)->not->toContain('$content = $assetRenderData->content;')
        ->and($banners)->toBeString()
        ->and($banners)->toContain('{!! $bannerItem->safeContentHtml() !!}')
        ->and($banners)->not->toContain('{!! $bannerItem->content !!}')
        ->and($pages)->toBeString()
        ->and($pages)->toContain('PublicHtmlSanitizer::class)->sanitize(')
        ->and($pages)->not->toContain("{!! \$widget->translation?->getMeta('no_results')");
});
