<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\CanonicalDesignSpecData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeArtifactData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeFileData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecAssetData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/** @method static CompiledThemeArtifactData run(array<string, mixed>|string|DesignSpecData $specification) */
final class CompileFoundationThemeArtifactAction
{
    use AsFake;
    use AsObject;

    public const string ARTIFACT_TYPE = 'capell-theme';

    public const string COMPILER_VERSION = 'foundation-compiler-v1';

    public const string TEMPLATE_VERSION = 'foundation-package-v1';

    public const string ARTIFACT_MEDIA_TYPE = 'application/vnd.capell.theme+json';

    /** @var array<string, string> */
    public const array FILE_MEDIA_TYPES = [
        'capell.json' => 'application/json',
        'composer.json' => 'application/json',
        'resources/asset-selections.json' => 'application/json',
        'resources/css/design-tokens.css' => 'text/css',
        'resources/design-spec.json' => 'application/json',
        'src/GeneratedFoundationThemeServiceProvider.php' => 'application/x-httpd-php',
    ];

    /** @var array<string, string> */
    public const array FIXED_FILE_SHA256 = [
        'capell.json' => '7c78b1b6fd082e1a62f56e54d0ac4fad72f603af4e417d739e4ca2200dcb2501',
        'composer.json' => 'd807c7baf0cf36ea11dd31aa88f50f83715bfa1f0343fd0e596d884121428eac',
        'src/GeneratedFoundationThemeServiceProvider.php' => '57551d9f755d36745cb63c8c9fc9c5e1cdba185b71bab68f4bb0e8b9050fc56b',
    ];

    /** @param array<string, mixed>|string|DesignSpecData $specification */
    public function handle(array|string|DesignSpecData $specification): CompiledThemeArtifactData
    {
        $canonical = CanonicalizeDesignSpecAction::run($specification);
        $files = $this->files($canonical);
        $contentSha256 = hash('sha256', $this->canonicalJson(array_map(
            static fn (CompiledThemeFileData $file): array => [
                'path' => $file->path,
                'sha256' => $file->sha256,
                'sizeBytes' => $file->sizeBytes,
            ],
            $files,
        )));
        $envelope = [
            'artifactType' => self::ARTIFACT_TYPE,
            'compilerVersion' => self::COMPILER_VERSION,
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
            'templateVersion' => self::TEMPLATE_VERSION,
        ];
        $artifactBytes = $this->canonicalJson($envelope);
        $artifact = new CompiledThemeArtifactData(
            artifactType: self::ARTIFACT_TYPE,
            schemaVersion: DesignSpecConstraints::SCHEMA_VERSION,
            compilerVersion: self::COMPILER_VERSION,
            templateVersion: self::TEMPLATE_VERSION,
            designSpecSha256: $canonical->sha256,
            contentSha256: $contentSha256,
            artifactBytes: $artifactBytes,
            artifactSha256: hash('sha256', $artifactBytes),
            files: $files,
        );

        ValidateCompiledThemeArtifactAction::run($artifactBytes);

        return $artifact;
    }

    /** @return list<CompiledThemeFileData> */
    private function files(CanonicalDesignSpecData $canonical): array
    {
        $specification = $canonical->specification;
        $contents = [
            'capell.json' => $this->prettyJson($this->manifest()),
            'composer.json' => $this->prettyJson($this->composer()),
            'resources/asset-selections.json' => $this->prettyJson($this->assets($specification)),
            'resources/css/design-tokens.css' => RenderFoundationDesignTokensAction::run($specification),
            'resources/design-spec.json' => $canonical->bytes . "\n",
            'src/GeneratedFoundationThemeServiceProvider.php' => $this->providerTemplate(),
        ];
        ksort($contents, SORT_STRING);

        return array_map(
            static fn (string $path, string $fileContents): CompiledThemeFileData => new CompiledThemeFileData(
                path: $path,
                mediaType: self::FILE_MEDIA_TYPES[$path],
                sizeBytes: strlen($fileContents),
                sha256: hash('sha256', $fileContents),
                contents: $fileContents,
            ),
            array_keys($contents),
            array_values($contents),
        );
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        return [
            'manifest-version' => 3,
            'name' => 'capell-generated/foundation-theme',
            'slug' => 'generated-foundation-theme',
            'displayName' => 'Generated Foundation Theme',
            'kind' => 'theme',
            'capellApiVersion' => '^1.0',
            'version' => '1.0.0',
            'description' => 'A deterministic Foundation theme compiled from a validated DesignSpec.',
            'product' => ['group' => 'Capell Themes', 'tier' => 'free', 'bundle' => 'themes'],
            'namespace' => 'Capell\\GeneratedFoundationTheme',
            'surfaces' => ['frontend'],
            'dependencies' => ['requires' => ['capell-app/core', 'capell-app/frontend', 'capell-app/layout-builder', 'capell-app/theme-foundation'], 'supports' => [], 'conflicts' => []],
            'providers' => ['metadata' => [], 'install' => [], 'runtime' => ['Capell\\GeneratedFoundationTheme\\GeneratedFoundationThemeServiceProvider'], 'admin' => [], 'frontend' => []],
            'contributes' => [],
            'database' => ['migrations' => false, 'settings' => false, 'requiredTables' => []],
            'commands' => ['install' => null, 'setup' => null, 'demo' => null, 'doctor' => null],
            'settings' => [],
            'permissions' => [],
            'capabilities' => ['generated-foundation-theme'],
            'security' => [
                'riskTier' => 'low',
                'publicSurface' => ['routeNames' => [], 'auth' => 'none', 'csrfExemptRoutes' => [], 'signedRoutes' => [], 'tokenizedRoutes' => [], 'webhookRoutes' => [], 'throttledRoutes' => []],
                'sensitiveData' => ['encryptedFields' => [], 'hashedTokenFields' => [], 'redactedOutputClasses' => [], 'plaintextJustifications' => []],
                'publicOutput' => ['cacheSafe' => true, 'forbidAuthoringSurface' => true, 'forbidSecrets' => true, 'forbidPublicBladeQueries' => true],
                'externalHttpClients' => ['requiresTimeouts' => false, 'requiresSecretRedaction' => false, 'clients' => []],
                'adminSurface' => ['authorization' => 'none', 'permissions' => []],
            ],
            'performance' => ['criticalCss' => ['required' => true, 'parityViewports' => ['mobile', 'tablet', 'desktop']], 'frontendRenderBudgetMs' => 20, 'adminQueryBudget' => 0, 'cacheTags' => ['generated-foundation-theme'], 'cacheSafety' => ['cacheable' => false, 'variesBy' => ['site', 'locale'], 'sensitiveOutput' => false, 'invalidationSources' => [], 'queueInvalidation' => false]],
            'healthChecks' => [],
            'commercial' => ['proposedLicense' => 'free', 'requestedCertification' => 'first-party', 'supportPolicy' => 'capell-first-party', 'privateDocsRequested' => false],
            'marketplace' => ['summary' => 'A generated Foundation theme.', 'screenshots' => [], 'categories' => ['frontend', 'themes'], 'hidden' => true],
            'extends' => 'default',
            'themeKey' => 'generated-foundation',
        ];
    }

    /** @return array<string, mixed> */
    private function composer(): array
    {
        return [
            'name' => 'capell-generated/foundation-theme',
            'description' => 'A deterministic Foundation theme compiled from a validated DesignSpec.',
            'type' => 'library',
            'license' => 'proprietary',
            'require' => ['php' => '^8.4', 'capell-app/core' => '^1.0', 'capell-app/frontend' => '^1.0', 'capell-app/layout-builder' => '^1.0', 'capell-app/theme-foundation' => '^1.0'],
            'autoload' => ['psr-4' => ['Capell\\GeneratedFoundationTheme\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => ['Capell\\GeneratedFoundationTheme\\GeneratedFoundationThemeServiceProvider']]],
        ];
    }

    /** @return list<array{id: string, kind: string, bytes: int}> */
    private function assets(DesignSpecData $specification): array
    {
        return array_map(
            static fn (DesignSpecAssetData $asset): array => ['id' => $asset->id, ...DesignSpecConstraints::ASSET_CATALOGUE[$asset->id]],
            $specification->assets,
        );
    }

    private function providerTemplate(): string
    {
        $path = dirname(__DIR__, 3) . '/resources/compiler/v1/GeneratedFoundationThemeServiceProvider.php.stub';
        $contents = file_get_contents($path);

        return $contents === false ? throw new RuntimeException('design_spec.compiler.template_unavailable') : $contents;
    }

    /** @param array<mixed> $value */
    private function canonicalJson(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw new RuntimeException('design_spec.compiler.encoding_failed');
        }
    }

    /** @param array<mixed> $value */
    private function prettyJson(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } catch (JsonException) {
            throw new RuntimeException('design_spec.compiler.encoding_failed');
        }
    }
}
