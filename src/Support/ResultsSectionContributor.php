<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Capell\Core\ThemeStudio\Data\ContentListingSectionData;
use Capell\FoundationTheme\Actions\ResolveThemeResultsListingAction;
use Capell\Frontend\Contracts\ThemeSectionPayloadContributor;
use Capell\Frontend\Facades\Frontend;

final class ResultsSectionContributor implements ThemeSectionPayloadContributor
{
    public function contribute(ThemeSection $section): ThemeSection
    {
        if (! $section instanceof ContentListingSectionData || $section->items !== []) {
            return $section;
        }

        $layout = Frontend::layout();
        $site = Frontend::site();
        $language = Frontend::language();

        if ($layout?->key !== LayoutEnum::Results->value || ! $site instanceof Site || ! $language instanceof Language) {
            return $section;
        }

        $results = ResolveThemeResultsListingAction::run(
            site: $site,
            language: $language,
            heading: $section->heading,
            summary: $section->summary,
        );

        return new ContentListingSectionData(
            heading: $results->heading,
            summary: $results->summary,
            items: $results->items,
            variant: $section->variant,
        );
    }
}
