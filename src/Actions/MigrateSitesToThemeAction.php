<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Actions\CreateThemeAction;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Data\ThemeMigrationPlanData;
use Capell\FoundationTheme\Data\ThemeMigrationResultData;
use Capell\FoundationTheme\Support\Themes\ThemeMigrationEnvironment;
use Capell\FoundationTheme\Support\Themes\ThemeStudioActiveTheme;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;
use Throwable;

/**
 * Moves onto a retired theme's successor, as planned by
 * {@see PlanThemeMigrationAction}: every site and layout on the retired theme
 * and every widget naming one of its section renderables (soft-deleted ones
 * included, so a restored record never lands on a removed theme), and the
 * Theme Studio active theme when it names the retired theme.
 * Page text, media and navigation are not
 * touched; sites and layouts change theme and theme section widgets change
 * renderable.
 *
 * Before anything is written a rollback report records the moved site ids, the
 * theme each one used and the successor record's previous state.
 * {@see RollbackThemeMigrationAction} reads that report and moves back exactly
 * those sites, never every site on the successor.
 *
 * Writes happen inside one transaction without model events; cache flushes,
 * CDN surrogate purges and theme image generation run only after it commits, so
 * no request can re-cache the old output mid-change.
 */
class MigrateSitesToThemeAction
{
    use AsFake;
    use AsObject;

    public const string LOCK_KEY = 'capell:theme-migrate';

    /**
     * @throws JsonException
     */
    public function handle(string $fromThemeKey, string $toThemeKey, ?string $presetKey = null, bool $allowSharedSuccessor = false): ThemeMigrationResultData
    {
        $lock = Cache::lock(self::LOCK_KEY, 600);

        throw_unless($lock->get(), RuntimeException::class, (string) __('capell-theme-foundation::theme-migration.busy'));

        try {
            return $this->migrate($fromThemeKey, $toThemeKey, $presetKey, $allowSharedSuccessor);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $remaps
     * @return list<array{id: int, name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>
     */
    private static function widgetEntries(array $remaps): array
    {
        $entries = [];

        foreach ($remaps as $id => $remap) {
            $entries[] = ['id' => $id, ...$remap];
        }

        return $entries;
    }

    /**
     * @param  array<int, string>  $items
     * @return list<array{id: int, name: string}>
     */
    private static function named(array $items): array
    {
        $named = [];

        foreach ($items as $id => $name) {
            $named[] = ['id' => $id, 'name' => $name];
        }

        return $named;
    }

    private static function widgetId(Widget $widget): int
    {
        $key = $widget->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    /**
     * Points each planned widget at the successor's renderable for the same
     * section, skipping any widget an editor changed since the plan was read.
     *
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $remaps
     * @return list<int> ids of the widgets that were remapped
     */
    private function remapWidgets(array $remaps): array
    {
        if ($remaps === []) {
            return [];
        }

        $remapped = [];

        foreach (Widget::query()->withoutGlobalScopes()->whereKey(array_keys($remaps))->lockForUpdate()->get() as $widget) {
            $id = self::widgetId($widget);
            $remap = $remaps[$id];
            $meta = $widget->getAttribute('meta');
            $meta = is_array($meta) ? $meta : [];

            if ($widget->getAttribute('component') !== ($remap['component_from'] ?? $widget->getAttribute('component'))
                || ($remap['meta_component_from'] !== null && ($meta['component'] ?? null) !== $remap['meta_component_from'])
                || ($remap['section_type_from'] !== null && ($meta['type'] ?? null) !== ($remap['section_type_added'] ? null : $remap['section_type_from']))) {
                continue;
            }

            if ($remap['meta_component_to'] !== null) {
                $meta['component'] = $remap['meta_component_to'];
            }

            if ($remap['section_type_to'] !== null && ($meta['type'] ?? null) === ($remap['section_type_added'] ? null : $remap['section_type_from'])) {
                $meta['type'] = $remap['section_type_to'];

                if ($remap['items_from'] !== null && ! isset($meta['items'])) {
                    $meta['items'] = $meta[$remap['items_from']] ?? [];
                }
            }

            // Written as stored: the model's meta mutator would hoist a
            // legacy meta.component into the column and change the row's shape.
            Widget::query()->withoutGlobalScopes()->whereKey($id)->toBase()->update([
                'component' => $remap['component_to'] ?? $widget->getAttribute('component'),
                'meta' => $meta === [] ? $widget->getRawOriginal('meta') : json_encode($meta, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
            $remapped[] = $id;
        }

        sort($remapped);

        return $remapped;
    }

    /**
     * @throws JsonException
     */
    private function migrate(string $fromThemeKey, string $toThemeKey, ?string $presetKey, bool $allowSharedSuccessor): ThemeMigrationResultData
    {
        $plan = PlanThemeMigrationAction::run($fromThemeKey, $toThemeKey, $presetKey, $allowSharedSuccessor);

        throw_unless($plan->canApply(), RuntimeException::class, implode(' ', $plan->blockers));

        if ($plan->fromThemeId === null || ! $plan->hasWork()) {
            return new ThemeMigrationResultData($plan, [], [], [], null);
        }

        $target = Theme::query()->find($plan->toThemeId) ?? $this->createSuccessor($plan);
        $createdSuccessor = $plan->toThemeId === null;
        $source = Theme::query()->findOrFail($plan->fromThemeId);
        $reportPath = $this->reportPath($plan);

        $writtenMeta = $plan->writesSuccessorPreset ? $this->successorMeta($target, $source, $plan) : $target->meta;
        $activeTheme = resolve(ThemeStudioActiveTheme::class)->current();
        $report = [
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'environment' => ThemeMigrationEnvironment::fingerprint(),
            'from' => ['theme_id' => $source->id, 'key' => $plan->fromThemeKey, 'was_default' => (bool) $source->default],
            'to' => [
                'theme_id' => $target->id,
                'key' => $plan->toThemeKey,
                'created_by_migration' => $createdSuccessor,
                'previous' => ['meta' => $target->meta, 'status' => (bool) $target->status, 'default' => (bool) $target->default],
                'written_meta' => $writtenMeta,
            ],
            'preset' => $plan->presetKey,
            'tokens_carried' => $plan->carriesTokens,
            'token_conflicts' => $plan->tokenConflicts,
            'not_carried' => $plan->notCarried,
            'sites' => self::named($plan->sites),
            'layouts' => self::named($plan->layouts),
            'widgets' => self::widgetEntries($plan->widgets),
            'active_theme' => $plan->switchesActiveTheme && $activeTheme !== null
                ? ['previous' => $activeTheme, 'written' => ['theme' => $plan->toThemeKey, 'preset' => $plan->presetKey]]
                : null,
        ];
        $this->writeReport($reportPath, $report);

        try {
            $moved = DB::transaction(function () use ($plan, $target, $source, $writtenMeta): array {
                $lockedTarget = Theme::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
                $lockedSource = Theme::query()->whereKey($source->getKey())->lockForUpdate()->firstOrFail();

                // The plan and the rollback report were built from the records
                // as they were read; refuse if an editor changed the successor
                // or gave it sites or layouts since, rather than overwrite that change.
                throw_if(
                    json_encode($lockedTarget->meta) !== json_encode($target->meta)
                        || ($plan->writesSuccessorPreset && (
                            Site::query()->withoutGlobalScopes()->where('theme_id', $lockedTarget->getKey())->exists()
                            || Layout::query()->withoutGlobalScopes()->where('theme_id', $lockedTarget->getKey())->exists()
                        )),
                    RuntimeException::class,
                    (string) __('capell-theme-foundation::theme-migration.changed', ['theme' => $plan->toThemeKey]),
                );
                $siteIds = Site::query()->withoutGlobalScopes()
                    ->whereKey(array_keys($plan->sites))
                    ->where('theme_id', $lockedSource->getKey())
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all();
                $layoutIds = Layout::query()->withoutGlobalScopes()
                    ->whereKey(array_keys($plan->layouts))
                    ->where('theme_id', $lockedSource->getKey())
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all();

                if ($plan->writesSuccessorPreset) {
                    $lockedTarget->meta = is_array($writtenMeta) ? $writtenMeta : null;
                }

                $lockedTarget->status = true;

                if ($lockedSource->default) {
                    $lockedTarget->default = true;
                    $lockedSource->default = false;
                    $lockedSource->saveQuietly();
                }

                $lockedTarget->saveQuietly();

                Site::query()->withoutGlobalScopes()->whereKey($siteIds)->update(['theme_id' => $lockedTarget->getKey()]);
                Layout::query()->withoutGlobalScopes()->whereKey($layoutIds)->update(['theme_id' => $lockedTarget->getKey()]);

                if ($plan->switchesActiveTheme) {
                    resolve(ThemeStudioActiveTheme::class)->switchTo($plan->toThemeKey, $plan->presetKey);
                }

                $widgetIds = $this->remapWidgets($plan->widgets);

                return [
                    'widgets' => $widgetIds,
                    'sites' => array_values(array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $siteIds)),
                    'layouts' => array_values(array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $layoutIds)),
                ];
            });
        } catch (Throwable $throwable) {
            File::delete($reportPath);

            throw $throwable;
        }

        // The report was written with the planned sites and layouts before the
        // move; keep only those actually moved so a rollback cannot pick up
        // one that reached the successor some other way.
        $movedSites = array_intersect_key($plan->sites, array_flip($moved['sites']));
        $movedLayouts = array_intersect_key($plan->layouts, array_flip($moved['layouts']));
        $report['sites'] = self::named($movedSites);
        $report['layouts'] = self::named($movedLayouts);
        $movedWidgets = array_intersect_key($plan->widgets, array_flip($moved['widgets']));
        $report['widgets'] = self::widgetEntries($movedWidgets);
        $this->writeReport($reportPath, $report);

        RefreshMigratedThemeOutputAction::run($target, $moved['sites'], $source->id, $moved['layouts'], $moved['widgets']);

        return new ThemeMigrationResultData($plan, $movedSites, $movedLayouts, $moved['widgets'], $reportPath);
    }

    private function createSuccessor(ThemeMigrationPlanData $plan): Theme
    {
        // A brand-new record has no sites yet, so its own model events cannot
        // purge or re-cache anything a visitor sees. If the move later fails,
        // this unused record stays behind; it is harmless and the next run
        // reuses it.
        return CreateThemeAction::run(
            key: $plan->toThemeKey,
            name: resolve(ThemeRegistry::class)->definition($plan->toThemeKey)->name,
            default: false,
            activePreset: $plan->presetKey,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function successorMeta(Theme $target, Theme $source, ThemeMigrationPlanData $plan): array
    {
        $meta = [];

        foreach (is_array($target->meta) ? $target->meta : [] as $key => $value) {
            $meta[(string) $key] = $value;
        }

        $editor = is_array($meta['editor'] ?? null) ? $meta['editor'] : [];
        $sourceMeta = is_array($source->meta) ? $source->meta : [];
        $sourceEditor = is_array($sourceMeta['editor'] ?? null) ? $sourceMeta['editor'] : [];

        if ($plan->carriesTokens && is_array($sourceEditor['tokens'] ?? null)) {
            $editor['tokens'] = $sourceEditor['tokens'];
        }

        $preset = is_array($editor['preset'] ?? null) ? $editor['preset'] : [];
        $preset['active'] = $plan->presetKey;
        $editor['preset'] = $preset;
        $meta['editor'] = $editor;
        $meta['active_preset'] = $plan->presetKey;

        return $meta;
    }

    private function reportPath(ThemeMigrationPlanData $plan): string
    {
        return storage_path(sprintf(
            'app/capell/theme-migrations/%s-%s-to-%s.json',
            now()->format('Ymd-His-u'),
            $plan->fromThemeKey,
            $plan->toThemeKey,
        ));
    }

    /**
     * @param  array<string, mixed>  $report
     *
     * @throws JsonException
     */
    private function writeReport(string $path, array $report): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
