<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\GenericSectionData;
use Illuminate\Support\Facades\View;

/*
 * Wave 4c §D render coverage: every new/changed "transparent, delightful
 * utility" section view (pricing-value-spectrum, faq-search-discovery,
 * changelog-stream, stats-display-band, and the "encouraging" form
 * variant) must render without error given a minimal payload, using the
 * same view names FoundationThemeServiceProvider::themeStudioSectionRenderers()
 * wires into VariantViewSectionRenderer.
 */

beforeEach(function (): void {
    View::addNamespace('capell-theme-foundation', dirname(__DIR__, 2) . '/resources/views');
});

it('renders every declared pricing-value-spectrum variant view without error', function (string $view): void {
    $section = new GenericSectionData(
        type: 'pricing-value-spectrum',
        data: [
            'heading' => 'Pick your plan',
            'summary' => 'Drag to see what unlocks.',
            'tiers' => [
                ['label' => 'Starter', 'price' => '$0', 'features' => ['One site'], 'ctaLabel' => 'Start free', 'ctaUrl' => '/signup'],
                ['label' => 'Growth', 'price' => '$29', 'features' => ['Five sites', 'Priority support'], 'ctaLabel' => 'Upgrade', 'ctaUrl' => '/upgrade'],
                ['label' => 'Scale', 'price' => '$99', 'features' => ['Unlimited sites', 'SLA'], 'ctaLabel' => 'Talk to sales', 'ctaUrl' => '/contact'],
            ],
        ],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('Pick your plan')
        ->toContain('Starter')
        ->toContain('data-pricing-spectrum-slider');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.pricing-value-spectrum'],
    'compact' => ['capell-theme-foundation::theme.sections.pricing-value-spectrum--compact'],
]);

it('renders the pricing-value-spectrum empty state without error', function (): void {
    $section = new GenericSectionData(type: 'pricing-value-spectrum', data: ['tiers' => []]);

    $html = view('capell-theme-foundation::theme.sections.pricing-value-spectrum', ['section' => $section])->render();

    expect($html)->toContain('No pricing plans configured.');
});

it('renders every declared faq-search-discovery variant view without error', function (string $view): void {
    $section = new GenericSectionData(
        type: 'faq-search-discovery',
        data: [
            'heading' => 'Frequently asked questions',
            'items' => [
                ['question' => 'What is Capell?', 'answer' => 'A CMS.', 'category' => 'General'],
                ['question' => 'How do I install a theme?', 'answer' => 'Via Composer.', 'category' => 'Themes'],
            ],
        ],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('Frequently asked questions')
        ->toContain('What is Capell?')
        ->toContain('data-faq-search-input');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.faq-search-discovery'],
    'categorised' => ['capell-theme-foundation::theme.sections.faq-search-discovery--categorised'],
]);

it('renders the faq-search-discovery empty state without error', function (): void {
    $section = new GenericSectionData(type: 'faq-search-discovery', data: ['items' => []]);

    $html = view('capell-theme-foundation::theme.sections.faq-search-discovery', ['section' => $section])->render();

    expect($html)->toContain('No FAQs configured.');
});

it('renders every declared changelog-stream variant view without error', function (string $view): void {
    $section = new GenericSectionData(
        type: 'changelog-stream',
        data: [
            'heading' => 'What is new',
            'entries' => [
                ['version' => 'v1.2.0', 'date' => '2026-07-01', 'tag' => 'Feature', 'title' => 'Faster search', 'summary' => 'Search results now load instantly.'],
                ['version' => 'v1.1.0', 'date' => '2026-06-15', 'tag' => 'Fix', 'title' => 'Fixed pagination', 'summary' => 'Pagination links now work on mobile.'],
            ],
        ],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('What is new')
        ->toContain('v1.2.0')
        ->toContain('Faster search');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.changelog-stream'],
    'grid' => ['capell-theme-foundation::theme.sections.changelog-stream--grid'],
]);

it('renders the changelog-stream empty state without error', function (): void {
    $section = new GenericSectionData(type: 'changelog-stream', data: ['entries' => []]);

    $html = view('capell-theme-foundation::theme.sections.changelog-stream', ['section' => $section])->render();

    expect($html)->toContain('No changelog entries configured.');
});

it('renders every declared stats-display-band variant view without error', function (string $view): void {
    $section = new GenericSectionData(
        type: 'stats-display-band',
        data: [
            'heading' => 'By the numbers',
            'stats' => [
                ['value' => 4200, 'label' => 'Happy customers', 'suffix' => '+'],
                ['value' => 99, 'label' => 'Uptime', 'suffix' => '%'],
            ],
        ],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('By the numbers')
        ->toContain('Happy customers')
        ->toContain('data-count-up="4200"');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.stats-display-band'],
    'light' => ['capell-theme-foundation::theme.sections.stats-display-band--light'],
]);

it('renders the stats-display-band empty state without error', function (): void {
    $section = new GenericSectionData(type: 'stats-display-band', data: ['stats' => []]);

    $html = view('capell-theme-foundation::theme.sections.stats-display-band', ['section' => $section])->render();

    expect($html)->toContain('No stats configured.');
});

it('renders the form--encouraging variant view without error', function (): void {
    $section = new GenericSectionData(
        type: 'form',
        data: [
            'heading' => 'Get in touch',
            'action' => '/contact',
            'submitLabel' => 'Send message',
            'fields' => [
                ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'encouragement' => 'Nice to meet you.'],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'encouragement' => 'Great, we will reach you there.'],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message', 'encouragement' => 'Thanks for sharing the details.'],
            ],
        ],
    );

    $html = view('capell-theme-foundation::theme.sections.form--encouraging', ['section' => $section])->render();

    expect($html)
        ->toContain('Get in touch')
        ->toContain('Send message')
        ->toContain('data-form-hint-field')
        ->toContain('aria-live="polite"')
        ->toContain('data-form-encouraging');
});
