<?php

declare(strict_types=1);

use Capell\FoundationTheme\Testing\AssertsPublicThemeAccessibility;
use Capell\FoundationTheme\Testing\AssertsPublicThemeOutputSafety;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

uses(AssertsPublicThemeOutputSafety::class);

/** @extends TestCase<MockObject> */
final class FoundationAccessibilityContractHarness extends TestCase
{
    use AssertsPublicThemeAccessibility;

    public function verifyRenderedDocument(string $html): void
    {
        $this->assertRenderedDocumentMeetsAccessibilityContract($html, 'Foundation public page shell');
    }
}

it('keeps the rendered Foundation page shell within the shared accessibility contract', function (): void {
    $page = view('capell-theme-foundation::theme.page', [
        'brand' => new readonly class
        {
            /**
             * @return array<string, string>
             */
            public function tokens(): array
            {
                return [
                    '--theme-primary' => '#315f8f',
                    '--theme-surface' => '#faf9f7',
                    '--theme-foreground' => '#111827',
                    '--theme-body-font' => 'Inter',
                ];
            }
        },
        'content' => '<article><h1>Foundation public page</h1><h2>Latest work</h2><p>Public content.</p></article>',
    ])->render();

    $document = sprintf(
        '<!DOCTYPE html><html lang="en"><head><title>Foundation public page</title></head><body>%s</body></html>',
        $page,
    );

    expect($page)
        ->toContain('id="main-content"')
        ->toContain('id="theme-status"')
        ->toContain('Foundation public page');

    new FoundationAccessibilityContractHarness('verifyRenderedDocument')->verifyRenderedDocument($document);
});

/*
 * Fleet backstop for Wave 1.1: every `packages/theme-*` directory
 * (including theme-foundation itself) is independently re-scanned for
 * authoring/package-metadata leaks here, regardless of whether its own
 * `tests/Unit/PublicOutputSafetyTest.php` wires the shared trait in. This
 * guards against a newly added theme that forgets to wire the trait into
 * its own test file — it would still be caught by this metadata-only
 * backstop.
 *
 * Only the metadata check runs here (not the DB-query check): layout-native
 * themes intentionally allow Frontend::/getMeta() calls outside their
 * legacy `sections/*.blade.php` copies, and a fleet-level test has no way
 * to know, per theme, which views are "legacy" and which are live-pipeline.
 */
it('keeps every theme package fleet-wide free of authoring or package metadata', function (): void {
    $repositoryRoot = dirname(__DIR__, 4);

    $themeDirectories = glob($repositoryRoot . '/packages/theme-*', GLOB_ONLYDIR) ?: [];

    expect($themeDirectories)->not->toBeEmpty();

    // theme-foundation is the base package every child theme extends; it is
    // the one legitimate place that wires up Livewire for the whole fleet
    // (app.blade.php's @livewireScripts / wire:navigate integration), so
    // those two tokens are exempted for it alone. Every other theme, and
    // every other banned token for theme-foundation itself, is still
    // checked in full.
    $exemptBannedTokensByThemeSlug = [
        'theme-foundation' => ['Livewire', 'wire:'],
    ];

    foreach ($themeDirectories as $themeDirectory) {
        $themeSlug = basename($themeDirectory);
        $viewsDirectory = $themeDirectory . '/resources/views';

        if (! is_dir($viewsDirectory)) {
            continue;
        }

        $this->assertThemeOutputMetadataIsSafe(
            $viewsDirectory,
            'capell-app/' . $themeSlug,
            $exemptBannedTokensByThemeSlug[$themeSlug] ?? [],
        );
    }
});
