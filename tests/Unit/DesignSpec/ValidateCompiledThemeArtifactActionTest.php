<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Actions\DesignSpec\ValidateCompiledThemeArtifactAction;

/**
 * @return array{
 *     artifactType: string,
 *     compilerVersion: string,
 *     contentSha256: string,
 *     designSpecSha256: string,
 *     files: list<array<string, mixed>>,
 *     schemaVersion: int,
 *     templateVersion: string
 * }
 */
function validCompiledThemeEnvelope(): array
{
    $fixture = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/design-spec/v1-canonical.json');
    if ($fixture === false) {
        throw new RuntimeException('Unable to load artifact validation fixture.');
    }

    $payload = foundationThemeJsonObject(json_decode(
        CompileFoundationThemeArtifactAction::run($fixture)->artifactBytes,
        true,
        64,
        JSON_THROW_ON_ERROR,
    ));
    $artifactType = $payload['artifactType'] ?? null;
    $compilerVersion = $payload['compilerVersion'] ?? null;
    $contentSha256 = $payload['contentSha256'] ?? null;
    $designSpecSha256 = $payload['designSpecSha256'] ?? null;
    $schemaVersion = $payload['schemaVersion'] ?? null;
    $templateVersion = $payload['templateVersion'] ?? null;
    if (! is_string($artifactType)
        || ! is_string($compilerVersion)
        || ! is_string($contentSha256)
        || ! is_string($designSpecSha256)
        || ! is_int($schemaVersion)
        || ! is_string($templateVersion)) {
        throw new RuntimeException('Compiled theme envelope is invalid.');
    }

    $files = [];
    foreach (foundationThemeJsonList($payload['files'] ?? null) as $file) {
        $files[] = foundationThemeJsonObject($file);
    }

    return [
        'artifactType' => $artifactType,
        'compilerVersion' => $compilerVersion,
        'contentSha256' => $contentSha256,
        'designSpecSha256' => $designSpecSha256,
        'files' => $files,
        'schemaVersion' => $schemaVersion,
        'templateVersion' => $templateVersion,
    ];
}

/** @param array<string, mixed> $payload */
function encodedCompiledThemeEnvelope(array $payload): string
{
    return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

it('rejects compatibility drift and unknown envelope fields', function (string $case): void {
    $payload = validCompiledThemeEnvelope();
    match ($case) {
        'artifact-type' => $payload['artifactType'] = 'source-export',
        'compiler' => $payload['compilerVersion'] = 'future',
        'template' => $payload['templateVersion'] = 'future',
        'schema' => $payload['schemaVersion'] = 2,
        'unknown' => $payload['model'] = 'private-model',
        default => throw new LogicException('Unknown artifact tampering case.'),
    };

    ValidateCompiledThemeArtifactAction::run(encodedCompiledThemeEnvelope($payload));
})->with(['artifact-type', 'compiler', 'template', 'schema', 'unknown'])->throws(InvalidArgumentException::class);

it('rejects alternate JSON encoding of an otherwise equivalent envelope', function (): void {
    $payload = validCompiledThemeEnvelope();

    ValidateCompiledThemeArtifactAction::run(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
})->throws(InvalidArgumentException::class, 'design_spec.artifact.non_canonical');

it('rejects path, media type, contents, and file digest tampering', function (string $case): void {
    $payload = validCompiledThemeEnvelope();
    match ($case) {
        'path' => $payload['files'][0]['path'] = '../capell.json',
        'media-type' => $payload['files'][0]['mediaType'] = 'application/x-httpd-php',
        'contents' => $payload['files'][0]['contentsBase64'] = base64_encode('{}'),
        'digest' => $payload['files'][0]['sha256'] = str_repeat('0', 64),
        default => throw new LogicException('Unknown artifact tampering case.'),
    };

    ValidateCompiledThemeArtifactAction::run(encodedCompiledThemeEnvelope($payload));
})->with(['path', 'media-type', 'contents', 'digest'])->throws(InvalidArgumentException::class);

it('rejects DesignSpec, provider template, asset catalogue, and content digest tampering', function (string $case): void {
    $payload = validCompiledThemeEnvelope();
    $path = match ($case) {
        'design-spec' => 'resources/design-spec.json',
        'provider' => 'src/GeneratedFoundationThemeServiceProvider.php',
        'composer' => 'composer.json',
        'manifest' => 'capell.json',
        'assets' => 'resources/asset-selections.json',
        'css' => 'resources/css/design-tokens.css',
        'valid-css-token' => 'resources/css/design-tokens.css',
        'content-digest' => null,
        default => throw new LogicException('Unknown artifact tampering case.'),
    };

    if ($path === null) {
        $payload['contentSha256'] = str_repeat('0', 64);
    } else {
        $index = array_search($path, array_column($payload['files'], 'path'), true);
        expect($index)->not->toBeFalse();
        $contentsBase64 = $payload['files'][$index]['contentsBase64'] ?? null;
        if (! is_string($contentsBase64)) {
            throw new RuntimeException('Compiled theme file contents are invalid.');
        }

        $contents = base64_decode($contentsBase64, true);
        if ($contents === false) {
            throw new RuntimeException('Compiled theme file contents are invalid.');
        }
        $contents = match ($case) {
            'design-spec' => str_replace('"schemaVersion":1', '"schemaVersion":2', $contents),
            'provider' => $contents . "\n// changed\n",
            'composer' => str_replace('"type": "library",', '"type": "library", "scripts": {"post-install-cmd": "unsafe"},', $contents),
            'manifest' => str_replace('"kind": "theme",', '"kind": "theme", "billing": {"credits": 100},', $contents),
            'assets' => "[]\n",
            'css' => $contents . "@import url(https://attacker.test/x.css);\n",
            'valid-css-token' => str_replace('#1D4ED8', '#1D4ED9', $contents),
            default => throw new LogicException('Unknown artifact tampering case.'),
        };
        $payload['files'][$index]['contentsBase64'] = base64_encode($contents);
        $payload['files'][$index]['sizeBytes'] = strlen($contents);
        $payload['files'][$index]['sha256'] = hash('sha256', $contents);

        if ($case === 'valid-css-token') {
            $fileManifest = array_map(
                static fn (array $file): array => [
                    'path' => $file['path'],
                    'sha256' => $file['sha256'],
                    'sizeBytes' => $file['sizeBytes'],
                ],
                $payload['files'],
            );
            $payload['contentSha256'] = hash('sha256', json_encode(
                $fileManifest,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        }
    }

    ValidateCompiledThemeArtifactAction::run(encodedCompiledThemeEnvelope($payload));
})->with(['design-spec', 'provider', 'composer', 'manifest', 'assets', 'css', 'valid-css-token', 'content-digest'])->throws(InvalidArgumentException::class);
