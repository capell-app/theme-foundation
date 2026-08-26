<?php

declare(strict_types=1);

use Capell\FoundationTheme\Data\WidgetAssetRenderData;
use Illuminate\Support\Facades\View;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Livewire\Blaze\Blaze;

/*
|--------------------------------------------------------------------------
| Author-supplied widget URLs must never reach public HTML unsanitised
|--------------------------------------------------------------------------
|
| The modern pricing-table and card-grid widgets render CMS-authored widget
| meta straight into an `href`. Blade escaping does not neutralise a
| `javascript:` scheme in href context, so both now route the value through
| PublicUrlSanitizer. These are render-level assertions on the emitted HTML
| rather than source-text greps, so a future refactor that re-introduces the
| raw value fails here regardless of how it is spelled.
*/

const HOSTILE_WIDGET_URL = 'javascript:alert(1)';
const LEGITIMATE_WIDGET_URL = 'https://capell.test/pricing/upgrade';

/**
 * The real widget wrapper needs a persisted Widget model, the Frontend facade
 * and Layout Builder container resolution. None of that participates in URL
 * sanitising, so a fixture namespace shadows it with a bare slot.
 */
function stubFoundationWidgetWrapper(): void
{
    View::prependNamespace(
        'capell-theme-foundation',
        dirname(__DIR__) . '/Fixtures/views/url-safety',
    );
}

function urlSafetyWidgetStub(): object
{
    return new class
    {
        public int $id = 1;

        public string $key = 'url-safety';

        public mixed $translation = null;

        public function getMeta(string $key, mixed $default = null): mixed
        {
            return $default;
        }
    };
}

function urlSafetyLoopStub(): object
{
    return new class
    {
        public int $index = 0;

        public bool $first = true;
    };
}

/**
 * @param  array<string, mixed>  $meta
 */
function urlSafetyRenderData(array $meta): WidgetAssetRenderData
{
    return new WidgetAssetRenderData(
        asset: null,
        image: null,
        linkedPage: null,
        translation: null,
        meta: $meta,
        alt: '',
        actions: [],
        accent: 'teal',
        caption: null,
        content: 'Everything you need to get started.',
        contentStructure: null,
        cropPreset: null,
        headingSize: 'h3',
        headingWeight: 'medium',
        hasTranslations: true,
        icon: null,
        linkText: null,
        linkUrl: null,
        position: null,
        role: 'card',
        social: [],
        status: null,
        tags: [],
        textAlign: null,
        title: 'Starter',
    );
}

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $data
 */
function renderUrlSafetyWidget(string $view, array $data): string
{
    stubFoundationWidgetWrapper();

    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        return view($view, [
            ...$data,
            'attributes' => new ComponentAttributeBag,
            'slot' => new ComponentSlot(''),
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
}

/**
 * @return array<string, mixed>
 */
function urlSafetyPricingTableData(string $ctaUrl): array
{
    return [
        'assetRenderDataItems' => [
            ['renderData' => urlSafetyRenderData(['cta_url' => $ctaUrl])],
        ],
        'currency' => '£',
        'billingOptions' => 'monthly',
        'container' => null,
        'containerKey' => 'main',
        'containerWidth' => null,
        'loop' => urlSafetyLoopStub(),
        'widget' => urlSafetyWidgetStub(),
    ];
}

/**
 * @return array<string, mixed>
 */
function urlSafetyCardGridData(string $linkUrl): array
{
    $renderData = urlSafetyRenderData([
        'link_text' => 'Read more',
        'link_url' => $linkUrl,
    ]);

    return [
        'assetRenderDataItems' => [['renderData' => $renderData]],
        'assets' => collect([$renderData]),
        'columns' => 3,
        'container' => null,
        'containerKey' => 'main',
        'containerWidth' => null,
        'loop' => urlSafetyLoopStub(),
        'widget' => urlSafetyWidgetStub(),
    ];
}

it('drops a javascript: scheme CTA from the rendered pricing table', function (): void {
    $html = renderUrlSafetyWidget(
        'capell-theme-foundation::components.widget.modern.pricing-table',
        urlSafetyPricingTableData(HOSTILE_WIDGET_URL),
    );

    expect($html)
        ->toContain('<a')
        ->not->toContain('javascript:')
        ->not->toContain('alert(1)')
        ->and($html)->toContain('href="#"');
});

it('keeps a legitimate https CTA in the rendered pricing table', function (): void {
    $html = renderUrlSafetyWidget(
        'capell-theme-foundation::components.widget.modern.pricing-table',
        urlSafetyPricingTableData(LEGITIMATE_WIDGET_URL),
    );

    expect($html)->toContain('href="' . LEGITIMATE_WIDGET_URL . '"');
});

it('drops a javascript: scheme card link from the rendered card grid', function (): void {
    $html = renderUrlSafetyWidget(
        'capell-theme-foundation::components.widget.modern.card-grid',
        urlSafetyCardGridData(HOSTILE_WIDGET_URL),
    );

    expect($html)
        ->toContain('ap-card')
        ->not->toContain('javascript:')
        ->not->toContain('alert(1)')
        ->and($html)->not->toContain('ap-card-link');
});

it('keeps a legitimate https card link in the rendered card grid', function (): void {
    $html = renderUrlSafetyWidget(
        'capell-theme-foundation::components.widget.modern.card-grid',
        urlSafetyCardGridData(LEGITIMATE_WIDGET_URL),
    );

    expect($html)
        ->toContain('ap-card-link')
        ->toContain('href="' . LEGITIMATE_WIDGET_URL . '"');
});
