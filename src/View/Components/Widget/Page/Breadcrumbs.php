<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\View\Components\Widget\AbstractWidget;
use Capell\Frontend\Actions\GetPageVariablesAction;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Override;
use Stringable;

class Breadcrumbs extends AbstractWidget
{
    protected static string $defaultView = 'capell-theme-foundation::components.widget.page.breadcrumbs';

    #[Override]
    public function render(array $data = []): View|string|Closure
    {
        $page = Frontend::page();
        $site = Frontend::site();
        $language = Frontend::language();

        $frontendData = Frontend::getFrontendData();
        $hasPreparedAncestors = is_array($frontendData) && array_key_exists('foundation.page.ancestors', $frontendData);
        $preparedAncestors = Frontend::getFrontendData('foundation.page.ancestors');

        if ($hasPreparedAncestors) {
            $ancestors = $preparedAncestors;
        } elseif (Frontend::getFrontendData('blog.article.render_data') !== null) {
            return '';
        } else {
            $ancestors = $page instanceof Page && $site instanceof Site && $language instanceof Language
                ? PageLoader::getPageAncestors($page, $language, $site)
                : null;
        }
        $pageTranslation = $page instanceof Page && $page->relationLoaded('translation') ? $page->translation : null;

        $currentPageLabel = $pageTranslation !== null
            ? __($pageTranslation->label, $this->translationVariables($page, $site))
            : '';

        $showCurrentPage = $page instanceof Page && ($page->url_params === null || Frontend::params() === []);
        $hasPreparedHome = is_array($frontendData) && array_key_exists('foundation.page.home', $frontendData);
        $preparedHome = Frontend::getFrontendData('foundation.page.home');
        $home = $hasPreparedHome
            ? $preparedHome
            : ($site instanceof Site && $language instanceof Language ? $site->getHomePage($language) : null);
        $homeTranslation = $home instanceof Page && $home->relationLoaded('translation') ? $home->translation : null;
        $siteDomain = $site instanceof Site && $site->relationLoaded('siteDomain') ? $site->siteDomain : null;
        $meta = is_array($this->widget->meta) ? $this->widget->meta : [];
        $showHome = $this->metaBoolean($meta, 'show_home', true);
        $showParent = $this->metaBoolean($meta, 'show_parent', true);
        $showCurrentPage = $showCurrentPage && $this->metaBoolean($meta, 'show_current_page', true);
        $minimumItems = max(1, $this->metaInteger($meta, 'minimum_items', 1));
        $ancestors = $this->visibleAncestors($ancestors instanceof Collection ? $ancestors : null, $showParent);
        $homeLabel = $showHome ? $homeTranslation?->label : null;
        $homeUrl = $showHome ? $siteDomain?->url : null;
        $visibleItemCount = ($homeUrl !== null && $homeLabel !== null ? 1 : 0)
            + $ancestors->count()
            + ($showCurrentPage ? 1 : 0);

        if ($visibleItemCount < $minimumItems) {
            return '';
        }

        return parent::render([
            ...$data,
            'ancestors' => $ancestors,
            'currentPageLabel' => $currentPageLabel,
            'homeLabel' => $homeLabel,
            'homeUrl' => $homeUrl,
            'page' => $page,
            'showCurrentPage' => $showCurrentPage,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function translationVariables(?Page $page, ?Site $site): array
    {
        $variables = GetPageVariablesAction::run($page, $site);

        return (new Collection(is_array($variables) ? $variables : []))
            ->filter(fn (mixed $value): bool => is_scalar($value) || $value instanceof Stringable)
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /**
     * @param  Collection<array-key, mixed>|null  $ancestors
     * @return Collection<int, Page>
     */
    private function visibleAncestors(?Collection $ancestors, bool $showParent): Collection
    {
        if (! $showParent || ! $ancestors instanceof Collection) {
            return new Collection;
        }

        return $ancestors
            ->filter(fn (mixed $ancestor): bool => $ancestor instanceof Page && ! (bool) ($ancestor->getAttributes()['home'] ?? false))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function metaBoolean(array $meta, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $meta)) {
            return $default;
        }

        return match (true) {
            is_bool($meta[$key]) => $meta[$key],
            is_int($meta[$key]) && $meta[$key] === 0 => false,
            is_int($meta[$key]) && $meta[$key] === 1 => true,
            is_string($meta[$key]) && in_array(strtolower(trim($meta[$key])), ['0', 'false', 'no', 'off'], true) => false,
            is_string($meta[$key]) && in_array(strtolower(trim($meta[$key])), ['1', 'true', 'yes', 'on'], true) => true,
            default => $default,
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function metaInteger(array $meta, string $key, int $default): int
    {
        $value = $meta[$key] ?? null;

        return is_int($value) ? $value : $default;
    }
}
