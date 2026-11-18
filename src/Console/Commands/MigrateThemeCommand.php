<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Actions\MigrateSitesToThemeAction;
use Capell\FoundationTheme\Actions\PlanThemeMigrationAction;
use Capell\FoundationTheme\Actions\RollbackThemeMigrationAction;
use Capell\FoundationTheme\Data\ThemeMigrationPlanData;
use Capell\FoundationTheme\Support\Themes\RetiredThemeReferenceScanner;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSectionMap;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSuccessors;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Previews by default. Only --force writes, and every write leaves a rollback
 * report that --rollback uses to move exactly those sites back.
 */
class MigrateThemeCommand extends Command
{
    protected $signature = 'capell:theme-migrate
        {from? : Key of the retired theme the sites use now}
        {to? : Key of its successor theme}
        {--preset= : Successor preset to use; required when the successor record is already in use}
        {--allow-shared-successor : Confirm the moved sites take on an in-use successor record\'s preset and tokens}
        {--rollback= : Path of a rollback report to reverse}
        {--force : Apply the change; without it the command only previews}';

    protected $description = 'Preview or move every site from a retired theme to its successor, with a rollback report.';

    public function handle(): int
    {
        $rollback = $this->option('rollback');

        if (is_string($rollback) && $rollback !== '') {
            return $this->rollback($rollback);
        }

        $from = $this->argument('from');
        $to = $this->argument('to');

        if (! is_string($from) || ! is_string($to)) {
            $this->error((string) __('capell-theme-foundation::theme-migration.missing_arguments'));

            return self::INVALID;
        }

        $preset = $this->option('preset');
        $preset = is_string($preset) && $preset !== '' ? $preset : null;
        $allowShared = (bool) $this->option('allow-shared-successor');

        $plan = PlanThemeMigrationAction::run($from, $to, $preset, $allowShared);
        $this->describe($plan);

        if (! $plan->hasWork() && resolve(RetiredThemeSuccessors::class)->successorFor($from) === $to) {
            // A rerun after the move is how an operator checks the package
            // can go, so it gives the verdict. The shared-successor checks
            // guard a move and do not apply when nothing moves; anything else
            // that blocks still fails the run.
            $blockers = PlanThemeMigrationAction::run($from, $to, $plan->presetKey !== '' ? $plan->presetKey : null, true)->blockers;

            foreach ($blockers as $blocker) {
                $this->error($blocker);
            }

            return $this->reportRemovalVerdict($from) && $blockers === [] ? self::SUCCESS : self::FAILURE;
        }

        if (! $plan->canApply()) {
            $this->error((string) __('capell-theme-foundation::theme-migration.plan.blocked'));

            foreach ($plan->blockers as $blocker) {
                $this->line('  ' . $blocker);
            }

            return self::FAILURE;
        }

        if (! $plan->hasWork()) {
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->info((string) __('capell-theme-foundation::theme-migration.plan.dry_run'));

            return self::SUCCESS;
        }

        try {
            $result = MigrateSitesToThemeAction::run($from, $to, $preset, $allowShared);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info((string) __('capell-theme-foundation::theme-migration.done', [
            'count' => count($result->sites),
            'layouts' => count($result->layouts),
            'widgets' => count($result->widgets),
            'from' => $from,
            'to' => $to,
            'path' => (string) $result->reportPath,
        ]));

        // The move succeeded, but a reference it could not reach still needs
        // attention before the package goes, so the run reports failure.
        return $this->reportRemovalVerdict($from) ? self::SUCCESS : self::FAILURE;
    }

    private function describe(ThemeMigrationPlanData $plan): void
    {
        if (! $plan->hasWork()) {
            $this->info((string) __('capell-theme-foundation::theme-migration.plan.no_sites', ['from' => $plan->fromThemeKey]));

            return;
        }

        $this->info((string) __('capell-theme-foundation::theme-migration.plan.heading', ['from' => $plan->fromThemeKey, 'to' => $plan->toThemeKey, 'preset' => $plan->presetKey]));
        $this->listRecords($plan->sites, $plan->layouts, array_map(static fn (array $remap): string => sprintf('%s (%s -> %s)', $remap['name'], $remap['component_from'] ?? $remap['meta_component_from'] ?? '', $remap['component_to'] ?? $remap['meta_component_to'] ?? ''), $plan->widgets));

        foreach ($plan->widgets as $remap) {
            if ($remap['section_type_to'] !== null) {
                $this->line((string) __('capell-theme-foundation::theme-migration.plan.section_mapped', [
                    'widget' => $remap['name'],
                    'from' => $remap['section_type_from'],
                    'to' => $remap['section_type_to'],
                ]));

                $labels = array_map(static fn (string $key): string => '"' . __($key) . '"', resolve(RetiredThemeSectionMap::class)->successorDefaultLabels($plan->toThemeKey, $remap['section_type_to']));

                if ($labels !== []) {
                    $this->line((string) __('capell-theme-foundation::theme-migration.plan.default_labels', [
                        'widget' => $remap['name'],
                        'to' => $remap['section_type_to'],
                        'labels' => implode(', ', $labels),
                    ]));
                }
            }

            if ($remap['hidden_fields'] !== []) {
                $this->warn((string) __('capell-theme-foundation::theme-migration.plan.fields_not_shown', [
                    'widget' => $remap['name'],
                    'to' => $remap['section_type_to'],
                    'fields' => implode(', ', $remap['hidden_fields']),
                ]));
            }
        }

        if ($plan->switchesActiveTheme) {
            $this->line((string) __('capell-theme-foundation::theme-migration.plan.active_theme', ['from' => $plan->fromThemeKey, 'to' => $plan->toThemeKey, 'preset' => $plan->presetKey]));
        }

        $this->line((string) match (true) {
            $plan->toThemeId === null => __('capell-theme-foundation::theme-migration.plan.successor_new', ['to' => $plan->toThemeKey]),
            $plan->successorIsShared => __('capell-theme-foundation::theme-migration.plan.successor_shared', ['to' => $plan->toThemeKey, 'sites' => $plan->successorSiteCount]),
            default => __('capell-theme-foundation::theme-migration.plan.successor_private', ['to' => $plan->toThemeKey, 'from' => $plan->fromThemeKey, 'preset' => $plan->presetKey]),
        });

        if ($plan->successorDisabled) {
            $this->warn((string) __('capell-theme-foundation::theme-migration.plan.successor_disabled', ['to' => $plan->toThemeKey, 'sites' => $plan->successorSiteCount]));
        }

        foreach ($plan->tokenConflicts as $token) {
            $this->warn((string) __('capell-theme-foundation::theme-migration.plan.token_conflict', ['token' => $token, 'to' => $plan->toThemeKey]));
        }

        foreach ($plan->notCarried as $item) {
            $this->warn((string) __('capell-theme-foundation::theme-migration.plan.not_carried', ['item' => $item, 'to' => $plan->toThemeKey]));
        }
    }

    private function rollback(string $reportPath): int
    {
        try {
            $preview = RollbackThemeMigrationAction::make()->preview($reportPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info((string) __('capell-theme-foundation::theme-migration.rollback.heading', ['from' => $preview['from'], 'to' => $preview['to']]));
        $this->listRecords($preview['sites'], $preview['layouts'], $preview['widgets']);

        if (! $this->option('force')) {
            $this->info((string) __('capell-theme-foundation::theme-migration.rollback.confirm'));

            return self::SUCCESS;
        }

        try {
            $outcome = RollbackThemeMigrationAction::run($reportPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info((string) __('capell-theme-foundation::theme-migration.rollback.done', ['count' => count($outcome['sites']), 'layouts' => count($outcome['layouts']), 'widgets' => count($outcome['widgets'])]));
        $this->line((string) ($outcome['successor_restored']
            ? __('capell-theme-foundation::theme-migration.rollback.successor_restored')
            : __('capell-theme-foundation::theme-migration.rollback.successor_kept')));

        if ($outcome['default_kept']) {
            $this->line((string) __('capell-theme-foundation::theme-migration.rollback.default_kept'));
        }

        if ($outcome['active_theme_kept']) {
            $this->line((string) __('capell-theme-foundation::theme-migration.rollback.active_theme_kept'));
        }

        if ($outcome['retired_theme_disabled']) {
            $this->warn((string) __('capell-theme-foundation::theme-migration.rollback.retired_theme_disabled', ['theme' => $preview['from']]));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $sites
     * @param  array<int, string>  $layouts
     * @param  array<int, string>  $widgets
     */
    private function listRecords(array $sites, array $layouts, array $widgets = []): void
    {
        foreach ($sites as $id => $name) {
            $this->line((string) __('capell-theme-foundation::theme-migration.plan.site', ['id' => $id, 'name' => $name]));
        }

        foreach ($layouts as $id => $name) {
            $this->line((string) __('capell-theme-foundation::theme-migration.plan.layout', ['id' => $id, 'name' => $name]));
        }

        foreach ($widgets as $id => $name) {
            $this->line((string) __('capell-theme-foundation::theme-migration.plan.widget', ['id' => $id, 'name' => $name]));
        }
    }

    /**
     * After a move, say whether anything still refers to the retired theme, so
     * the operator knows whether its package can be removed.
     *
     * @return bool whether the package is safe to remove
     */
    private function reportRemovalVerdict(string $retiredThemeKey): bool
    {
        $scanner = resolve(RetiredThemeReferenceScanner::class);
        $remaining = $scanner->removalVerdict($retiredThemeKey);

        if ($remaining === []) {
            $this->info((string) __('capell-theme-foundation::theme-migration.verdict.safe', ['theme' => $retiredThemeKey]));

            return true;
        }

        $this->warn((string) __('capell-theme-foundation::theme-migration.verdict.unsafe', ['theme' => $retiredThemeKey]));

        foreach ($remaining as $location => $count) {
            $this->line((string) __('capell-theme-foundation::theme-migration.verdict.location', ['location' => $location, 'count' => $count]));
        }

        $this->line((string) __('capell-theme-foundation::theme-migration.verdict.guidance'));

        return false;
    }
}
