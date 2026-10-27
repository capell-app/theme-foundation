<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Render;

use Capell\FoundationTheme\Actions\ResolveFoundationImageLoadingAction;
use Capell\FoundationTheme\Data\FoundationImageLoadingData;
use Capell\Frontend\Contracts\PublicRenderDataContributor;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\PublicRenderDataContributionData;
use Capell\Frontend\Data\PublicRenderDataContributionMetadataData;
use Illuminate\Database\Eloquent\Model;
use Override;

final class FoundationImageLoadingContributor implements PublicRenderDataContributor
{
    public const string KEY = 'foundation_image_loading';

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function supports(FrontendRenderContextData $context): bool
    {
        return $context->layout !== null;
    }

    #[Override]
    public function metadata(FrontendRenderContextData $context): PublicRenderDataContributionMetadataData
    {
        return new PublicRenderDataContributionMetadataData(ResolveFoundationImageLoadingAction::run());
    }

    #[Override]
    public function contribute(FrontendRenderContextData $context): PublicRenderDataContributionData
    {
        return new PublicRenderDataContributionData(new FoundationImageLoadingData(ResolveFoundationImageLoadingAction::run()));
    }

    /** @return list<class-string<Model>> */
    #[Override]
    public function cacheDependencyModelTypes(): array
    {
        return [];
    }
}
