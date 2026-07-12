<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Layout;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Actions\BuildPageContentRenderDataAction;
use Capell\FoundationTheme\Data\LayoutNeighborLinksData;
use Capell\FoundationTheme\Data\PageContentRenderData;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use RuntimeException;

final class Main extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $theme;

    public Theme $themeModel;

    public PageContentRenderData $pageContentRenderData;

    public ?Pageable $previousPage;

    public ?Pageable $nextPage;

    public mixed $finalCta;

    public function __construct(
        public Layout $layout,
        public Pageable $page,
        mixed $theme = [],
        public ?LayoutNeighborLinksData $layoutNeighborLinks = null,
        public ?string $containerClass = null,
        public ?string $mainClass = null,
        public ?string $mainContainerClass = null,
        public mixed $pageSlot = null,
    ) {
        $this->theme = is_array($theme) ? $theme : [];
        $themeModel = Frontend::theme();

        throw_unless($themeModel instanceof Theme, RuntimeException::class, 'Foundation layout main rendering requires a frontend theme.');

        $this->themeModel = $themeModel;
        $this->previousPage = $this->layoutNeighborLinks?->previousPage;
        $this->nextPage = $this->layoutNeighborLinks?->nextPage;
        $this->finalCta = $this->page->getMeta('final_cta');
        $this->pageContentRenderData = BuildPageContentRenderDataAction::run(
            page: $this->page,
            pageContents: ['title', 'content'],
            showPageTitle: true,
        );
    }

    public function render(): View
    {
        return view('capell::components.layout.main');
    }
}
