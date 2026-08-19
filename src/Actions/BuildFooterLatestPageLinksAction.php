<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\FooterLatestPageLinkData;
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
                )];
            })
            ->values();
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
