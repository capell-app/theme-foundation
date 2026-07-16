<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\LayoutBuilder\Actions\ApplyLayoutSidebarWidgetContributionsAction;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array{created: int, updated: int, skipped: int} run(bool $force = false)
 */
final class InstallFoundationThemeLayoutDefaultsAction
{
    use AsFake;
    use AsObject;

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function handle(bool $force = false): array
    {
        LayoutModelRegistrar::register();

        $existingLayoutKeys = Layout::query()
            ->whereIn('key', array_keys($this->layoutDefaults()))
            ->pluck('key')
            ->all();
        $existingContainersByLayoutKey = Layout::query()
            ->whereIn('key', array_keys($this->layoutDefaults()))
            ->get(['key', 'containers'])
            ->mapWithKeys(fn (Layout $layout): array => [$layout->key => $layout->containers])
            ->all();

        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->createHomeLayout();
        $layoutCreator->createDefaultLayout();

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->childrenWidget();
        $widgetCreator->latestPagesWidget();
        $widgetCreator->pageContentWidget();
        $widgetCreator->siblingsWidget();

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->layoutDefaults() as $layoutKey => $containers) {
            $layout = $this->resolveLayout($layoutKey);
            $existingContainers = $existingContainersByLayoutKey[$layoutKey] ?? null;
            $hadContainers = is_array($existingContainers) && $existingContainers !== [];
            $existedBeforeInstall = in_array($layoutKey, $existingLayoutKeys, true);

            if ($hadContainers && $existedBeforeInstall && ! $force) {
                $layout->forceFill(['containers' => $existingContainers])->save();

                if ($this->ensurePageContentWidget($layout)) {
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }

                continue;
            }

            $layout->update([
                'containers' => $containers,
            ]);

            ApplyLayoutSidebarWidgetContributionsAction::run($layout);

            $result[$existedBeforeInstall ? 'updated' : 'created']++;
        }

        $result['updated'] += $this->ensureAdditionalPageLayoutsHavePageContent(array_keys($this->layoutDefaults()));

        return $result;
    }

    private function resolveLayout(string $layoutKey): Layout
    {
        return Layout::query()->where('key', $layoutKey)->firstOrFail();
    }

    /**
     * @param  list<string>  $managedLayoutKeys
     */
    private function ensureAdditionalPageLayoutsHavePageContent(array $managedLayoutKeys): int
    {
        return Layout::query()
            ->whereNotIn('key', $managedLayoutKeys)
            ->where(function (Builder $query): void {
                $query->whereNull('group')
                    ->orWhere('group', '!=', LayoutGroupEnum::System->value);
            })
            ->get()
            ->filter(fn (Layout $layout): bool => $this->ensurePageContentWidget($layout))
            ->count();
    }

    private function ensurePageContentWidget(Layout $layout): bool
    {
        if (in_array('page-content', $layout->widgets, true)) {
            return false;
        }

        $containers = is_array($layout->containers) ? $layout->containers : [];

        if (! is_array($containers['main'] ?? null)) {
            $layout->update([
                'containers' => $this->insertMainContainerAfterHero($containers),
            ]);

            return true;
        }

        $main = $containers['main'];
        $widgets = is_array($main['widgets'] ?? null) ? $main['widgets'] : [];
        $heroWidgetIndex = $this->lastHeroWidgetIndex($widgets);

        if ($heroWidgetIndex === null) {
            array_unshift($widgets, ['widget_key' => 'page-content']);
        } else {
            array_splice($widgets, $heroWidgetIndex + 1, 0, [['widget_key' => 'page-content']]);
        }

        $main['widgets'] = array_values($widgets);
        $containers['main'] = $main;

        $layout->update([
            'containers' => $this->moveMainContainerAfterHero($containers),
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<string, mixed>
     */
    private function insertMainContainerAfterHero(array $containers): array
    {
        $mainContainer = $this->mainContainer([
            ['widget_key' => 'page-content'],
        ], 12);

        if ($containers === []) {
            return ['main' => $mainContainer];
        }

        if (! array_key_exists('hero', $containers)) {
            return [
                ...$containers,
                'main' => $mainContainer,
            ];
        }

        $updated = [];

        foreach ($containers as $key => $container) {
            $updated[$key] = $container;

            if ($key === 'hero') {
                $updated['main'] = $mainContainer;
            }
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<string, mixed>
     */
    private function moveMainContainerAfterHero(array $containers): array
    {
        $keys = array_keys($containers);
        $heroIndex = array_search('hero', $keys, true);
        $mainIndex = array_search('main', $keys, true);

        if ($heroIndex === false || $mainIndex === false || $mainIndex > $heroIndex) {
            return $containers;
        }

        $main = $containers['main'];
        unset($containers['main']);

        $updated = [];

        foreach ($containers as $key => $container) {
            $updated[$key] = $container;

            if ($key === 'hero') {
                $updated['main'] = $main;
            }
        }

        return $updated;
    }

    /**
     * @param  array<int, mixed>  $widgets
     */
    private function lastHeroWidgetIndex(array $widgets): ?int
    {
        $heroWidgetIndex = null;

        foreach ($widgets as $index => $widget) {
            $widgetKey = is_array($widget) ? ($widget['widget_key'] ?? null) : $widget;

            if ($widgetKey === 'hero') {
                $heroWidgetIndex = $index;
            }
        }

        return $heroWidgetIndex;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function layoutDefaults(): array
    {
        return [
            LayoutEnum::Home->value => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'page-content'],
                ], 12),
            ],
            LayoutEnum::Default->value => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'children'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'siblings'],
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $widgets
     * @return array<string, mixed>
     */
    private function sidebarContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => ContainerWidthEnum::Full,
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $widgets
     * @return array<string, mixed>
     */
    private function mainContainer(array $widgets, int $colspan = 9): array
    {
        return [
            'meta' => [
                'colspan' => $colspan,
            ],
            'widgets' => $widgets,
        ];
    }
}
