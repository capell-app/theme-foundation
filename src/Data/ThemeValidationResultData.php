<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\FoundationTheme\Actions\ValidateThemeCatalogueEntryAction;
use Spatie\LaravelData\Data;

/**
 * Outcome of {@see ValidateThemeCatalogueEntryAction}
 * for a single theme — the three-way agreement check between a theme
 * package's `capell.json` manifest, its `docs/themes.json` catalogue entry,
 * its registered {@see ThemeDefinitionData},
 * and its `docs/screenshots.json` manifest completeness.
 */
class ThemeValidationResultData extends Data
{
    /**
     * @param  list<string>  $violations  Human-readable failure descriptions;
     *                                    empty when the theme passes every check.
     */
    public function __construct(
        public readonly string $themeKey,
        public readonly array $violations,
    ) {}

    public function passes(): bool
    {
        return $this->violations === [];
    }
}
