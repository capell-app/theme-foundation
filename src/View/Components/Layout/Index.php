<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Layout;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Actions\BuildLayoutNeighborLinksDataAction;
use Capell\FoundationTheme\Data\LayoutNeighborLinksData;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use RuntimeException;

final class Index extends Component
{
    public Layout $layout;

    public ?LayoutNeighborLinksData $layoutNeighborLinks;

    public Pageable $page;

    public Site $site;

    public Theme $theme;

    public bool $isSystemPageLayout;

    public function __construct()
    {
        $theme = Frontend::theme();
        $page = Frontend::page();
        $layout = Frontend::layout();
        $site = Frontend::site();
        $language = Frontend::language();

        throw_if(! $theme instanceof Theme || ! $page instanceof Pageable || ! $layout instanceof Layout || ! $site instanceof Site || ! $language instanceof Language, RuntimeException::class, 'Foundation layout rendering requires a complete frontend context.');

        $this->theme = $theme;
        $this->page = $page;
        $this->layout = $layout;
        $this->site = $site;
        $this->isSystemPageLayout = data_get($this->layout->admin ?? [], 'system_page_layout') === true;
        $this->layoutNeighborLinks = $this->isSystemPageLayout
            ? null
            : BuildLayoutNeighborLinksDataAction::run($this->page, $this->site, $language);
    }

    public function render(): View
    {
        return view('capell::components.layout.index');
    }
}
