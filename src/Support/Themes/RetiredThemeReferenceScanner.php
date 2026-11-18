<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

use Capell\Core\Data\Database\SqlFragment;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\TableName;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Counts every remaining reference to a retired theme. It is the safety net
 * behind `capell:theme-migrate`: a retired theme's package is safe to remove
 * only when every blocking location reads zero.
 *
 * {@see self::removalVerdict()} decides whether the package can go. It comes
 * only from a raw search of every text and JSON column of every table of
 * this application (history, log and cache tables excepted) for the theme's
 * renderable prefix, and of every theme id column for any of its records,
 * plus the settings that name the theme. The search uses the query builder,
 * so no Eloquent global scope (soft deletes, Publishing Studio workspaces)
 * hides a row from it.
 *
 * {@see self::scan()} names the known locations those references sit in. It
 * is cheap enough for the doctor check and explains a verdict, but never
 * decides one. It also reads every row, drafts and soft-deleted rows
 * included.
 */
final class RetiredThemeReferenceScanner
{
    /**
     * Columns whose name marks them as a theme reference (theme, theme_id,
     * theme_key, component, component_item or *_component) and how a retired
     * theme migration treats each. The registry is matched by column name only
     * against the packages test schema; references hiding in other columns are
     * found at run time by {@see self::schemaWide()}.
     *
     * - moved: the migration moves it and this scanner counts it;
     * - scanned: not moved, but a remaining reference blocks the migration and
     *   package removal;
     * - revoked: cached output the migration revokes after commit;
     * - history: an audit or history row that records what was rendered then;
     * - label: a descriptive key that is never resolved to a theme.
     *
     * @var array<string, array<string, 'moved'|'scanned'|'revoked'|'history'|'label'>>
     */
    public const array SCHEMA_LOCATIONS = [
        'sites' => ['theme_id' => 'moved'],
        'layouts' => ['theme_id' => 'moved'],
        'widgets' => ['component' => 'moved', 'component_item' => 'scanned'],
        'blueprints' => ['component' => 'scanned', 'component_item' => 'scanned'],
        'public_widget_snapshots' => ['theme_id' => 'revoked'],
        'layout_content_snapshots' => ['theme_id' => 'history'],
        'capell_public_render_contract_events' => ['theme_id' => 'history'],
        'layout_presets' => ['theme_key' => 'label'],
    ];

    /**
     * Settings stored under a theme key inside another record. Each is read
     * only by a reader that theme registers, so a retired theme's entry is
     * inert once its package is gone and is left in place.
     *
     * Layout container theme settings are read by the theme's
     * LayoutContainerThemePresentationProjector; only Foundation (key
     * `default`) registers one.
     *
     * @var array<string, 'label'>
     */
    public const array KEYED_SETTINGS = [
        'layouts.containers.*.meta.theme_settings' => 'label',
    ];

    /**
     * Locations whose remaining references make the retired package unsafe to
     * remove.
     *
     * @var list<string>
     */
    public const array BLOCKING = [
        'sites.theme_id',
        'layouts.theme_id',
        'layouts.containers',
        'widgets.component',
        'widgets.meta.component',
        'widgets.component_item',
        'blueprints.component',
        'blueprints.meta.component',
        'blueprints.meta.component_item',
        'blueprints.component_item',
        'widgets.meta.component_item',
        'theme_studio.active_theme',
        'config.capell-frontend.foundation_theme',
    ];

    /**
     * Tables the schema-wide search skips, as names or `fnmatch` patterns: they
     * record what happened, measure it or cache what was rendered, and never
     * resolve a theme for a new render.
     *
     * @var list<string>
     */
    public const array UNSCANNED_TABLES = [
        '*_audit_*',
        '*_events',
        'activity_log',
        'cache',
        'cache_locks',
        'email_events',
        'html_cache_request_observations',
        'import_rollback_reports',
        'page_speed_audit_results',
        'publishing_revisions',
        'search_console_*_metrics',
        'capell_public_render_contract_events',
        'failed_jobs',
        'job_batches',
        'jobs',
        'layout_content_snapshots',
        'migrations',
        'public_widget_snapshots',
        'sessions',
        'snapshots',
        'stored_events',
    ];

    /**
     * Blocking locations held in settings or configuration rather than in a
     * table row, so the schema-wide search cannot see them.
     *
     * @var list<string>
     */
    public const array SETTING_LOCATIONS = [
        'theme_studio.active_theme',
        'config.capell-frontend.foundation_theme',
    ];

    /**
     * Themes whose widget renderables are not named after the theme key.
     *
     * @var array<string, string> theme key => renderable prefix
     */
    private const array RENDERABLE_PREFIXES = [
        'knowledge' => 'capell.widget.docs.',
    ];

    public static function renderablePrefix(string $themeKey): string
    {
        return self::RENDERABLE_PREFIXES[$themeKey] ?? 'capell.widget.' . $themeKey . '.';
    }

    /**
     * Escapes LIKE wildcards with `!`, which every supported database accepts
     * as an ESCAPE character; a theme key may contain `_`.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function whereStartsWith(Builder $query, string $column, string $prefix): Builder
    {
        return self::whereLike($query, $column, self::escapeLike($prefix) . '%');
    }

    /**
     * Adds a LIKE condition whose pattern escapes wildcards with `!`.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function whereLike(Builder $query, string $column, string $pattern, string $boolean = 'and'): Builder
    {
        $base = $query->getQuery();
        new SqlFragment(self::likeTarget($base->getGrammar(), $column) . " LIKE ? ESCAPE '!'", [$pattern])->applyWhere($base, $boolean);

        return $query;
    }

    /**
     * Ids of every record of the theme, read without any global scope:
     * soft-deleted rows and Publishing Studio workspace drafts included. An
     * empty list means no record of the theme exists, so no theme id column
     * can refer to it.
     *
     * @return list<int>
     */
    public static function themeIds(string $themeKey): array
    {
        $table = new Theme()->getTable();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $ids = [];

        foreach (DB::table($table)->where('key', $themeKey)->orderBy('id')->pluck('id') as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * Counts the known locations, every row included (soft-deleted rows and
     * Publishing Studio workspace drafts too). It explains a verdict; only
     * {@see self::removalVerdict()} decides one.
     *
     * @return array<string, int> location => remaining references
     */
    public function scan(string $themeKey): array
    {
        $prefix = self::renderablePrefix($themeKey);
        $themeIds = self::themeIds($themeKey);

        return [
            'sites.theme_id' => $this->count(Site::query()->withoutGlobalScopes()->whereIn('theme_id', $themeIds)),
            'layouts.theme_id' => $this->count(Layout::query()->withoutGlobalScopes()->whereIn('theme_id', $themeIds)),
            'layouts.containers' => $this->count(self::whereContains(Layout::query()->withoutGlobalScopes(), 'containers', $prefix)),
            'widgets.component' => $this->count(self::whereStartsWith(Widget::query()->withoutGlobalScopes(), 'component', $prefix)),
            'widgets.meta.component' => $this->count(self::whereStartsWith(Widget::query()->withoutGlobalScopes(), 'meta->component', $prefix)),
            'widgets.component_item' => $this->count(self::whereStartsWith(Widget::query()->withoutGlobalScopes(), 'component_item', $prefix)),
            'blueprints.component' => $this->count(self::whereStartsWith(Blueprint::query()->withoutGlobalScopes(), 'component', $prefix)),
            'blueprints.meta.component' => $this->count(self::whereStartsWith(Blueprint::query()->withoutGlobalScopes(), 'meta->component', $prefix)),
            'blueprints.component_item' => $this->count(self::whereStartsWith(Blueprint::query()->withoutGlobalScopes(), 'component_item', $prefix)),
            'blueprints.meta.component_item' => $this->count(self::whereStartsWith(Blueprint::query()->withoutGlobalScopes(), 'meta->component_item', $prefix)),
            'widgets.meta.component_item' => $this->count(self::whereStartsWith(Widget::query()->withoutGlobalScopes(), 'meta->component_item', $prefix)),
            ...$this->settings($themeKey),
        ];
    }

    /**
     * Every place that still refers to the theme: each table column the raw
     * schema-wide search finds, plus the settings that name it. Empty means
     * its package is safe to remove on this installation.
     *
     * @return array<string, int>
     */
    public function removalVerdict(string $themeKey): array
    {
        return [
            ...array_filter($this->settings($themeKey), static fn (int $count): bool => $count > 0),
            ...$this->schemaWide($themeKey),
        ];
    }

    /**
     * Searches every text and JSON column for the theme's renderable prefix and
     * every theme id column for any of its records, outside the unscanned
     * tables. It reads rows through the query builder, so every row counts.
     *
     * Only tables carrying this connection's table prefix are read, so another
     * application's tables in a shared database are left alone. Each table is
     * read once, with one conditional count per column.
     *
     * @return array<string, int> "schema:table.column" => matching rows
     */
    public function schemaWide(string $themeKey): array
    {
        $connection = DB::connection();
        $tablePrefix = $connection->getTablePrefix();
        $needle = '%' . self::escapeLike(self::renderablePrefix($themeKey)) . '%';
        $themeIds = self::themeIds($themeKey);
        $found = [];

        foreach ($connection->getSchemaBuilder()->getTables($connection->getSchemaBuilder()->getCurrentSchemaListing()) as $table) {
            if ($tablePrefix !== '' && ! str_starts_with($table['name'], $tablePrefix)) {
                continue;
            }

            $name = substr($table['name'], strlen($tablePrefix));

            if (self::isUnscanned($name)) {
                continue;
            }

            $grammar = $connection->getQueryGrammar();
            $selects = [];
            $bindings = [];
            $columns = [];
            $themeForeignKeys = self::themeForeignKeyColumns($name, $tablePrefix);

            foreach ($connection->getSchemaBuilder()->getColumns($name) as $column) {
                if ($column['name'] === 'theme_id' || in_array($column['name'], $themeForeignKeys, true)) {
                    // No record of the theme exists, so nothing can refer to one.
                    if ($themeIds === []) {
                        continue;
                    }

                    $selects[] = sprintf('SUM(CASE WHEN %s IN (%s) THEN 1 ELSE 0 END)', $grammar->wrap($column['name']), implode(', ', array_fill(0, count($themeIds), '?')));
                    $bindings = [...$bindings, ...$themeIds];
                } elseif (self::isTextColumn($column['type_name'])) {
                    $selects[] = sprintf("SUM(CASE WHEN %s LIKE ? ESCAPE '!' THEN 1 ELSE 0 END)", self::likeTarget($grammar, $column['name']));
                    $bindings[] = $needle;
                } else {
                    continue;
                }

                $columns[] = $column['name'];
            }

            if ($columns === []) {
                continue;
            }

            $aliased = [];

            foreach ($selects as $index => $select) {
                $aliased[] = $select . ' as ' . $grammar->wrap('reference_count_' . $index);
            }

            $query = $connection->table($name);
            new SqlFragment(implode(', ', $aliased), array_values($bindings))->applySelect($query);
            $row = (array) $query->first();

            foreach ($columns as $index => $column) {
                $count = $row['reference_count_' . $index] ?? 0;

                if (is_numeric($count) && (int) $count > 0) {
                    $found['schema:' . $name . '.' . $column] = (int) $count;
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * @param  array<string, int>  $scan
     * @return array<string, int> blocking locations that still hold references
     */
    public function blocking(array $scan): array
    {
        return array_filter(
            array_intersect_key($scan, array_flip(self::BLOCKING)),
            static fn (int $count): bool => $count > 0,
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private static function whereContains(Builder $query, string $column, string $value): Builder
    {
        return self::whereLike($query, $column, '%' . self::escapeLike($value) . '%');
    }

    private static function isUnscanned(string $table): bool
    {
        foreach (self::UNSCANNED_TABLES as $pattern) {
            $matches = str_contains($pattern, '*')
                ? fnmatch($pattern, $table)
                // Framework tables (cache, jobs, sessions, migrations) are
                // created without the Capell table prefix.
                : $table === $pattern || $table === TableName::resolve($pattern);

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * The column expression a LIKE search reads. PostgreSQL has no LIKE
     * operator for json or jsonb, so a whole column is cast to text there; a
     * JSON path (`meta->component`) already reads as text.
     */
    private static function likeTarget(Grammar $grammar, string $column): string
    {
        $wrapped = $grammar->wrap($column);

        return $grammar instanceof PostgresGrammar && ! str_contains($column, '->')
            ? 'CAST(' . $wrapped . ' AS TEXT)'
            : $wrapped;
    }

    /**
     * Columns of the table whose foreign key points at the themes table. A
     * column only counts as a theme id when it is `theme_id` or one of these:
     * another table's `*_theme_id` (an email template theme, say) holds ids
     * of a different table that can equal a theme's.
     *
     * @return list<string>
     */
    private static function themeForeignKeyColumns(string $table, string $tablePrefix): array
    {
        $themes = new Theme()->getTable();
        $columns = [];

        foreach (DB::connection()->getSchemaBuilder()->getForeignKeys($table) as $foreignKey) {
            if (in_array($foreignKey['foreign_table'], [$themes, $tablePrefix . $themes], true) && count($foreignKey['columns']) === 1) {
                $columns[] = $foreignKey['columns'][0];
            }
        }

        return $columns;
    }

    private static function isTextColumn(string $type): bool
    {
        $type = strtolower($type);

        return str_contains($type, 'char') || str_contains($type, 'text') || str_contains($type, 'json') || str_contains($type, 'clob');
    }

    /**
     * @return array<string, int> setting location => 1 when it names the theme
     */
    private function settings(string $themeKey): array
    {
        $activeTheme = resolve(ThemeStudioActiveTheme::class)->current();

        return [
            'theme_studio.active_theme' => ($activeTheme['theme'] ?? null) === $themeKey ? 1 : 0,
            'config.capell-frontend.foundation_theme' => config('capell-frontend.foundation_theme') === $themeKey ? 1 : 0,
        ];
    }

    /**
     * @param  Builder<covariant Model>  $query
     */
    private function count(Builder $query): int
    {
        return Schema::hasTable($query->getModel()->getTable()) ? $query->count() : 0;
    }
}
