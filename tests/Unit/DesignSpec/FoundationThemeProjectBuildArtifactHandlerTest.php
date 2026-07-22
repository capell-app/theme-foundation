<?php

declare(strict_types=1);

use Capell\Core\Data\ProjectBuild\ProjectBuildArtifactReferenceData;
use Capell\Core\Support\ProjectBuild\ProjectBuildArtifactHandlerRegistry;
use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Support\DesignSpec\FoundationThemeProjectBuildArtifactHandler;

it('registers and validates the released capell-theme artifact handler', function (): void {
    $fixture = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/design-spec/v1-canonical.json');
    $compiled = CompileFoundationThemeArtifactAction::run($fixture === false ? throw new RuntimeException('Missing handler fixture.') : $fixture);
    $reference = new ProjectBuildArtifactReferenceData(
        key: 'generated-theme',
        type: 'capell-theme',
        path: 'artifacts/generated-theme.json',
        digest: $compiled->artifactSha256,
        sizeBytes: strlen($compiled->artifactBytes),
        mediaType: CompileFoundationThemeArtifactAction::ARTIFACT_MEDIA_TYPE,
    );

    $handler = new FoundationThemeProjectBuildArtifactHandler;
    $handler->validate($reference, $compiled->artifactBytes);

    expect($handler->type())->toBe('capell-theme')
        ->and(resolve(ProjectBuildArtifactHandlerRegistry::class)->types())->toContain('capell-theme');
});

it('rejects a mismatched artifact media type', function (): void {
    $fixture = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/design-spec/v1-canonical.json');
    $compiled = CompileFoundationThemeArtifactAction::run($fixture === false ? throw new RuntimeException('Missing handler fixture.') : $fixture);
    $reference = new ProjectBuildArtifactReferenceData(
        key: 'generated-theme',
        type: 'capell-theme',
        path: 'artifacts/generated-theme.json',
        digest: $compiled->artifactSha256,
        sizeBytes: strlen($compiled->artifactBytes),
        mediaType: 'application/json',
    );

    (new FoundationThemeProjectBuildArtifactHandler)->validate($reference, $compiled->artifactBytes);
})->throws(InvalidArgumentException::class, 'design_spec.artifact.reference_invalid');
