<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\SectionVariantViewResolver;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    View::addNamespace('capell-section-variant-fixture', __DIR__ . '/../Fixtures/views/section-variants');
});

it('returns the base view when no variant is set', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero']))
        ->toBe('hero');
});

it('treats the default variant as the base view', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', [
        'type' => 'hero',
        'variant' => 'default',
    ]))->toBe('hero');
});

it('resolves a declared variant to its sidecar view', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', [
        'type' => 'hero',
        'variant' => 'compact',
    ]))->toBe('hero--compact');
});

it('falls back to the base view when the variant view is missing', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', [
        'type' => 'hero',
        'variant' => 'from-another-theme',
    ]))->toBe('hero');
});

it('returns null when neither the variant nor the base view exists', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', [
        'type' => 'not-shipped',
        'variant' => 'compact',
    ]))->toBeNull();
});

it('returns null when the section type is missing or not a string', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', []))->toBeNull()
        ->and(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => '']))->toBeNull()
        ->and(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => ['hero']]))->toBeNull();
});

it('ignores a non-string variant', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', [
        'type' => 'hero',
        'variant' => ['compact'],
    ]))->toBe('hero');
});

it('uses the active preset variant when the editor has not chosen one', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero'], 'compact'))
        ->toBe('hero--compact')
        ->and(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero', 'variant' => ''], 'compact'))
        ->toBe('hero--compact');
});

it('keeps the editor choice over the active preset variant, including an explicit default', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero', 'variant' => 'default'], 'compact'))
        ->toBe('hero')
        ->and(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero', 'variant' => 'compact'], 'from-another-theme'))
        ->toBe('hero--compact');
});

it('falls back to the base view when the preset variant is not shipped', function (): void {
    expect(SectionVariantViewResolver::resolve('capell-section-variant-fixture', ['type' => 'hero'], 'from-another-theme'))
        ->toBe('hero');
});
