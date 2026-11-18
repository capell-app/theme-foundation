<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\FoundationTheme\Actions\PlanThemeMigrationAction;
use Spatie\LaravelData\Data;

/**
 * What {@see PlanThemeMigrationAction} found: which sites would move, what
 * happens to the successor theme record, and anything that blocks the move or
 * will not be carried across.
 */
class ThemeMigrationPlanData extends Data
{
    /**
     * @param  array<int, string>  $sites  site id => site name, soft-deleted sites included
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets  widget id => renderable remap
     * @param  array<int, string>  $layouts  layout id => layout name, for layouts that name the retired theme themselves
     * @param  list<string>  $tokenConflicts  saved token keys whose value differs on the successor
     * @param  list<string>  $notCarried  customisations on the retired theme that stay behind
     * @param  list<string>  $blockers  reasons the move cannot be applied as requested
     */
    public function __construct(
        public readonly string $fromThemeKey,
        public readonly string $toThemeKey,
        public readonly string $presetKey,
        public readonly ?int $fromThemeId,
        public readonly ?int $toThemeId,
        public readonly array $sites,
        public readonly array $layouts,
        public readonly array $widgets,
        public readonly bool $switchesActiveTheme,
        public readonly int $successorSiteCount,
        public readonly int $successorLayoutCount,
        public readonly bool $successorIsShared,
        public readonly bool $successorDisabled,
        public readonly bool $writesSuccessorPreset,
        public readonly bool $carriesTokens,
        public readonly array $tokenConflicts,
        public readonly array $notCarried,
        public readonly array $blockers,
    ) {}

    public function hasWork(): bool
    {
        return $this->sites !== [] || $this->layouts !== [] || $this->widgets !== [] || $this->switchesActiveTheme;
    }

    public function canApply(): bool
    {
        return $this->blockers === [];
    }
}
