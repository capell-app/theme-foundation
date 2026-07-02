<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Footer;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Support\NavigationAvailability;
use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

final class Index extends Component
{
    public mixed $contactPage;

    public mixed $containerWidth;

    public mixed $footerCopy;

    public ?string $footerDividerColor;

    public bool $hasFooterMenu;

    public bool $hasFooterPrimaryContent;

    public bool $hasLatestFooterPages;

    public string $footerRenderHooks;

    public mixed $footerSpacing;

    /**
     * @var Collection<array-key, mixed>
     */
    public Collection $latestFooterPages;

    /**
     * @var Collection<array-key, array{description: mixed, primaryColor: mixed, title: mixed, url: mixed}>
     */
    public Collection $relatedSites;

    public mixed $site;

    public mixed $siteLanguages;

    public mixed $subFooterMenuItems;

    public mixed $theme;

    public mixed $footerMenuItems;

    public function __construct(
        public string $headingClass = 'font-heading text-sm font-semibold uppercase leading-tight tracking-[0.08em] text-[var(--color-footer-heading)]',
    ) {
        $language = Frontend::language();
        $site = Frontend::site();
        $page = Frontend::page();
        $theme = Frontend::theme();
        $this->site = $site;
        $this->theme = $theme;
        $navigationAvailable = NavigationAvailability::check();

        $this->containerWidth = GetLayoutContainerWidthAction::run();
        $this->footerRenderHooks = resolve(RenderHookRegistry::class)->renderAll(
            RenderHookLocation::Footer,
            item: ['headingClass' => $this->headingClass],
            target: 'footer.index',
        );

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Pageable || ! $theme instanceof Theme) {
            $this->footerMenuItems = null;
            $this->subFooterMenuItems = null;
            $this->contactPage = null;
            $this->siteLanguages = collect();
            $this->footerCopy = null;
            $this->footerSpacing = 'compact';
            $this->footerDividerColor = null;
            $this->latestFooterPages = collect();
            $this->relatedSites = collect();
            $this->hasFooterMenu = false;
            $this->hasLatestFooterPages = false;
            $this->hasFooterPrimaryContent = trim($this->footerRenderHooks) !== '';

            return;
        }

        $this->footerMenuItems = $navigationAvailable
            ? $this->menuItems(NavigationHandle::Footer->value, $site, $language)
            : null;
        $this->subFooterMenuItems = $navigationAvailable
            ? $this->menuItems(NavigationHandle::SubFooter->value, $site, $language)
            : null;
        $frontendData = Frontend::getFrontendData();
        $hasPreparedContactPage = is_array($frontendData) && array_key_exists('foundation.footer.contact_page', $frontendData);
        $preparedContactPage = Frontend::getFrontendData('foundation.footer.contact_page');
        $preparedSiteLanguages = Frontend::getFrontendData('foundation.footer.site_languages');
        $preparedLatestFooterPages = Frontend::getFrontendData('foundation.footer.latest_pages');
        $preparedRelatedSites = Frontend::getFrontendData('foundation.footer.related_sites');

        $this->contactPage = $hasPreparedContactPage
            ? $preparedContactPage
            : Page::getFirstPageByTypeForSite('contact', $site, $language);
        $this->siteLanguages = $preparedSiteLanguages instanceof Collection
            ? $preparedSiteLanguages
            : SiteLoader::pageLanguages($site, $language, $page);
        $this->footerCopy = $site->translation?->getMeta('footer_copy');
        $this->footerSpacing = $theme->getMeta('footer_spacing', 'compact');
        $this->footerDividerColor = (bool) $theme->getMeta('footer_divider') ? $theme->getMeta('footer_border_color') : null;
        $this->latestFooterPages = $preparedLatestFooterPages instanceof Collection
            ? $preparedLatestFooterPages
            : PageLoader::getPages(
                language: $language,
                site: $site,
                limit: 4,
                ordering: PageOrderEnum::Latest,
                pageGroup: BlueprintGroupEnum::Default,
            );
        $this->relatedSites = $preparedRelatedSites instanceof Collection
            ? $preparedRelatedSites
            : $this->relatedSites($site, $language);
        $this->hasFooterMenu = $this->footerMenuItems?->isNotEmpty() === true;
        $this->hasLatestFooterPages = ! $this->hasFooterMenu && $this->latestFooterPages->isNotEmpty();
        $hasFooterRenderHooks = trim($this->footerRenderHooks) !== '';
        $this->hasFooterPrimaryContent = $this->hasFooterMenu || $this->hasLatestFooterPages || $hasFooterRenderHooks;
    }

    public function render(): View
    {
        return view('capell::components.footer.index');
    }

    private function menuItems(string $key, Site $site, Language $language): mixed
    {
        $menu = NavigationLoader::getNavigation($key, $site, $language);

        $page = Frontend::page();
        $siteDomain = $site->siteDomain;

        if (! $menu instanceof Navigation || ! $page instanceof Pageable || ! $page instanceof Model || ! $siteDomain instanceof SiteDomain) {
            return null;
        }

        return BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $menu,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $siteDomain,
        ))->items;
    }

    /**
     * @return Collection<int, array{description: mixed, primaryColor: mixed, title: mixed, url: mixed}>
     */
    private function relatedSites(Site $site, Language $language): Collection
    {
        return SiteLoader::related($site, $language)
            ->map(function (Site $relatedSite): array {
                $relations = $relatedSite->getRelations();
                $siteDomain = $relations['siteDomain'] ?? null;
                $translation = $relations['translation'] ?? null;

                return [
                    'description' => data_get($translation, 'meta.description'),
                    'primaryColor' => $relatedSite->getThemeColor('primary'),
                    'title' => data_get($translation, 'title'),
                    'url' => data_get($siteDomain, 'full_url'),
                ];
            })
            ->filter(fn (array $relatedSite): bool => is_string($relatedSite['url']) && $relatedSite['url'] !== '')
            ->values();
    }
}
