<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSuccessors;
use Capell\FoundationTheme\Support\Themes\ThemeMigrationEnvironment;
use Capell\FoundationTheme\Support\Themes\ThemeStudioActiveTheme;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * Reverses one theme migration from its rollback report, refusing unless every
 * precondition still holds:
 *
 * - the report is readable and has not been used before;
 * - it was written on this installation (a report copied to a cloned
 *   environment is refused even when the theme ids match);
 * - its theme ids still belong to the same retired theme and declared
 *   successor;
 * - the retired theme is still installed, so whatever moves back renders.
 *
 * Only the sites, layouts and widgets the migration actually moved, and that are still
 * on the successor, go back. The successor record's previous preset, tokens
 * and status are restored only when no site or layout uses it any more and
 * nobody has edited it since; the default theme and the Theme Studio active
 * theme flip back only while they still hold what the migration wrote.
 * Anything left as it is is reported.
 */
class RollbackThemeMigrationAction
{
    use AsFake;
    use AsObject;

    /**
     * @return array{sites: array<int, string>, layouts: array<int, string>, widgets: array<int, string>, from: string, to: string}
     */
    public function preview(string $reportPath): array
    {
        $report = $this->readReport($reportPath);
        $checked = $this->checkPreconditions($report);
        $target = Theme::query()->find($checked['to_id']);
        $targetId = $target instanceof Theme ? $target->id : 0;

        return [
            'sites' => $this->stillOn($this->namedIds($report['sites'] ?? []), Site::query()->withoutGlobalScopes()->whereKey(array_keys($this->namedIds($report['sites'] ?? [])))->where('theme_id', $targetId)->pluck('id')->all()),
            'layouts' => $this->stillOn($this->namedIds($report['layouts'] ?? []), Layout::query()->withoutGlobalScopes()->whereKey(array_keys($this->namedIds($report['layouts'] ?? [])))->where('theme_id', $targetId)->pluck('id')->all()),
            'widgets' => $this->widgetsStillRemapped($this->widgetEntries($report['widgets'] ?? [])),
            'from' => $checked['from_key'],
            'to' => $checked['to_key'],
        ];
    }

    /**
     * @return array{sites: list<int>, layouts: list<int>, widgets: list<int>, successor_restored: bool, default_kept: bool, active_theme_kept: bool, retired_theme_disabled: bool}
     */
    public function handle(string $reportPath): array
    {
        $this->readReport($reportPath);

        $lock = Cache::lock(MigrateSitesToThemeAction::LOCK_KEY, 600);

        throw_unless($lock->get(), RuntimeException::class, (string) __('capell-theme-foundation::theme-migration.busy'));

        try {
            // Read again under the lock so two rollbacks cannot both pass the
            // "not used before" check.
            $report = $this->readReport($reportPath);
            $outcome = $this->rollback($report);

            // Stamp the report as soon as the transaction has committed, before
            // any cache or CDN refresh that could still fail.
            $report['rolled_back_at'] = now()->toIso8601String();
            $report['rolled_back_sites'] = $outcome['sites'];
            $report['rolled_back_layouts'] = $outcome['layouts'];
            $report['rolled_back_widgets'] = $outcome['widgets'];
            File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } finally {
            $lock->release();
        }

        RefreshMigratedThemeOutputAction::run($outcome['target'], [], null, []);
        RefreshMigratedThemeOutputAction::run($outcome['source'], $outcome['sites'], $outcome['target']->id, $outcome['layouts'], $outcome['widgets']);

        return [
            'sites' => $outcome['sites'],
            'layouts' => $outcome['layouts'],
            'widgets' => $outcome['widgets'],
            'successor_restored' => $outcome['successor_restored'],
            'default_kept' => $outcome['default_kept'],
            'active_theme_kept' => $outcome['active_theme_kept'],
            'retired_theme_disabled' => ! $outcome['source']->status,
        ];
    }

    private static function widgetId(Widget $widget): int
    {
        $key = $widget->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function readReport(string $reportPath): array
    {
        throw_unless(File::isFile($reportPath), RuntimeException::class, (string) __('capell-theme-foundation::theme-migration.rollback.missing_report', ['path' => $reportPath]));

        $invalid = (string) __('capell-theme-foundation::theme-migration.rollback.invalid_report', ['path' => $reportPath]);

        try {
            $report = json_decode(File::get($reportPath), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException($invalid);
        }

        throw_unless(
            is_array($report) && ($report['version'] ?? null) === 1 && is_array($report['from'] ?? null) && is_array($report['to'] ?? null) && is_array($report['sites'] ?? null),
            RuntimeException::class,
            $invalid,
        );

        throw_if(
            ($report['rolled_back_at'] ?? null) !== null,
            RuntimeException::class,
            (string) __('capell-theme-foundation::theme-migration.rollback.already_used', ['path' => $reportPath]),
        );

        throw_if(
            ($report['environment'] ?? null) !== ThemeMigrationEnvironment::fingerprint(),
            RuntimeException::class,
            (string) __('capell-theme-foundation::theme-migration.rollback.other_environment'),
        );

        $named = [];

        foreach ($report as $key => $value) {
            $named[(string) $key] = $value;
        }

        return $named;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{from_key: string, to_key: string, from_id: int, to_id: int}
     */
    private function checkPreconditions(array $report): array
    {
        $from = is_array($report['from'] ?? null) ? $report['from'] : [];
        $to = is_array($report['to'] ?? null) ? $report['to'] : [];
        $fromKey = is_string($from['key'] ?? null) ? $from['key'] : '';
        $toKey = is_string($to['key'] ?? null) ? $to['key'] : '';
        $fromId = is_numeric($from['theme_id'] ?? null) ? (int) $from['theme_id'] : 0;
        $toId = is_numeric($to['theme_id'] ?? null) ? (int) $to['theme_id'] : 0;
        $mismatched = (string) __('capell-theme-foundation::theme-migration.rollback.mismatched_report');

        throw_if($fromKey === '' || resolve(RetiredThemeSuccessors::class)->successorFor($fromKey) !== $toKey, RuntimeException::class, $mismatched);

        throw_unless(
            resolve(ThemeRegistry::class)->has($fromKey),
            RuntimeException::class,
            (string) __('capell-theme-foundation::theme-migration.rollback.retired_theme_missing', ['theme' => $fromKey]),
        );

        throw_unless(
            Theme::query()->whereKey($fromId)->where('key', $fromKey)->exists() && Theme::query()->whereKey($toId)->where('key', $toKey)->exists(),
            RuntimeException::class,
            $mismatched,
        );

        return ['from_key' => $fromKey, 'to_key' => $toKey, 'from_id' => $fromId, 'to_id' => $toId];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{sites: list<int>, layouts: list<int>, widgets: list<int>, successor_restored: bool, default_kept: bool, active_theme_kept: bool, source: Theme, target: Theme}
     */
    private function rollback(array $report): array
    {
        $checked = $this->checkPreconditions($report);
        $from = is_array($report['from'] ?? null) ? $report['from'] : [];
        $to = is_array($report['to'] ?? null) ? $report['to'] : [];
        $previous = is_array($to['previous'] ?? null) ? $to['previous'] : [];
        $activeTheme = is_array($report['active_theme'] ?? null) ? $report['active_theme'] : null;
        $siteIds = array_keys($this->namedIds($report['sites'] ?? []));
        $layoutIds = array_keys($this->namedIds($report['layouts'] ?? []));
        $widgets = $this->widgetEntries($report['widgets'] ?? []);

        return DB::transaction(function () use ($checked, $siteIds, $layoutIds, $widgets, $from, $to, $previous, $activeTheme): array {
            $source = Theme::query()->whereKey($checked['from_id'])->lockForUpdate()->first();
            $target = Theme::query()->whereKey($checked['to_id'])->lockForUpdate()->first();

            throw_unless(
                $source instanceof Theme && $target instanceof Theme && $source->key === $checked['from_key'] && $target->key === $checked['to_key'],
                RuntimeException::class,
                (string) __('capell-theme-foundation::theme-migration.rollback.mismatched_report'),
            );

            $movedBack = $this->idsStillOn(Site::query()->withoutGlobalScopes()->whereKey($siteIds), $target->id);
            $movedBackLayouts = $this->idsStillOn(Layout::query()->withoutGlobalScopes()->whereKey($layoutIds), $target->id);

            Site::query()->withoutGlobalScopes()->whereKey($movedBack)->update(['theme_id' => $source->id]);
            Layout::query()->withoutGlobalScopes()->whereKey($movedBackLayouts)->update(['theme_id' => $source->id]);
            $restoredWidgets = $this->restoreWidgets($widgets);

            // Restoring the successor's previous settings would restyle any
            // site or layout still on it, so only do it once it is unused again
            // and unchanged since the migration wrote it.
            $successorRestored = false;

            if (Site::query()->withoutGlobalScopes()->where('theme_id', $target->id)->doesntExist()
                && Layout::query()->withoutGlobalScopes()->where('theme_id', $target->id)->doesntExist()
                && json_encode($target->meta) === json_encode($to['written_meta'] ?? null)) {
                $previousMeta = $previous['meta'] ?? null;
                $target->meta = is_array($previousMeta) ? $previousMeta : null;
                $target->status = (bool) ($previous['status'] ?? $target->status);
                $successorRestored = true;
            }

            // The default flag moved with the migration; move it back only if
            // nobody has chosen another default since.
            $wasDefault = ($from['was_default'] ?? false) === true;
            $defaultKept = $wasDefault && ! $target->default;

            if ($wasDefault && $target->default) {
                $target->default = (bool) ($previous['default'] ?? false);
                $source->default = true;
                $source->saveQuietly();
            }

            $target->saveQuietly();

            $activeThemeKept = false;

            if ($activeTheme !== null) {
                $written = is_array($activeTheme['written'] ?? null) ? $activeTheme['written'] : [];
                $before = is_array($activeTheme['previous'] ?? null) ? $activeTheme['previous'] : [];
                $settings = resolve(ThemeStudioActiveTheme::class);
                $current = $settings->current();

                if ($current !== null && $current['theme'] === ($written['theme'] ?? null) && $current['preset'] === ($written['preset'] ?? null)
                    && is_string($before['theme'] ?? null) && is_string($before['preset'] ?? null)) {
                    $settings->switchTo($before['theme'], $before['preset']);
                } else {
                    $activeThemeKept = true;
                }
            }

            return [
                'sites' => $movedBack,
                'layouts' => $movedBackLayouts,
                'widgets' => $restoredWidgets,
                'successor_restored' => $successorRestored,
                'default_kept' => $defaultKept,
                'active_theme_kept' => $activeThemeKept,
                'source' => $source,
                'target' => $target,
            ];
        });
    }

    /**
     * @param  Builder<Site>|Builder<Layout>  $query
     * @return list<int>
     */
    private function idsStillOn($query, int $themeId): array
    {
        return array_values($query
            ->where('theme_id', $themeId)
            ->lockForUpdate()
            ->pluck('id')
            ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
            ->all());
    }

    /**
     * @return array<int, string>
     */
    private function namedIds(mixed $items): array
    {
        $named = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item) && is_numeric($item['id'] ?? null)) {
                $named[(int) $item['id']] = is_string($item['name'] ?? null) ? $item['name'] : '';
            }
        }

        return $named;
    }

    /**
     * @param  array<int, string>  $named
     * @param  array<mixed>  $ids
     * @return array<int, string>
     */
    private function stillOn(array $named, array $ids): array
    {
        $still = [];

        foreach ($ids as $id) {
            if (is_numeric($id) && array_key_exists((int) $id, $named)) {
                $still[(int) $id] = $named[(int) $id];
            }
        }

        ksort($still);

        return $still;
    }

    /**
     * @return array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>
     */
    private function widgetEntries(mixed $items): array
    {
        $entries = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item) || ! is_numeric($item['id'] ?? null)) {
                continue;
            }

            $string = static fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null;
            $entries[(int) $item['id']] = [
                'name' => is_string($item['name'] ?? null) ? $item['name'] : '',
                'component_from' => $string($item['component_from'] ?? null),
                'component_to' => $string($item['component_to'] ?? null),
                'meta_component_from' => $string($item['meta_component_from'] ?? null),
                'meta_component_to' => $string($item['meta_component_to'] ?? null),
                'section_type_from' => $string($item['section_type_from'] ?? null),
                'section_type_to' => $string($item['section_type_to'] ?? null),
                'items_from' => $string($item['items_from'] ?? null),
                'section_type_added' => ($item['section_type_added'] ?? false) === true,
                'hidden_fields' => array_values(array_filter(is_array($item['hidden_fields'] ?? null) ? $item['hidden_fields'] : [], is_string(...))),
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets
     * @return array<int, string> widget id => name, for widgets still on the renderables the migration set
     */
    private function widgetsStillRemapped(array $widgets): array
    {
        $still = [];

        foreach ($this->unchangedWidgets($widgets, false) as $widget) {
            $still[self::widgetId($widget)] = $widgets[self::widgetId($widget)]['name'];
        }

        ksort($still);

        return $still;
    }

    /**
     * Restores the retired renderables on widgets still pointing at what the
     * migration set; a widget an editor changed since is left alone.
     *
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets
     * @return list<int>
     */
    private function restoreWidgets(array $widgets): array
    {
        $restored = [];

        foreach ($this->unchangedWidgets($widgets, true) as $widget) {
            $remap = $widgets[self::widgetId($widget)];
            $meta = $widget->getAttribute('meta');
            $meta = is_array($meta) ? $meta : [];

            if ($remap['meta_component_from'] !== null) {
                $meta['component'] = $remap['meta_component_from'];
            }

            if ($remap['section_type_from'] !== null && ($meta['type'] ?? null) === $remap['section_type_to']) {
                // A type the migration added is removed, not replaced.
                if ($remap['section_type_added']) {
                    unset($meta['type']);
                } else {
                    $meta['type'] = $remap['section_type_from'];
                }

                // The migration only copied the items; the original list is
                // still saved under its own key.
                if ($remap['items_from'] !== null && ($meta['items'] ?? null) === ($meta[$remap['items_from']] ?? null)) {
                    unset($meta['items']);
                }
            }

            // Written as stored, as the migration wrote it.
            Widget::query()->withoutGlobalScopes()->whereKey(self::widgetId($widget))->toBase()->update([
                'component' => $remap['component_from'] ?? $widget->getAttribute('component'),
                'meta' => $meta === [] ? $widget->getRawOriginal('meta') : json_encode($meta, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
            $restored[] = self::widgetId($widget);
        }

        sort($restored);

        return $restored;
    }

    /**
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets
     * @return list<Widget>
     */
    private function unchangedWidgets(array $widgets, bool $lock): array
    {
        if ($widgets === [] || ! Schema::hasTable(new Widget()->getTable())) {
            return [];
        }

        $query = Widget::query()->withoutGlobalScopes()->whereKey(array_keys($widgets));

        if ($lock) {
            $query->lockForUpdate();
        }

        $unchanged = [];

        foreach ($query->get() as $widget) {
            $remap = $widgets[self::widgetId($widget)];
            $meta = $widget->getAttribute('meta');
            $metaComponent = is_array($meta) ? ($meta['component'] ?? null) : null;

            if (($remap['component_to'] === null || $widget->getAttribute('component') === $remap['component_to'])
                && ($remap['meta_component_to'] === null || $metaComponent === $remap['meta_component_to'])
                && ($remap['section_type_to'] === null || (is_array($meta) && ($meta['type'] ?? null) === $remap['section_type_to']))) {
                $unchanged[] = $widget;
            }
        }

        return $unchanged;
    }
}
