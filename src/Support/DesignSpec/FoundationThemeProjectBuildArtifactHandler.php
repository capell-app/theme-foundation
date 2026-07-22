<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\DesignSpec;

use Capell\Core\Contracts\ProjectBuild\ProjectBuildArtifactHandler;
use Capell\Core\Data\ProjectBuild\ProjectBuildArtifactReferenceData;
use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Actions\DesignSpec\ValidateCompiledThemeArtifactAction;
use InvalidArgumentException;

final readonly class FoundationThemeProjectBuildArtifactHandler implements ProjectBuildArtifactHandler
{
    public function type(): string
    {
        return CompileFoundationThemeArtifactAction::ARTIFACT_TYPE;
    }

    public function validate(ProjectBuildArtifactReferenceData $artifact, string $bytes): void
    {
        if ($artifact->type !== $this->type()
            || $artifact->mediaType !== CompileFoundationThemeArtifactAction::ARTIFACT_MEDIA_TYPE) {
            throw new InvalidArgumentException('design_spec.artifact.reference_invalid');
        }

        ValidateCompiledThemeArtifactAction::run($bytes);
    }
}
