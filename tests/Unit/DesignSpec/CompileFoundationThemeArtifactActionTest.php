<?php

declare(strict_types=1);

use Capell\Core\Support\Manifest\ManifestValidator;
use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Actions\DesignSpec\ValidateCompiledThemeArtifactAction;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeFileData;

function compilerDesignSpecFixture(string $name): string
{
    $contents = file_get_contents(dirname(__DIR__, 2) . "/Fixtures/design-spec/{$name}.json");

    return $contents === false ? throw new RuntimeException('Unable to load compiler DesignSpec fixture.') : $contents;
}

/** @return array<string, CompiledThemeFileData> */
function compiledThemeFilesByPath(string $fixture = 'v1-canonical'): array
{
    $artifact = CompileFoundationThemeArtifactAction::run(compilerDesignSpecFixture($fixture));
    $files = [];
    foreach ($artifact->files as $file) {
        $files[$file->path] = $file;
    }

    return $files;
}

it('compiles byte-identical ordinary theme artifacts for identical canonical inputs', function (): void {
    $payload = json_decode(compilerDesignSpecFixture('v1-canonical'), true, 64, JSON_THROW_ON_ERROR);
    $first = CompileFoundationThemeArtifactAction::run($payload);
    $second = CompileFoundationThemeArtifactAction::run(array_reverse($payload, true));

    expect($first->artifactType)->toBe('capell-theme')
        ->and($first->compilerVersion)->toBe('foundation-compiler-v1')
        ->and($first->templateVersion)->toBe('foundation-package-v1')
        ->and($first->artifactBytes)->toBe($second->artifactBytes)
        ->and($first->artifactSha256)->toBe($second->artifactSha256)
        ->and($first->contentSha256)->toBe($second->contentSha256)
        ->and($first->artifactSha256)->toBe('15dd28d9ec51a5b546da904d2afdccd0fe2e423f42defa41cf08ba5e00285ec7');
});

it('emits only reviewed allowlisted package paths and server-owned asset selections', function (): void {
    $files = compiledThemeFilesByPath();
    $assets = json_decode($files['resources/asset-selections.json']->contents, true, 64, JSON_THROW_ON_ERROR);

    expect(array_keys($files))->toBe(array_keys(CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES))
        ->and(array_column($assets, 'id'))->toBe(['logo-foundation', 'font-serif-latin'])
        ->and(array_column($assets, 'kind'))->toBe(['image', 'font'])
        ->and($files['resources/design-spec.json']->contents)->not->toContain('/tmp')
        ->and($files['resources/css/design-tokens.css']->contents)
        ->not->toContain('url(')
        ->not->toContain('@import');

    foreach (array_keys($files) as $path) {
        expect($path)->not->toStartWith('/')
            ->not->toContain('..')
            ->not->toContain('\\');
    }
});

it('keeps reviewed executable source independent from model-selected values', function (): void {
    $canonical = compiledThemeFilesByPath('v1-canonical');
    $multisite = compiledThemeFilesByPath('v1-two-sites-two-locales');
    $providerPath = 'src/GeneratedFoundationThemeServiceProvider.php';

    expect($canonical[$providerPath]->contents)->toBe($multisite[$providerPath]->contents)
        ->and($canonical[$providerPath]->sha256)->toBe($multisite[$providerPath]->sha256)
        ->and($canonical[$providerPath]->contents)
        ->not->toContain('Capell Studio')
        ->not->toContain('Capell Europe')
        ->not->toContain('prompt')
        ->not->toContain('billing');
});

it('produces a manifest and composer contract accepted as an ordinary package', function (): void {
    $files = compiledThemeFilesByPath();
    $directory = sys_get_temp_dir() . '/capell-generated-theme-' . bin2hex(random_bytes(8));

    foreach ($files as $file) {
        $path = $directory . '/' . $file->path;
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $file->contents);
    }

    require_once $directory . '/src/GeneratedFoundationThemeServiceProvider.php';
    $manifest = json_decode($files['capell.json']->contents, true, 64, JSON_THROW_ON_ERROR);
    $composer = json_decode($files['composer.json']->contents, true, 64, JSON_THROW_ON_ERROR);

    (new ManifestValidator)->validate($manifest, $composer, 'capell-generated/foundation-theme', $directory . '/capell.json');

    expect($manifest['kind'])->toBe('theme')
        ->and($manifest['security']['publicOutput']['forbidAuthoringSurface'])->toBeTrue()
        ->and($manifest['dependencies']['requires'])->toBe(['capell-app/core', 'capell-app/frontend', 'capell-app/layout-builder', 'capell-app/theme-foundation']);
});

it('round trips the complete closed artifact through its validator', function (): void {
    $compiled = CompileFoundationThemeArtifactAction::run(compilerDesignSpecFixture('v1-canonical'));
    $validated = ValidateCompiledThemeArtifactAction::run($compiled->artifactBytes);

    expect($validated->artifactSha256)->toBe($compiled->artifactSha256)
        ->and($validated->contentSha256)->toBe($compiled->contentSha256)
        ->and($validated->designSpecSha256)->toBe($compiled->designSpecSha256)
        ->and($validated->files)->toHaveCount(6);
});
