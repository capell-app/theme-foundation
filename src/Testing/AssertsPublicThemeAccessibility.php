<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Testing;

use Capell\Core\Support\Accessibility\PublicAccessibilityContract;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Canonical accessibility assertions for theme Pest suites, backed by Core's
 * {@see PublicAccessibilityContract}.
 *
 * This is the accessibility counterpart of
 * {@see AssertsPublicThemeOutputSafety}. Capell ships 30 `theme-*` packages
 * and 11 `widget-*` packages but only a handful of hand-rolled accessibility
 * tests, each asserting a different theme-specific token
 * (`expect($blade)->toContain('aria-label=')`). Those tests prove a token is
 * *present somewhere*; they cannot prove the theme's markup actually satisfies
 * an invariant. Core's contract states the invariants once; this trait is the
 * per-theme adapter that feeds theme output into it.
 *
 * Two ways in, matching how much a given theme test can afford to set up:
 *
 * - {@see assertRenderedHtmlMeetsAccessibilityContract()} /
 *   {@see assertRenderedDocumentMeetsAccessibilityContract()} take genuinely
 *   rendered HTML. Preferred wherever the test can hydrate the view's data,
 *   because it inspects exactly what an anonymous visitor receives.
 * - {@see assertThemeBladeMeetsAccessibilityContract()} sweeps every
 *   `*.blade.php` under a theme's `resources/views`, normalising each file to
 *   a static HTML skeleton first. This is what makes a fleet-wide and
 *   newly-scaffolded-theme contract possible at all: a theme's 20-plus
 *   section views each need different hydrated props, so rendering all of them
 *   in one test is not practical, while the structural invariants (alt text,
 *   accessible names, label association, heading nesting, ARIA
 *   well-formedness, tab order) are already decided by the literal markup.
 *
 * The skeleton normalisation is deliberately conservative — it only ever makes
 * the contract *more* permissive, never less:
 *
 * - `{{ … }}` / `{!! … !!}` become a non-empty text sentinel, so a control
 *   named by `{{ __('capell-theme-x::…') }}` counts as named. Only a literally
 *   empty accessible name is reported.
 * - `<x-…>` component tags become opaque wrappers via
 *   {@see PublicAccessibilityContract::OPAQUE_CONTENT_ATTRIBUTE}, because a
 *   component's slot content is unknowable from the call site.
 * - Blade directives are stripped while their bodies are kept, so both arms of
 *   an `@if`/`@else` are inspected. That flattening is why the duplicate-id
 *   check is skipped in skeleton mode: two branches legitimately reuse one id.
 *
 * Mix in via `uses(AssertsPublicThemeAccessibility::class);` and call an
 * `assert*` method from an `it()` block.
 */
trait AssertsPublicThemeAccessibility
{
    /**
     * Non-empty stand-in for a Blade echo. An ASCII token, so it stays valid
     * both as element text and as an attribute name when a view interpolates
     * a whole attribute bag (`<button {{ $attributes }}>`).
     */
    private const string BLADE_VALUE_SENTINEL = 'capell-blade-value';

    /**
     * Violation fragments that skeleton mode cannot decide, because stripping
     * Blade control flow flattens mutually exclusive branches into one tree.
     *
     * @var list<string>
     */
    private const array SKELETON_MODE_UNDECIDABLE = ['duplicate ids'];

    /**
     * Asserts a rendered component/section fragment satisfies the contract.
     */
    protected function assertRenderedHtmlMeetsAccessibilityContract(string $html, string $description = 'rendered theme output'): void
    {
        $violations = new PublicAccessibilityContract()->inspectFragment($html);

        $this->assertSame(
            [],
            $violations,
            sprintf("The %s violates the public accessibility contract:\n- %s", $description, implode("\n- ", $violations)),
        );
    }

    /**
     * Asserts a rendered whole public page satisfies the contract, including
     * the document-scoped landmark, heading and language invariants.
     */
    protected function assertRenderedDocumentMeetsAccessibilityContract(string $html, string $description = 'rendered theme document'): void
    {
        $violations = new PublicAccessibilityContract()->inspectDocument($html);

        $this->assertSame(
            [],
            $violations,
            sprintf("The %s violates the public accessibility contract:\n- %s", $description, implode("\n- ", $violations)),
        );
    }

    /**
     * Sweeps every Blade view under `$viewsDirectory`, normalises each to a
     * static HTML skeleton, and asserts the skeleton satisfies the contract's
     * fragment-level invariants.
     *
     * A missing directory passes: a freshly scaffolded theme owns no views yet
     * and inherits everything from `theme-foundation`, exactly as the
     * public-output safety sweep already behaves.
     *
     * @param  list<string>  $exemptViewPaths  View paths, relative to
     *                                         `$viewsDirectory`, excluded from the sweep. Each entry must be
     *                                         justified in the calling test — this is an escape hatch for
     *                                         markup the skeleton cannot represent, not for real defects.
     */
    protected function assertThemeBladeMeetsAccessibilityContract(string $viewsDirectory, array $exemptViewPaths = []): void
    {
        $contract = new PublicAccessibilityContract;
        $viewsDirectory = rtrim($viewsDirectory, '/');
        $failures = [];

        foreach (self::bladeViewPaths($viewsDirectory) as $viewPath) {
            $relativePath = ltrim(str_replace($viewsDirectory, '', $viewPath), '/');

            if (in_array($relativePath, $exemptViewPaths, true)) {
                continue;
            }

            $skeleton = self::bladeToHtmlSkeleton((string) file_get_contents($viewPath));

            foreach ($contract->inspectSkeletonFragment($skeleton) as $violation) {
                if (self::isUndecidableInSkeletonMode($violation)) {
                    continue;
                }

                $failures[] = $relativePath . ': ' . $violation;
            }
        }

        $this->assertSame(
            [],
            $failures,
            sprintf(
                "%d public Blade view(s) under %s violate the public accessibility contract:\n- %s",
                count($failures),
                $viewsDirectory,
                implode("\n- ", $failures),
            ),
        );
    }

    private static function isUndecidableInSkeletonMode(string $violation): bool
    {
        foreach (self::SKELETON_MODE_UNDECIDABLE as $undecidable) {
            if (str_contains($violation, $undecidable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduces Blade source to the static HTML skeleton it will always render,
     * substituting non-empty sentinels for dynamic values and opaque wrappers
     * for component calls. See the trait docblock for why each substitution is
     * safe in the permissive direction.
     */
    private static function bladeToHtmlSkeleton(string $blade): string
    {
        $opaqueAttribute = PublicAccessibilityContract::OPAQUE_CONTENT_ATTRIBUTE;

        $replacements = [
            // Blade comments, `@php` blocks, and script/style bodies contribute
            // no markup and can contain characters that derail HTML parsing.
            '/\{\{--.*?--\}\}/s' => '',
            '/@php\b.*?@endphp\b/s' => '',
            '/<script\b[^>]*>.*?<\/script>/is' => '',
            '/<style\b[^>]*>.*?<\/style>/is' => '',
            // Echoes become a non-empty sentinel — raw echoes first, so the
            // `{{ … }}` pattern cannot chew the `{!! … !!}` delimiters.
            '/\{!!.*?!!\}/s' => self::BLADE_VALUE_SENTINEL,
            '/\{\{.*?\}\}/s' => self::BLADE_VALUE_SENTINEL,
            // Component calls become opaque wrappers. Self-closing first: an
            // unclosed `<div>` would otherwise swallow every later sibling.
            '/<x-[^>]*?\/>/s' => '<div ' . $opaqueAttribute . '="1"></div>',
            '/<x-[\w.:-]*/s' => '<div ' . $opaqueAttribute . '="1"',
            '/<\/x-[\w.:-]*>/s' => '</div>',
            // Directives are removed while their bodies stay, so every branch
            // of an `@if`/`@else` is inspected. The recursive `(?1)` subroutine
            // matches balanced parentheses at any nesting depth.
            '/@[a-zA-Z]\w*(?:\s*(\((?:[^()]++|(?1))*\)))?/s' => '',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $blade = preg_replace($pattern, $replacement, $blade) ?? $blade;
        }

        return $blade;
    }

    /**
     * @return list<string>
     */
    private static function bladeViewPaths(string $viewsDirectory): array
    {
        if (! is_dir($viewsDirectory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDirectory, FilesystemIterator::SKIP_DOTS),
        );
        $paths = [];

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
                continue;
            }

            if (! str_ends_with($fileInfo->getFilename(), '.blade.php')) {
                continue;
            }

            $paths[] = $fileInfo->getPathname();
        }

        sort($paths, SORT_STRING);

        return $paths;
    }
}
