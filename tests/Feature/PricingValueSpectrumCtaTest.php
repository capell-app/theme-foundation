<?php

declare(strict_types=1);

/**
 * @param  list<array<string, mixed>>  $tiers
 * @return array{cta: DOMElement|null, deadLinks: int, tiers: list<array<string, mixed>>}
 */
function renderPricingValueSpectrum(array $tiers): array
{
    $html = view('capell-theme-foundation::theme.sections.pricing-value-spectrum', [
        'section' => (object) ['heading' => 'Plans', 'tiers' => $tiers],
    ])->render();

    $document = new DOMDocument;
    $previousUseInternalErrors = libxml_use_internal_errors(true);

    try {
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);
    }

    $xpath = new DOMXPath($document);
    $cta = $xpath->query('//a[@data-pricing-spectrum-cta]');
    $deadLinks = $xpath->query('//a[@href="#"]');
    $template = $xpath->query('//template[@data-pricing-spectrum-tier-data]');

    throw_unless($cta instanceof DOMNodeList && $deadLinks instanceof DOMNodeList && $template instanceof DOMNodeList, RuntimeException::class, 'Expected the pricing section to be queryable.');

    $templateNode = $template->item(0);

    throw_unless($templateNode instanceof DOMElement, RuntimeException::class, 'Expected the tier payload template.');

    $tierData = json_decode(trim($templateNode->textContent), true, flags: JSON_THROW_ON_ERROR);

    throw_unless(is_array($tierData) && array_is_list($tierData), RuntimeException::class, 'Expected the tier payload to be a list.');

    $ctaNode = $cta->item(0);

    /** @var list<array<string, mixed>> $tierData */
    return [
        'cta' => $ctaNode instanceof DOMElement ? $ctaNode : null,
        'deadLinks' => $deadLinks->length,
        'tiers' => $tierData,
    ];
}

it('renders a hidden call to action when only a later tier links somewhere', function (): void {
    $rendered = renderPricingValueSpectrum([
        ['label' => 'Starter', 'price' => '£9', 'features' => ['One site'], 'ctaLabel' => 'Talk to us'],
        ['label' => 'Team', 'price' => '£29', 'features' => [], 'ctaLabel' => 'Start trial', 'ctaUrl' => '/trial'],
    ]);

    expect($rendered['cta'])->toBeInstanceOf(DOMElement::class)
        ->and($rendered['cta']?->hasAttribute('hidden'))->toBeTrue()
        ->and($rendered['cta']?->hasAttribute('href'))->toBeFalse()
        ->and($rendered['deadLinks'])->toBe(0);
});

it('publishes only sanitised tier links to the slider', function (): void {
    $rendered = renderPricingValueSpectrum([
        ['label' => 'Starter', 'price' => '£9', 'ctaLabel' => 'Buy', 'ctaUrl' => '/buy'],
        ['label' => 'Team', 'price' => '£29', 'ctaLabel' => 'Buy', 'ctaUrl' => 'javascript:alert(1)'],
    ]);

    expect($rendered['tiers'][0])->toMatchArray(['ctaLabel' => 'Buy', 'ctaUrl' => '/buy'])
        ->and($rendered['tiers'][1])->toMatchArray(['ctaLabel' => null, 'ctaUrl' => null])
        ->and($rendered['cta']?->getAttribute('href'))->toBe('/buy')
        ->and($rendered['cta']?->hasAttribute('hidden'))->toBeFalse();
});

it('omits the call to action when no tier links anywhere', function (): void {
    $rendered = renderPricingValueSpectrum([
        ['label' => 'Starter', 'price' => '£9', 'ctaLabel' => 'Buy'],
    ]);

    expect($rendered['cta'])->toBeNull();
});
