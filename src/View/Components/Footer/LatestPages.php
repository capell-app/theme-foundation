<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Footer;

use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class LatestPages extends Component
{
    /** @var Collection<int, mixed> */
    public Collection $pages;

    /**
     * @param  Collection<int, mixed>|null  $pages
     */
    public function __construct(public string $headingClass, public int $limit = 4, ?Collection $pages = null)
    {
        $language = Frontend::language();
        $candidateLimit = max($this->limit, $this->limit * 4);

        $this->pages = $this->visibleFooterPages($pages ?? ($language instanceof Language
            ? PageLoader::getPages(
                language: $language,
                site: Frontend::site(),
                limit: $candidateLimit,
                ordering: PageOrderEnum::Latest,
                pageGroup: BlueprintGroupEnum::Default,
            )
            : collect()))
            ->take($this->limit)
            ->values();
    }

    public function hasPages(): bool
    {
        return $this->pages->isNotEmpty();
    }

    public function render(): ViewContract
    {
        return view('capell::components.footer.latest-pages');
    }

    /**
     * @param  Collection<int, mixed>  $pages
     * @return Collection<int, mixed>
     */
    private function visibleFooterPages(Collection $pages): Collection
    {
        return $pages
            ->reject(fn (mixed $page): bool => data_get($page, 'translation.meta.exclude_from_footer') === true)
            ->values();
    }
}
