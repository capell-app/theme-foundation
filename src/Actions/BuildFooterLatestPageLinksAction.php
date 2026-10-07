<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Page;
use Capell\FoundationTheme\Data\FooterLatestPageLinkData;
use Capell\Frontend\Facades\Frontend;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildFooterLatestPageLinksAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  Collection<int, mixed>  $pages
     * @return Collection<int, FooterLatestPageLinkData>
     */
    public function handle(Collection $pages): Collection
    {
        return $pages
            ->flatMap(function (mixed $page): array {
                $url = $this->publicPageUrl($page);

                return $url === null ? [] : [new FooterLatestPageLinkData(
                    page: $page,
                    url: $url,
                    label: $this->pageLabel($page),
                )];
            })
            ->values();
    }

    private function pageLabel(mixed $page): string
    {
        if ($page instanceof Page) {
            return NavigationCreator::getPageNavigationLabel($page, Frontend::language());
        }

        $label = is_object($page) && method_exists($page, 'getTranslation')
            ? ($page->getTranslation('label') ?? $page->getTranslation('title'))
            : null;

        if (is_string($label) && trim($label) !== '') {
            return trim($label);
        }

        $name = data_get($page, 'name', '');

        return is_string($name) ? $name : '';
    }

    private function publicPageUrl(mixed $page): ?string
    {
        $pageUrl = $page instanceof Model
            ? ($page->relationLoaded('pageUrl') ? $page->getRelation('pageUrl') : null)
            : data_get($page, 'pageUrl');

        $url = data_get($pageUrl, 'full_url');

        return is_string($url) && $url !== '' ? $url : null;
    }
}
