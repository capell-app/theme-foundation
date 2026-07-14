<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ValidateThemeCatalogueEntryAction;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Illuminate\Filesystem\Filesystem;

it('reports missing buyer documentation and public output safety evidence', function (): void {
    $filesystem = new Filesystem;
    $fixture = createCommercialReadinessFixture($filesystem);

    try {
        $result = ValidateThemeCatalogueEntryAction::run('theme-fixture', $fixture['packagesRoot']);

        expect($result->violations)
            ->toContain('default: README.md is missing.')
            ->toContain('default: a public-output safety test is missing.');
    } finally {
        $filesystem->deleteDirectory($fixture['root']);
    }
});

it('accepts either package-local or foundation fleet safety evidence', function (string $safetyTestPath): void {
    $filesystem = new Filesystem;
    $fixture = createCommercialReadinessFixture($filesystem);

    try {
        $filesystem->put($fixture['packageRoot'] . '/README.md', "# Fixture theme\n");
        $filesystem->ensureDirectoryExists(dirname($fixture['packageRoot'] . '/' . $safetyTestPath));
        $filesystem->put($fixture['packageRoot'] . '/' . $safetyTestPath, "<?php\n\ndeclare(strict_types=1);\n");

        $result = ValidateThemeCatalogueEntryAction::run('theme-fixture', $fixture['packagesRoot']);

        expect($result->violations)->toBe([]);
    } finally {
        $filesystem->deleteDirectory($fixture['root']);
    }
})->with([
    'child theme package contract' => 'tests/Unit/PublicOutputSafetyTest.php',
    'foundation fleet contract' => 'tests/Feature/FleetPublicOutputSafetyTest.php',
]);

/**
 * @return array{root: string, packagesRoot: string, packageRoot: string}
 */
function createCommercialReadinessFixture(Filesystem $filesystem): array
{
    $root = sys_get_temp_dir() . '/capell-theme-readiness-' . bin2hex(random_bytes(8));
    $packagesRoot = $root . '/packages';
    $packageRoot = $packagesRoot . '/theme-fixture';

    $filesystem->ensureDirectoryExists($packageRoot . '/docs');
    $filesystem->ensureDirectoryExists($root . '/docs');

    writeCommercialReadinessJson($filesystem, $packageRoot . '/capell.json', [
        'manifest-version' => 3,
        'name' => 'capell-app/theme-foundation',
        'kind' => 'theme',
        'themeKey' => 'default',
        'extends' => null,
        'product' => ['tier' => 'free'],
        'providers' => ['runtime' => [FoundationThemeServiceProvider::class]],
        'dependencies' => ['supports' => []],
        'marketplace' => ['screenshots' => []],
    ]);

    writeCommercialReadinessJson($filesystem, $root . '/docs/themes.json', [
        'schemaVersion' => 1,
        'lastReviewed' => '2026-07-14',
        'themes' => [[
            'package' => 'capell-app/theme-foundation',
            'themeKey' => 'default',
            'displayName' => 'Fixture',
            'tier' => 'foundation',
            'family' => 'fixture',
            'lane' => 'Validator fixture',
            'audience' => ['Test buyers'],
            'overlapRisk' => 'low',
            'priorityPhase' => 1,
            'visualDifferentiators' => ['Deterministic fixture'],
            'notes' => 'Exercises commercial package proof.',
            'extends' => null,
            'customisationSurfaces' => [
                'header' => 'Foundation navigation chrome',
                'footer' => 'Foundation footer chrome',
                'themeStudioTokens' => ['primaryColor'],
            ],
        ]],
    ]);

    $screenshotEntries = array_map(
        static fn (string $surface): array => [
            'id' => 'fixture-' . $surface,
            'screenshotPath' => 'packages/theme-fixture/docs/screenshots/fixture-' . $surface . '.png',
            'required' => true,
        ],
        ['homepage', 'directory', 'detail', 'contact', 'empty', 'not-found', 'cta'],
    );

    writeCommercialReadinessJson($filesystem, $packageRoot . '/docs/screenshots.json', [
        'entries' => $screenshotEntries,
    ]);

    return [
        'root' => $root,
        'packagesRoot' => $packagesRoot,
        'packageRoot' => $packageRoot,
    ];
}

/**
 * @param  array<string, mixed>  $payload
 */
function writeCommercialReadinessJson(Filesystem $filesystem, string $path, array $payload): void
{
    $filesystem->put(
        $path,
        json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
}
