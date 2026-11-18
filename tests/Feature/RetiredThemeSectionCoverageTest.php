<?php

declare(strict_types=1);

use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSectionMap;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSuccessors;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\SplFileInfo;

/*
 * `capell:theme-migrate` refuses a move while a widget's saved section type has
 * no view in the successor theme's own `sections` namespace and no map entry
 * points it at a section that has one. Every retired theme must therefore have
 * each of its base section views answered by one of the two.
 *
 * A section with no successor equivalent and no renderable on the successor
 * stays unmapped on purpose, so the move keeps refusing it.
 */
dataset('retired section coverage routes', [
    'magazine into editorial' => ['magazine', 'editorial', []],
    'blog into editorial' => ['blog', 'editorial', []],
    'broadsheet into editorial' => ['broadsheet', 'editorial', []],
    // Folio draws `generic` for a section type it does not support; no widget saves that type.
    'folio into editorial' => ['folio', 'editorial', ['generic']],
    'bistro into business' => ['bistro', 'business', []],
    'events into business' => ['events', 'business', []],
    'grounds into business' => ['grounds', 'business', ['unsupported']],
    'almanac into business' => ['almanac', 'business', []],
    'knowledge into platform' => ['knowledge', 'platform', []],
    // Switchboard's section and resolved-section views draw any saved section; neither is a saved type.
    'switchboard into platform' => ['switchboard', 'platform', ['resolved-section', 'section']],
    'liquid-glass into platform' => ['liquid-glass', 'platform', []],
    'showreel into portfolio' => ['showreel', 'portfolio', []],
    'submissions into portfolio' => ['submissions', 'portfolio', []],
    'curated into portfolio' => ['curated', 'portfolio', []],
    'saas into portfolio' => ['saas', 'portfolio', []],
    'agency into portfolio' => ['agency', 'portfolio', []],
    'onepage into portfolio' => ['onepage', 'portfolio', []],
    // Paperdesk draws `empty` for a section type it does not support; no widget saves that type.
    'paperdesk into portfolio' => ['paperdesk', 'portfolio', ['empty']],
    'catalogue into portfolio' => ['catalogue', 'portfolio', []],
    'directory into portfolio' => ['directory', 'portfolio', []],
    'minimalist into portfolio' => ['minimalist', 'portfolio', []],
    'brutalist into portfolio' => ['brutalist', 'portfolio', []],
    'awards into portfolio' => ['awards', 'portfolio', []],
]);

/**
 * @return list<string> the section types stored in meta.type: a variant is saved in meta.variant, so only base views count
 */
function retiredCoverageSectionTypes(string $theme): array
{
    // Folio keeps its section views under widget/sections; every other theme under sections.
    $views = dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views';
    $files = [];

    // These themes draw their own sections straight from widget/, beside their layout views.
    $directories = [$views . '/sections', $views . '/widget/sections', ...(in_array($theme, ['events', 'knowledge', 'switchboard', 'liquid-glass'], true) ? [$views . '/widget'] : [])];

    foreach (array_filter($directories, is_dir(...)) as $directory) {
        $files = [...$files, ...File::files($directory)];
    }

    $types = array_values(array_unique(array_map(static fn (SplFileInfo $file): string => $file->getBasename('.blade.php'), $files)));
    $types = array_values(array_filter($types, static fn (string $type): bool => ! str_contains($type, '--')));
    sort($types);

    return $types;
}

/**
 * A successor draws a section through its own view, or through Foundation's
 * renderable for a standard section, which every theme inherits.
 */
function retiredCoverageSuccessorDraws(string $namespace, string $type): bool
{
    return View::exists($namespace . '::sections.' . $type) || FoundationSectionWidgetComponentEnum::fromSectionType($type) instanceof FoundationSectionWidgetComponentEnum;
}

it('answers every section view of a retired theme with a successor view or a map entry', function (string $retired, string $successor, array $unmapped): void {
    $namespace = 'capell-theme-' . $successor;
    View::addNamespace($namespace, dirname(__DIR__, 3) . '/theme-' . $successor . '/resources/views');
    $map = resolve(RetiredThemeSectionMap::class);
    $uncovered = [];
    $mappedOntoMissingView = [];

    foreach (retiredCoverageSectionTypes($retired) as $type) {
        if (retiredCoverageSuccessorDraws($namespace, $type)) {
            continue;
        }

        $mapping = $map->for($retired, $type);

        if ($mapping === null) {
            $uncovered[] = $type;

            continue;
        }

        if (! retiredCoverageSuccessorDraws($namespace, $mapping['type'])) {
            $mappedOntoMissingView[] = $type . ' -> ' . $mapping['type'];
        }
    }

    expect($uncovered)->toBe($unmapped, 'Retired ' . $retired . ' sections with neither a ' . $successor . ' view nor a map entry')
        ->and($mappedOntoMissingView)->toBe([]);
})->with('retired section coverage routes');

it('keys each successor default label to a translation that successor theme defines for a section it has', function (): void {
    $successors = array_values(resolve(RetiredThemeSuccessors::class)->all());

    foreach (resolve(RetiredThemeSectionMap::class)->allSuccessorDefaultLabels() as $successor => $sections) {
        expect($successors)->toContain($successor);

        foreach ($sections as $sectionType => $keys) {
            expect(File::exists(dirname(__DIR__, 3) . '/theme-' . $successor . '/resources/views/sections/' . $sectionType . '.blade.php'))->toBeTrue();

            foreach ($keys as $key) {
                [$namespace, $path] = explode('::', $key, 2);
                [$file, $entry] = explode('.', $path, 2);
                expect($namespace)->toBe('capell-theme-' . $successor);

                $lines = require dirname(__DIR__, 3) . '/theme-' . $successor . '/resources/lang/en/' . $file . '.php';

                expect(data_get($lines, $entry))->toBeString()->not->toBe('');
            }
        }
    }
});
