<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Core\Support\TableName;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Data\ThemeMigrationPlanData;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\Support\Themes\RetiredThemeReferenceScanner;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSectionMap;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSuccessors;
use Capell\FoundationTheme\Support\Themes\ThemeStudioActiveTheme;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Works out, without changing anything, what moving every site, layout and theme
 * section widget from a retired theme onto its successor would do.
 *
 * The successor's theme record is one global row shared by every site on that
 * theme, so when it is already in use or already customised the move leaves
 * that record exactly as it is: the operator must name the successor's current
 * preset and confirm that the moved sites will take on the successor's saved
 * design tokens. Every token that differs is listed.
 */
class PlanThemeMigrationAction
{
    use AsFake;
    use AsObject;

    /** Theme meta that belongs to the editor or the preset, handled separately. */
    private const array PRESET_META_KEYS = ['editor', 'active_preset'];

    private const string WIDGET_RENDERABLE_TYPE = 'layout-widget';

    /** Theme section renderables share one view that includes `sections.<type>`. */
    private const string SECTION_VIEW_SUFFIX = '::widget.section';

    /** Remaining references in these locations block the move. */
    private const array UNMOVABLE_LOCATIONS = [
        'layouts.containers',
        'widgets.component_item',
        'widgets.meta.component_item',
        'blueprints.component',
        'blueprints.meta.component',
        'blueprints.component_item',
        'blueprints.meta.component_item',
    ];

    private const array MEDIA_COLLECTIONS = [
        'hero_video_desktop',
        'hero_video_tablet',
        'hero_video_mobile',
        'hero_image_desktop',
        'hero_image_tablet',
        'hero_image_mobile',
    ];

    public function handle(string $fromThemeKey, string $toThemeKey, ?string $presetKey = null, bool $allowSharedSuccessor = false): ThemeMigrationPlanData
    {
        $blockers = [];
        $successorKey = resolve(RetiredThemeSuccessors::class)->successorFor($fromThemeKey);

        if ($successorKey === null) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.not_retired', ['theme' => $fromThemeKey]);
        } elseif ($successorKey !== $toThemeKey) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.wrong_successor', ['theme' => $fromThemeKey, 'successor' => $successorKey]);
        }

        $registry = resolve(ThemeRegistry::class);
        $successor = $registry->has($toThemeKey) ? $registry->definition($toThemeKey) : null;

        if ($successor === null) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.successor_not_installed', ['theme' => $toThemeKey]);
        }

        $explicitPreset = is_string($presetKey) && $presetKey !== '' ? $presetKey : null;

        if ($explicitPreset !== null && $successor !== null && $successor->preset($explicitPreset) === null) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.unknown_preset', ['theme' => $toThemeKey, 'preset' => $explicitPreset]);
        }

        $source = Theme::query()->where('key', $fromThemeKey)->first();
        $target = Theme::query()->where('key', $toThemeKey)->first();
        $sites = $source instanceof Theme ? $this->sitesOn($source) : [];
        $layouts = $source instanceof Theme ? $this->layoutsOn($source) : [];
        $successorSiteCount = $target instanceof Theme ? Site::query()->withoutGlobalScopes()->where('theme_id', $target->getKey())->count() : 0;
        $successorLayoutCount = $target instanceof Theme ? Layout::query()->withoutGlobalScopes()->where('theme_id', $target->getKey())->count() : 0;
        $activeTheme = resolve(ThemeStudioActiveTheme::class)->current();
        $sourceTokens = $this->tokens($source);
        $targetTokens = $this->tokens($target);
        $targetPreset = $this->activePreset($target);
        $successorIsShared = $target instanceof Theme && ($successorSiteCount > 0 || $successorLayoutCount > 0 || $targetTokens !== []);

        if ($successorIsShared) {
            if ($explicitPreset === null) {
                $blockers[] = __('capell-theme-foundation::theme-migration.blockers.shared_needs_preset', ['theme' => $toThemeKey, 'preset' => $targetPreset ?? '']);
            } elseif ($targetPreset !== null && $explicitPreset !== $targetPreset) {
                $blockers[] = __('capell-theme-foundation::theme-migration.blockers.shared_preset_differs', ['theme' => $toThemeKey, 'preset' => $targetPreset]);
            }

            if (! $allowSharedSuccessor) {
                $blockers[] = __('capell-theme-foundation::theme-migration.blockers.shared_needs_confirmation', ['theme' => $toThemeKey, 'sites' => $successorSiteCount, 'layouts' => $successorLayoutCount]);
            }
        }

        // Theme section widgets name a renderable the retired package
        // registers. Each is remapped to the same section on the successor; a
        // section the successor does not provide blocks the whole move.
        $widgets = $this->widgetRemaps($fromThemeKey, $toThemeKey);
        $missingSections = [];

        foreach ($widgets as $id => $remap) {
            foreach ([$remap['component_to'], $remap['meta_component_to']] as $renderable) {
                if ($renderable !== null && ! $this->renderableExists($renderable)) {
                    $missingSections[$renderable][] = $id;
                }
            }
        }

        if (! $source instanceof Theme && $widgets !== []) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.orphan_widgets', [
                'theme' => $fromThemeKey,
                'successor' => $toThemeKey,
                'widgets' => implode(', ', array_keys($widgets)),
            ]);
        }

        // A theme's section renderable draws the widget's saved section type
        // with that theme's own section view; a type the successor has no view
        // for would render nothing, so it blocks the move.
        foreach ($this->sectionViewGaps($widgets) as $view => $ids) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.missing_section_view', [
                'theme' => $toThemeKey,
                'view' => $view,
                'widgets' => implode(', ', $ids),
            ]);
        }

        // A retired section printed sample items when it had none of its own;
        // the successor section prints nothing, so moving it would make the
        // section vanish from the page.
        foreach ($this->emptyMappedSections($fromThemeKey, $widgets) as $type => $ids) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.empty_mapped_section', [
                'theme' => $toThemeKey,
                'section' => $type,
                'widgets' => implode(', ', $ids),
            ]);
        }

        foreach ($missingSections as $renderable => $ids) {
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.missing_section', [
                'theme' => $toThemeKey,
                'renderable' => $renderable,
                'widgets' => implode(', ', array_unique($ids)),
            ]);
        }

        // A Publishing Studio draft would publish the retired theme back over
        // the move, and the move cannot rewrite a workspace's copy safely.
        $workspaces = $this->workspaceDrafts(RetiredThemeReferenceScanner::themeIds($fromThemeKey), array_keys($widgets));

        if ($workspaces !== []) {
            $abandoned = $this->abandonedWorkspaces($workspaces);
            $labels = array_map(static fn (int $id): string => in_array($id, $abandoned, true) ? $id . ' (abandoned)' : (string) $id, $workspaces);
            $blockers[] = __('capell-theme-foundation::theme-migration.blockers.workspace_drafts', ['theme' => $fromThemeKey, 'workspaces' => implode(', ', $labels)]);

            foreach ($abandoned as $id) {
                $blockers[] = __('capell-theme-foundation::theme-migration.blockers.abandoned_workspace', ['id' => $id]);
            }
        }

        // References this command cannot move safely block the move rather
        // than leave it half done.
        $scan = resolve(RetiredThemeReferenceScanner::class)->scan($fromThemeKey);

        foreach (self::UNMOVABLE_LOCATIONS as $location) {
            if (($scan[$location] ?? 0) > 0) {
                $blockers[] = __('capell-theme-foundation::theme-migration.blockers.unmovable_reference', ['location' => $location, 'count' => $scan[$location], 'theme' => $fromThemeKey]);
            }
        }

        $resolvedPreset = $explicitPreset
            ?? ($successorIsShared ? $targetPreset : null)
            ?? ($successor instanceof ThemeDefinitionData && $successor->presets !== [] ? $successor->presets[0]->key : null)
            ?? '';

        return new ThemeMigrationPlanData(
            fromThemeKey: $fromThemeKey,
            toThemeKey: $toThemeKey,
            presetKey: $resolvedPreset,
            fromThemeId: $source instanceof Theme ? $source->id : null,
            toThemeId: $target instanceof Theme ? $target->id : null,
            sites: $sites,
            layouts: $layouts,
            widgets: $widgets,
            switchesActiveTheme: ($activeTheme['theme'] ?? null) === $fromThemeKey,
            successorSiteCount: $successorSiteCount,
            successorLayoutCount: $successorLayoutCount,
            successorIsShared: $successorIsShared,
            successorDisabled: $target instanceof Theme && ! $target->status,
            writesSuccessorPreset: ! $successorIsShared,
            carriesTokens: ! $successorIsShared && $sourceTokens !== [],
            tokenConflicts: $successorIsShared ? $this->tokenConflicts($sourceTokens, $targetTokens) : [],
            notCarried: $source instanceof Theme ? $this->notCarried($source, $target) : [],
            blockers: array_values(array_map(static fn (mixed $blocker): string => (string) $blocker, $blockers)),
        );
    }

    private static function widgetId(Widget $widget): int
    {
        $key = $widget->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    /**
     * Soft-deleted records move too, so a restored one keeps rendering; the
     * plan marks them so a deleted copy does not read as a duplicate.
     */
    private static function listedName(string $name, bool $deleted): string
    {
        return $deleted ? (string) __('capell-theme-foundation::theme-migration.plan.deleted_name', ['name' => $name]) : $name;
    }

    /**
     * @return array<int, string>
     */
    private function sitesOn(Theme $theme): array
    {
        $sites = [];

        foreach (Site::query()->withoutGlobalScopes()->where('theme_id', $theme->getKey())->orderBy('id')->get(['id', 'name', 'deleted_at']) as $site) {
            $sites[(int) $site->id] = self::listedName((string) $site->name, $site->getAttribute('deleted_at') !== null);
        }

        return $sites;
    }

    /**
     * Abandoned workspaces keep their rows until they are pruned.
     *
     * @param  list<int>  $workspaces
     * @return list<int>
     */
    private function abandonedWorkspaces(array $workspaces): array
    {
        $table = TableName::resolve('workspaces');

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return [];
        }

        $abandoned = [];

        foreach (DB::table($table)->whereIn('id', $workspaces)->where('status', 'abandoned')->orderBy('id')->pluck('id') as $id) {
            if (is_numeric($id)) {
                $abandoned[] = (int) $id;
            }
        }

        return $abandoned;
    }

    /**
     * Workspaces holding a draft of the theme record, or a draft site, layout
     * or widget that still uses the theme.
     *
     * @param  list<int>  $themeIds
     * @param  list<int>  $widgetIds
     * @return list<int>
     */
    private function workspaceDrafts(array $themeIds, array $widgetIds): array
    {
        $workspaces = [];

        foreach ([
            [new Theme()->getTable(), 'id', $themeIds],
            [new Site()->getTable(), 'theme_id', $themeIds],
            [new Layout()->getTable(), 'theme_id', $themeIds],
            [new Widget()->getTable(), 'id', $widgetIds],
        ] as [$table, $column, $ids]) {
            if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'workspace_id')) {
                continue;
            }

            foreach (DB::table($table)->whereIn($column, $ids)->where('workspace_id', '!=', 0)->pluck('workspace_id') as $workspace) {
                if (is_numeric($workspace)) {
                    $workspaces[] = (int) $workspace;
                }
            }
        }

        $workspaces = array_values(array_unique($workspaces));
        sort($workspaces);

        return $workspaces;
    }

    /**
     * @return array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>
     */
    private function widgetRemaps(string $fromThemeKey, string $toThemeKey): array
    {
        if (! Schema::hasTable(new Widget()->getTable())) {
            return [];
        }

        $prefix = RetiredThemeReferenceScanner::renderablePrefix($fromThemeKey);
        $successorPrefix = RetiredThemeReferenceScanner::renderablePrefix($toThemeKey);
        $remaps = [];

        $widgets = Widget::query()->withoutGlobalScopes()
            ->where(static fn (Builder $query): Builder => $query
                ->where(static fn (Builder $inner): Builder => RetiredThemeReferenceScanner::whereStartsWith($inner, 'component', $prefix))
                ->orWhere(static fn (Builder $inner): Builder => RetiredThemeReferenceScanner::whereStartsWith($inner, 'meta->component', $prefix)))
            ->orderBy('id')
            ->get();

        foreach ($widgets as $widget) {
            $component = $widget->getAttribute('component');
            $meta = $widget->getAttribute('meta');
            $metaComponent = is_array($meta) ? ($meta['component'] ?? null) : null;
            $component = is_string($component) && str_starts_with($component, $prefix) ? $component : null;
            $metaComponent = is_string($metaComponent) && str_starts_with($metaComponent, $prefix) ? $metaComponent : null;
            $name = $widget->getAttribute('name');
            $meta = is_array($meta) ? $meta : [];
            $savedType = is_string($meta['type'] ?? null) && $meta['type'] !== '' ? $meta['type'] : null;
            // Some themes' bespoke widgets save no section type; their
            // renderable name says which section they are.
            $named = $component ?? $metaComponent;
            $impliedType = $savedType === null && $named !== null ? substr($named, strlen($prefix)) : null;
            $sectionType = $savedType ?? ($impliedType !== null && resolve(RetiredThemeSectionMap::class)->for($fromThemeKey, $impliedType) !== null ? $impliedType : null);
            // A retired section the successor offers under another name is
            // pointed at it; the saved data stays, and only a copy of the
            // items lands where the successor reads them.
            $mapping = $sectionType === null ? null : resolve(RetiredThemeSectionMap::class)->for($fromThemeKey, $sectionType);
            $itemsFrom = $mapping !== null && $mapping['items_from'] !== null && ! isset($meta['items']) && is_array($meta[$mapping['items_from']] ?? null)
                ? $mapping['items_from']
                : null;

            $remaps[self::widgetId($widget)] = [
                'name' => is_string($name) ? $name : '',
                'component_from' => $component,
                'component_to' => $component === null ? null : $this->successorRenderable($component, $prefix, $successorPrefix, $sectionType, $mapping),
                'meta_component_from' => $metaComponent,
                'meta_component_to' => $metaComponent === null ? null : $this->successorRenderable($metaComponent, $prefix, $successorPrefix, $sectionType, $mapping),
                'section_type_from' => $mapping === null ? null : $sectionType,
                'section_type_to' => $mapping['type'] ?? null,
                'items_from' => $itemsFrom,
                'section_type_added' => $mapping !== null && $savedType === null,
                'hidden_fields' => $mapping === null ? [] : resolve(RetiredThemeSectionMap::class)->hiddenFields($mapping, $meta),
            ];
        }

        return $remaps;
    }

    /**
     * The successor renderable a retired theme's widget moves to. A widget
     * keeps its renderable name when the successor registers it. Otherwise
     * it moves by its section type: to the successor's renderable for the
     * section it maps onto, or, for a standard section the successor has no
     * renderable for, to Foundation's, which every theme inherits. A widget
     * none of these draw keeps its name, so the move still blocks on it.
     *
     * @param  array{type: string, items_from: ?string, not_shown: list<string>, requires_items?: bool}|null  $mapping
     */
    private function successorRenderable(string $renderable, string $prefix, string $successorPrefix, ?string $sectionType, ?array $mapping): string
    {
        $suffix = substr($renderable, strlen($prefix));
        // A renderable named after its section follows the section's mapping.
        $own = $successorPrefix . ($mapping !== null && $suffix === $sectionType ? $mapping['type'] : $suffix);

        if ($sectionType === null || $this->renderableExists($own)) {
            return $own;
        }

        $target = $mapping['type'] ?? $sectionType;

        if ($this->renderableExists($successorPrefix . $target)) {
            return $successorPrefix . $target;
        }

        $foundation = FoundationSectionWidgetComponentEnum::fromSectionType($target);

        return $foundation instanceof FoundationSectionWidgetComponentEnum ? $foundation->value : $own;
    }

    /**
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets
     * @return array<string, list<int>> missing successor section view => widget ids
     */
    private function sectionViewGaps(array $widgets): array
    {
        if ($widgets === []) {
            return [];
        }

        $gaps = [];

        foreach (Widget::query()->withoutGlobalScopes()->whereKey(array_keys($widgets))->orderBy('id')->get() as $widget) {
            $id = self::widgetId($widget);
            $renderable = $widgets[$id]['component_to'] ?? $widgets[$id]['meta_component_to'];
            $meta = $widget->getAttribute('meta');
            $type = $widgets[$id]['section_type_to']
                ?? (is_array($meta) && is_string($meta['type'] ?? null) && $meta['type'] !== '' ? $meta['type'] : null);
            $blade = $renderable !== null && $this->renderableExists($renderable)
                ? resolve(RenderableRegistry::class)->get(self::WIDGET_RENDERABLE_TYPE, $renderable)->blade
                : null;

            if ($type === null || ! is_string($blade) || ! str_ends_with($blade, self::SECTION_VIEW_SUFFIX)) {
                continue;
            }

            $view = substr($blade, 0, -strlen(self::SECTION_VIEW_SUFFIX)) . '::sections.' . $type;

            if (! view()->exists($view)) {
                $gaps[$view][] = $id;
            }
        }

        return $gaps;
    }

    /**
     * @param  array<int, array{name: string, component_from: ?string, component_to: ?string, meta_component_from: ?string, meta_component_to: ?string, section_type_from: ?string, section_type_to: ?string, items_from: ?string, section_type_added: bool, hidden_fields: list<string>}>  $widgets
     * @return array<string, list<int>> retired section type => ids of mapped widgets with no items for the successor section
     */
    private function emptyMappedSections(string $fromThemeKey, array $widgets): array
    {
        $mapped = array_filter($widgets, static fn (array $remap): bool => $remap['section_type_to'] !== null);

        if ($mapped === []) {
            return [];
        }

        $map = resolve(RetiredThemeSectionMap::class);
        $empty = [];

        foreach (Widget::query()->withoutGlobalScopes()->whereKey(array_keys($mapped))->orderBy('id')->get() as $widget) {
            $id = self::widgetId($widget);
            $remap = $mapped[$id];
            $meta = $widget->getAttribute('meta');
            $meta = is_array($meta) ? $meta : [];
            $mapping = $remap['section_type_from'] === null ? null : $map->for($fromThemeKey, $remap['section_type_from']);

            if ($mapping !== null && ($mapping['requires_items'] ?? true) && $map->successorItems($mapping, $meta) === []) {
                $empty[$remap['section_type_from']][] = $id;
            }
        }

        return $empty;
    }

    private function renderableExists(string $key): bool
    {
        try {
            resolve(RenderableRegistry::class)->get(self::WIDGET_RENDERABLE_TYPE, $key);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Layouts can name a theme of their own, which wins over the site's theme
     * for every page on that layout, so they move with the sites.
     *
     * @return array<int, string>
     */
    private function layoutsOn(Theme $theme): array
    {
        $layouts = [];

        foreach (Layout::query()->withoutGlobalScopes()->where('theme_id', $theme->getKey())->orderBy('id')->get(['id', 'name', 'deleted_at']) as $layout) {
            $layouts[(int) $layout->id] = self::listedName(is_string($layout->name) ? $layout->name : '', $layout->getAttribute('deleted_at') !== null);
        }

        return $layouts;
    }

    /**
     * @return array<string, mixed>
     */
    private function tokens(?Theme $theme): array
    {
        $meta = $theme instanceof Theme && is_array($theme->meta) ? $theme->meta : [];
        $editor = is_array($meta['editor'] ?? null) ? $meta['editor'] : [];
        $tokens = is_array($editor['tokens'] ?? null) ? $editor['tokens'] : [];
        $named = [];

        foreach ($tokens as $key => $value) {
            $named[(string) $key] = $value;
        }

        return $named;
    }

    private function activePreset(?Theme $theme): ?string
    {
        $meta = $theme instanceof Theme && is_array($theme->meta) ? $theme->meta : [];
        $editor = is_array($meta['editor'] ?? null) ? $meta['editor'] : [];
        $preset = is_array($editor['preset'] ?? null) ? $editor['preset'] : [];
        $active = $preset['active'] ?? $meta['active_preset'] ?? null;

        return is_string($active) && $active !== '' ? $active : null;
    }

    /**
     * @param  array<string, mixed>  $sourceTokens
     * @param  array<string, mixed>  $targetTokens
     * @return list<string>
     */
    private function tokenConflicts(array $sourceTokens, array $targetTokens): array
    {
        $conflicts = [];

        foreach ($sourceTokens as $key => $value) {
            if (! array_key_exists($key, $targetTokens) || $targetTokens[$key] !== $value) {
                $conflicts[] = $key;
            }
        }

        sort($conflicts);

        return $conflicts;
    }

    /**
     * @return list<string>
     */
    private function notCarried(Theme $source, ?Theme $target): array
    {
        $notCarried = [];

        if (is_string($source->custom_css) && trim($source->custom_css) !== '') {
            $notCarried[] = 'custom_css';
        }

        foreach (self::MEDIA_COLLECTIONS as $collection) {
            if ($source->getMedia($collection)->isNotEmpty()) {
                $notCarried[] = 'media:' . $collection;
            }
        }

        $sourceMeta = is_array($source->meta) ? $source->meta : [];
        $targetMeta = $target instanceof Theme && is_array($target->meta) ? $target->meta : [];

        foreach ($sourceMeta as $key => $value) {
            $key = (string) $key;

            if (in_array($key, self::PRESET_META_KEYS, true) || $value === null || $value === [] || $value === '') {
                continue;
            }

            if (! array_key_exists($key, $targetMeta) || $targetMeta[$key] !== $value) {
                $notCarried[] = 'meta:' . $key;
            }
        }

        return $notCarried;
    }
}
