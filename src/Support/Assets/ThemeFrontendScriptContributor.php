<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\Frontend\Contracts\FrontendResourceContributor;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Capell\Frontend\Enums\FrontendResourcePlacement;

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
