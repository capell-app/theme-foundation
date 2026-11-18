<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\FoundationTheme\Actions\MigrateSitesToThemeAction;
use Spatie\LaravelData\Data;

/**
 * Outcome of {@see MigrateSitesToThemeAction}: the sites that moved and the
 * rollback report that can move exactly those sites back.
 */
class ThemeMigrationResultData extends Data
{
    /**
     * @param  array<int, string>  $sites  site id => site name
     * @param  array<int, string>  $layouts  layout id => layout name
     * @param  list<int>  $widgets  ids of the widgets remapped to the successor's renderables
     */
    public function __construct(
        public readonly ThemeMigrationPlanData $plan,
        public readonly array $sites,
        public readonly array $layouts,
        public readonly array $widgets,
        public readonly ?string $reportPath,
    ) {}
}
