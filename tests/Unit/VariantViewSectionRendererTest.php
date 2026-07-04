<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\GenericSectionData;
use Capell\FoundationTheme\Rendering\VariantViewSectionRenderer;

beforeEach(function (): void {
    view()->addNamespace('capell-variant-fixtures', __DIR__ . '/../Fixtures/views');
});

it('renders the base view when no variant is requested', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-base',
        variantViews: ['split' => 'capell-variant-fixtures::variant-split'],
    );

    $section = new GenericSectionData('hero', ['heading' => 'Welcome']);

    expect($renderer->render($section))->toContain('base: Welcome');
});

it('renders the matching variant view when the section requests one', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-base',
        variantViews: ['split' => 'capell-variant-fixtures::variant-split'],
    );

    $section = new GenericSectionData('hero', ['heading' => 'Welcome', 'variant' => 'split']);

    expect($renderer->render($section))->toContain('split: Welcome');
});

it('falls back to the base view for an unknown variant key', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-base',
        variantViews: ['split' => 'capell-variant-fixtures::variant-split'],
    );

    $section = new GenericSectionData('hero', ['heading' => 'Welcome', 'variant' => 'does-not-exist']);

    expect($renderer->render($section))->toContain('base: Welcome');
});

it('degrades to fallback markup on render failure by default', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-missing',
        variantViews: [],
    );

    $section = new GenericSectionData('hero', []);

    expect($renderer->render($section))
        ->toContain('data-theme="foundation"')
        ->toContain('data-section="hero"');
});

it('rethrows the render failure when failLoudly is enabled', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-missing',
        variantViews: [],
        failLoudly: true,
    );

    $section = new GenericSectionData('hero', []);

    expect(fn () => $renderer->render($section))->toThrow(InvalidArgumentException::class);
});

it('never echoes the variant key into the rendered public HTML', function (): void {
    $renderer = new VariantViewSectionRenderer(
        themeKey: 'foundation',
        sectionKey: 'hero',
        baseView: 'capell-variant-fixtures::variant-base',
        variantViews: ['split' => 'capell-variant-fixtures::variant-split'],
    );

    $section = new GenericSectionData('hero', ['heading' => 'Welcome', 'variant' => 'split']);

    expect($renderer->render($section))->not->toContain('variant');
});
