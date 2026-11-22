<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\Core\Contracts\Rendering\FrontendResourceContributor;
use Capell\Core\Data\Rendering\Assets\FrontendResourceContributionData;
use Capell\Core\Data\Rendering\Assets\FrontendResourceData;
use Capell\Core\Data\Rendering\FrontendResourceContextData;
use Capell\Core\Enums\Rendering\FrontendResourcePlacement;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;

final readonly class ThemeFrontendScriptContributor implements FrontendResourceContributor
{
    public function __construct(
        private ThemeFrontendScriptRegistry $scripts,
    ) {}

    public function resources(FrontendResourceContextData $context): array
    {
        $themeKey = $context->theme?->key;

        if (! is_string($themeKey) || $themeKey === '') {
            return [];
        }

        $script = $this->scripts->forTheme($themeKey);

        if ($script === null) {
            return [];
        }

        return [
            new FrontendResourceContributionData(
                FrontendResourceData::moduleScript(
                    handle: $script->handle,
                    package: $script->packageName,
                    source: new ViteResourceSourceData($script->entry, $script->publicDirectory),
                    placement: FrontendResourcePlacement::BodyEnd,
                ),
            ),
        ];
    }
}
