<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\Database\SqlFragment;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Facades\CapellDatabase;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Data\SectionVariantScopeData;
use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static SectionVariantScopeData run(?Widget $widget) */
final class ResolveSectionVariantScopeAction
{
    use AsFake;
    use AsObject;

    public function handle(?Widget $widget): SectionVariantScopeData
    {
        if (! $widget instanceof Widget) {
            return new SectionVariantScopeData([], false);
        }

        $keys = [];
        $hasUses = false;
        // A reusable widget has no single theme. Include every tracked layout use,
        // including pages of a shared layout whose theme comes from their site.
        $layouts = Layout::query()->with(['theme', 'site.theme']);
        $grammar = $layouts->getQuery()->getGrammar();
        CapellDatabase::for($layouts->getModel())->queryDialect()->jsonExactSearch(
            SqlFragment::raw($grammar->wrap('layouts.containers')),
            SqlFragment::value($widget->key),
            '$.*.widgets[*].widget_key',
        )->applyWhere($layouts->getQuery());
        $layouts = $layouts->get();
        $layoutIds = $layouts->modelKeys();
        $consumedLayouts = [];
        $models = [];
        foreach (CapellCore::getPageTypes() as $type) {
            if ($type->model !== null && is_a($type->model, Pageable::class, true)) {
                $models[$type->model] = $type->model;
            }
        }

        // The public registry includes optional page types without coupling this
        // package to their models. Project each model once, even for shared types.
        foreach ($models as $model) {
            $consumer = new $model;
            // Keep asset owners in a subquery; a shared widget can have many thousands.
            $pageIds = $widget->widgetPageAssets()
                ->where('pageable_type', $consumer->getMorphClass())
                ->select('pageable_id')->getQuery();
            $pages = $model::query()
                ->where(static function (Builder $query) use ($layoutIds, $pageIds, $consumer): void {
                    $query->whereIn($consumer->qualifyColumn('layout_id'), $layoutIds)
                        ->orWhereIn($consumer->getQualifiedKeyName(), $pageIds);
                })
                // Scoped subqueries preserve soft-delete visibility without hydrating consumers.
                ->leftJoinSub(Layout::query()->select(['id', 'theme_id']), 'variant_layouts', 'variant_layouts.id', '=', $consumer->qualifyColumn('layout_id'))
                ->leftJoinSub(Site::query()->select(['id', 'theme_id']), 'variant_sites', 'variant_sites.id', '=', $consumer->qualifyColumn('site_id'))
                ->leftJoinSub(Theme::query()->select(['id', 'key']), 'variant_layout_themes', 'variant_layout_themes.id', '=', 'variant_layouts.theme_id')
                ->leftJoinSub(Theme::query()->select(['id', 'key']), 'variant_site_themes', 'variant_site_themes.id', '=', 'variant_sites.theme_id');
            $grammar = $pages->getQuery()->getGrammar();
            $themeKeyQuery = (clone $pages)->distinct()->toBase();
            (new SqlFragment(
                'COALESCE(' . $grammar->wrap('variant_layout_themes.key') . ', ' . $grammar->wrap('variant_site_themes.key') . ', ?) AS ' . $grammar->wrap('theme_key'),
                [$this->fallbackTheme()],
            ))->applySelect($themeKeyQuery);
            $themeKeys = $themeKeyQuery->pluck('theme_key');
            foreach ($themeKeys as $key) {
                $hasUses = true;
                $keys[] = is_string($key) ? $key : $this->fallbackTheme();
            }

            $usedLayoutIds = (clone $pages)->whereNotNull('variant_layouts.id')
                ->distinct()->toBase()->pluck('variant_layouts.id');
            foreach ($usedLayoutIds as $layoutId) {
                if (is_int($layoutId) || is_string($layoutId)) {
                    $consumedLayouts[$layoutId] = true;
                }
            }
        }

        foreach ($layouts as $layout) {
            $hasUses = true;
            // Unassigned layouts still have an authoring context; a consumed
            // layout instead follows each actual page's frontend theme order.
            if (! isset($consumedLayouts[$layout->id])) {
                $keys[] = LayoutContainerSchemaContextData::fromLayout($layout)->themeKey ?? $this->fallbackTheme();
            }
        }

        return new SectionVariantScopeData(array_values(array_unique($keys)), $hasUses);
    }

    private function fallbackTheme(): string
    {
        $key = config('capell-frontend.foundation_theme', 'default');

        return is_string($key) ? $key : '';
    }
}
