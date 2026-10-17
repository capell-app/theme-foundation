<?php

declare(strict_types=1);
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
use Capell\ThemeSubmissions\Support\Demo\SubmissionsDemoContent;
use Illuminate\Support\Facades\View;

test('directory card density container queries compile without comma warnings', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $directoryCss = file_get_contents($packagesPath . '/theme-directory/resources/css/theme-directory.css');

    expect($directoryCss)
        ->toContain('@container pfd-tokens style(--theme-card-density: compact)')
        ->toContain('@container pfd-tokens style(--theme-card-density: dense)')
        ->not->toContain('@container pfd-tokens style(--theme-card-density: compact),');
});

test('catalogue onepage and saas navigation ctas fall back from empty labels', function (string $theme): void {
    View::addNamespace('capell-theme-' . $theme, dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views');
    /** @var view-string $navigationView */
    $navigationView = implode('', ['capell-theme-', $theme, '::sections.navigation']);
    $html = view($navigationView, ['section' => [
        'items' => [], 'ctaLabel' => '', 'ctaUrl' => '/contact',
    ]])->render();

    expect($html)->toContain(__('capell-theme-' . $theme . '::sections.navigation.cta'), 'href="/contact"');
})->with(['catalogue', 'onepage', 'saas']);

test('theme section navigations expose the shared mobile disclosure menu', function (): void {
    require_once __DIR__ . '/../Support/ThemeNavigationAssertions.php';

    foreach (['agency', 'awards', 'brutalist', 'catalogue', 'curated', 'directory', 'editorial', 'magazine', 'minimalist', 'onepage', 'platform', 'portfolio', 'saas', 'showreel', 'submissions'] as $theme) {
        assertThemeNavigationOutcomes($theme, requireCurrentPage: false);
    }
});

test('platform contact screenshots wait for the rendered contact hero', function (): void {
    // The capture contract must target the contact hero rather than a nonexistent form.
    /** @var array{entries: list<array{id: string, waitFor: string}>} $manifest */
    $manifest = capell_json_file_array(dirname(__DIR__, 3) . '/theme-platform/docs/screenshots.json');
    $contacts = collect($manifest['entries'])->filter(fn (array $entry): bool => str_starts_with($entry['id'], 'platform-contact'));

    expect($contacts)->not->toBeEmpty();

    foreach ($contacts as $entry) {
        expect($entry['waitFor'])->toBe('#hero');
    }
});

test('submissions contact and cta demo pages seed distinct page records', function (): void {
    $definitions = collect((new SubmissionsDemoContent)->definitions('submissions', 'Submissions', 'https://submissions.test'));
    $contact = $definitions->firstWhere('surface', 'contact');
    $cta = $definitions->firstWhere('surface', 'cta');

    expect($contact)->toBeInstanceOf(ThemeDemoPageDefinition::class)
        ->and($cta)->toBeInstanceOf(ThemeDemoPageDefinition::class);

    throw_unless($contact instanceof ThemeDemoPageDefinition && $cta instanceof ThemeDemoPageDefinition, RuntimeException::class, 'Missing demo definitions.');

    expect($contact->slug)->toBe('theme-submissions-contact')
        ->and($cta->slug)->toBe('theme-submissions-cta')
        ->and($contact->name)->not->toBe($cta->name);
});
