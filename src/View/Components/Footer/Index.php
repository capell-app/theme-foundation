<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Footer;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Support\NavigationAvailability;
use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Capell\Navigation\Support\NavigationFrontendRuntimeManifestContributor;
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
        $resolvedFrontendData = Frontend::getFrontendData();
        $frontendData = is_array($resolvedFrontendData) ? $resolvedFrontendData : [];
        $this->contactPage = array_key_exists('foundation.footer.contact_page', $frontendData)
            ? $frontendData['foundation.footer.contact_page']
            : null;
        $this->siteLanguages = ($frontendData['foundation.footer.site_languages'] ?? null) instanceof Collection
            ? $frontendData['foundation.footer.site_languages']
            : collect();
        $this->footerCopy = $site->translation?->getMeta('footer_copy');
        $this->footerSpacing = $theme->getMeta('footer_spacing', 'compact');
        $this->footerDividerColor = (bool) $theme->getMeta('footer_divider') ? $theme->getMeta('footer_border_color') : null;
        $this->latestFooterPages = ($frontendData['foundation.footer.latest_pages'] ?? null) instanceof Collection
            ? $frontendData['foundation.footer.latest_pages']
            : collect();
        $this->relatedSites = ($frontendData['foundation.footer.related_sites'] ?? null) instanceof Collection
            ? $frontendData['foundation.footer.related_sites']
            : collect();
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
        $preparedRenderModel = Frontend::getFrontendData(
            NavigationFrontendRuntimeManifestContributor::renderModelKey($key),
        );

        if ($preparedRenderModel instanceof NavigationRenderData) {
            return $preparedRenderModel->items;
        }

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
}
