<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeArtifactData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeFileData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecAssetData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;
use InvalidArgumentException;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static CompiledThemeArtifactData run(string $artifactBytes) */
final class ValidateCompiledThemeArtifactAction
{
    use AsFake;
    use AsObject;

    private const int MAX_ARTIFACT_BYTES = 1_048_576;

    /** @var list<string> */
    private const array ROOT_KEYS = ['artifactType', 'compilerVersion', 'contentSha256', 'designSpecSha256', 'files', 'schemaVersion', 'templateVersion'];

    /** @var list<string> */
    private const array FILE_KEYS = ['contentsBase64', 'mediaType', 'path', 'sha256', 'sizeBytes'];

    public function handle(string $artifactBytes): CompiledThemeArtifactData
    {
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw new InvalidArgumentException('design_spec.artifact.too_large');
        }

        $payload = $this->object($artifactBytes, 'design_spec.artifact.invalid_json');
        $this->exactKeys($payload, self::ROOT_KEYS, 'design_spec.artifact.root_invalid');

        if (($payload['artifactType'] ?? null) !== CompileFoundationThemeArtifactAction::ARTIFACT_TYPE
            || ($payload['compilerVersion'] ?? null) !== CompileFoundationThemeArtifactAction::COMPILER_VERSION
            || ($payload['templateVersion'] ?? null) !== CompileFoundationThemeArtifactAction::TEMPLATE_VERSION
            || ($payload['schemaVersion'] ?? null) !== DesignSpecConstraints::SCHEMA_VERSION) {
            throw new InvalidArgumentException('design_spec.artifact.compatibility_invalid');
        }

        $files = $this->files($payload['files'] ?? null);
        $filesByPath = [];
        foreach ($files as $file) {
            $filesByPath[$file->path] = $file;
        }

        $designSpecBytes = rtrim($filesByPath['resources/design-spec.json']->contents, "\n");
        $canonical = CanonicalizeDesignSpecAction::run($designSpecBytes);
        if ($filesByPath['resources/design-spec.json']->contents !== $canonical->bytes . "\n"
            || ! is_string($payload['designSpecSha256'] ?? null)
            || ! hash_equals($canonical->sha256, $payload['designSpecSha256'])) {
            throw new InvalidArgumentException('design_spec.artifact.design_spec_digest_invalid');
        }

        $this->validatePackageFiles($filesByPath, $canonical->specification);
        $contentSha256 = hash('sha256', $this->json(array_map(
            static fn (CompiledThemeFileData $file): array => ['path' => $file->path, 'sha256' => $file->sha256, 'sizeBytes' => $file->sizeBytes],
            $files,
        )));
        if (! is_string($payload['contentSha256'] ?? null)
            || ! hash_equals($contentSha256, $payload['contentSha256'])) {
            throw new InvalidArgumentException('design_spec.artifact.content_digest_invalid');
        }

        $expectedArtifactBytes = $this->json([
            'artifactType' => CompileFoundationThemeArtifactAction::ARTIFACT_TYPE,
            'compilerVersion' => CompileFoundationThemeArtifactAction::COMPILER_VERSION,
            'contentSha256' => $contentSha256,
            'designSpecSha256' => $canonical->sha256,
            'files' => array_map(
                static fn (CompiledThemeFileData $file): array => [
                    'contentsBase64' => base64_encode($file->contents),
                    'mediaType' => $file->mediaType,
                    'path' => $file->path,
                    'sha256' => $file->sha256,
                    'sizeBytes' => $file->sizeBytes,
                ],
                $files,
            ),
            'schemaVersion' => DesignSpecConstraints::SCHEMA_VERSION,
            'templateVersion' => CompileFoundationThemeArtifactAction::TEMPLATE_VERSION,
        ]);
        if (! hash_equals($expectedArtifactBytes, $artifactBytes)) {
            throw new InvalidArgumentException('design_spec.artifact.non_canonical');
        }

        return new CompiledThemeArtifactData(
            artifactType: CompileFoundationThemeArtifactAction::ARTIFACT_TYPE,
            schemaVersion: DesignSpecConstraints::SCHEMA_VERSION,
            compilerVersion: CompileFoundationThemeArtifactAction::COMPILER_VERSION,
            templateVersion: CompileFoundationThemeArtifactAction::TEMPLATE_VERSION,
            designSpecSha256: $canonical->sha256,
            contentSha256: $contentSha256,
            artifactBytes: $artifactBytes,
            artifactSha256: hash('sha256', $artifactBytes),
            files: $files,
        );
    }

    /** @return list<CompiledThemeFileData> */
    private function files(mixed $payload): array
    {
        if (! is_array($payload) || ! array_is_list($payload) || count($payload) !== count(CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES)) {
            throw new InvalidArgumentException('design_spec.artifact.files_invalid');
        }

        $files = [];
        foreach ($payload as $entry) {
            if (! is_array($entry) || array_is_list($entry)) {
                throw new InvalidArgumentException('design_spec.artifact.file_invalid');
            }

            $file = $this->objectValue($entry, 'design_spec.artifact.file_invalid');
            $this->exactKeys($file, self::FILE_KEYS, 'design_spec.artifact.file_invalid');
            $path = $file['path'] ?? null;
            if (! is_string($path)
                || ! array_key_exists($path, CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES)
                || array_key_exists($path, $files)) {
                throw new InvalidArgumentException('design_spec.artifact.path_invalid');
            }

            $contents = is_string($file['contentsBase64'] ?? null) ? base64_decode($file['contentsBase64'], true) : false;
            if ($contents === false
                || ($file['mediaType'] ?? null) !== CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES[$path]
                || ($file['sizeBytes'] ?? null) !== strlen($contents)
                || ! is_string($file['sha256'] ?? null)
                || ! hash_equals($file['sha256'], hash('sha256', $contents))) {
                throw new InvalidArgumentException('design_spec.artifact.file_integrity_invalid');
            }

            $files[$path] = new CompiledThemeFileData($path, $file['mediaType'], strlen($contents), $file['sha256'], $contents);
        }

        $expectedPaths = array_keys(CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES);
        sort($expectedPaths, SORT_STRING);
        if (array_keys($files) !== $expectedPaths) {
            throw new InvalidArgumentException('design_spec.artifact.file_order_invalid');
        }

        return array_values($files);
    }

    /**
     * @param  array<string, CompiledThemeFileData>  $files
     */
    private function validatePackageFiles(array $files, DesignSpecData $specification): void
    {
        foreach (CompileFoundationThemeArtifactAction::FIXED_FILE_SHA256 as $path => $sha256) {
            if (! hash_equals($sha256, $files[$path]->sha256)) {
                throw new InvalidArgumentException('design_spec.artifact.fixed_file_invalid');
            }
        }

        $manifest = $this->object($files['capell.json']->contents, 'design_spec.artifact.manifest_invalid');
        $composer = $this->object($files['composer.json']->contents, 'design_spec.artifact.composer_invalid');
        $dependencies = $this->objectValue($manifest['dependencies'] ?? null, 'design_spec.artifact.package_contract_invalid');
        $providers = $this->objectValue($manifest['providers'] ?? null, 'design_spec.artifact.package_contract_invalid');
        $requirements = $this->objectValue($composer['require'] ?? null, 'design_spec.artifact.package_contract_invalid');
        $autoload = $this->objectValue($composer['autoload'] ?? null, 'design_spec.artifact.package_contract_invalid');
        $expectedDependencies = ['capell-app/core', 'capell-app/frontend', 'capell-app/layout-builder', 'capell-app/theme-foundation'];
        if (($manifest['manifest-version'] ?? null) !== 3
            || ($manifest['name'] ?? null) !== 'capell-generated/foundation-theme'
            || ($manifest['kind'] ?? null) !== 'theme'
            || ($manifest['themeKey'] ?? null) !== 'generated-foundation'
            || ($manifest['extends'] ?? null) !== 'default'
            || ($dependencies['requires'] ?? null) !== $expectedDependencies
            || ($providers['runtime'] ?? null) !== ['Capell\\GeneratedFoundationTheme\\GeneratedFoundationThemeServiceProvider']
            || array_keys($requirements) !== ['php', 'capell-app/core', 'capell-app/frontend', 'capell-app/layout-builder', 'capell-app/theme-foundation']
            || ($this->objectValue($autoload['psr-4'] ?? null, 'design_spec.artifact.package_contract_invalid')) !== ['Capell\\GeneratedFoundationTheme\\' => 'src/']) {
            throw new InvalidArgumentException('design_spec.artifact.package_contract_invalid');
        }

        $providerPath = dirname(__DIR__, 3) . '/resources/compiler/v1/GeneratedFoundationThemeServiceProvider.php.stub';
        $provider = file_get_contents($providerPath);
        if ($provider === false || ! hash_equals(hash('sha256', $provider), $files['src/GeneratedFoundationThemeServiceProvider.php']->sha256)) {
            throw new InvalidArgumentException('design_spec.artifact.provider_template_invalid');
        }

        $css = strtolower($files['resources/css/design-tokens.css']->contents);
        if ($files['resources/css/design-tokens.css']->contents !== RenderFoundationDesignTokensAction::run($specification)) {
            throw new InvalidArgumentException('design_spec.artifact.css_invalid');
        }

        foreach (['url(', '@import', 'expression(', 'javascript:', 'prompt', 'billing', 'preview', 'editor'] as $forbidden) {
            if (str_contains($css, $forbidden)) {
                throw new InvalidArgumentException('design_spec.artifact.css_invalid');
            }
        }

        $assetSelections = $this->list($files['resources/asset-selections.json']->contents, 'design_spec.artifact.assets_invalid');
        $expectedAssets = array_map(
            static fn (DesignSpecAssetData $asset): array => ['id' => $asset->id, ...DesignSpecConstraints::ASSET_CATALOGUE[$asset->id]],
            $specification->assets,
        );
        if ($assetSelections !== $expectedAssets) {
            throw new InvalidArgumentException('design_spec.artifact.assets_invalid');
        }
    }

    /** @return array<string, mixed> */
    private function object(string $bytes, string $error): array
    {
        try {
            $value = json_decode($bytes, true, DesignSpecConstraints::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException($error);
        }

        return $this->objectValue($value, $error);
    }

    /**
     * @return array<string, mixed>
     */
    private function objectValue(mixed $value, string $error): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new InvalidArgumentException($error);
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw new InvalidArgumentException($error);
            }

            $object[$key] = $item;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(string $bytes, string $error): array
    {
        try {
            $value = json_decode($bytes, true, DesignSpecConstraints::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException($error);
        }

        return is_array($value) && array_is_list($value) ? $value : throw new InvalidArgumentException($error);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function exactKeys(array $payload, array $keys, string $error): void
    {
        $actual = array_keys($payload);
        sort($actual, SORT_STRING);
        $expected = $keys;
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw new InvalidArgumentException($error);
        }
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw new InvalidArgumentException('design_spec.artifact.invalid');
        }
    }
}
