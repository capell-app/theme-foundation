<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveFoundationSectionAnchorAction;

test('configured foundation anchors preserve canonical links and sanitize editor input', function (): void {
    expect(ResolveFoundationSectionAnchorAction::run(
        sectionType: 'Features',
        configuredAnchor: ' Featured Work! ',
        containerKey: 'Main',
        widgetKey: 'Foundation Features',
        widgetIndex: 0,
        occurrence: 1,
    ))->toBe('featured-work');
});

test('repeated configured anchors include their sanitized widget occurrence', function (): void {
    expect(ResolveFoundationSectionAnchorAction::run(
        sectionType: 'Features',
        configuredAnchor: 'features',
        containerKey: 'Main',
        widgetKey: 'Foundation Features #1',
        widgetIndex: 2,
        occurrence: 3,
    ))->toBe('features-foundation-features-1-3');
});

test('unconfigured anchors include sanitized section and widget context', function (): void {
    expect(ResolveFoundationSectionAnchorAction::run(
        sectionType: 'Contact Split',
        configuredAnchor: null,
        containerKey: 'Main Content',
        widgetKey: 'Contact / Sales',
        widgetIndex: 2,
        occurrence: 1,
    ))->toBe('contact-split-main-content-contact-sales-2-1');
});
