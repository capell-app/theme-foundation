<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Support\Security\PublicUrlSanitizer;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\FoundationTheme\Data\ResultsPaginationLinkData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveResultsPaginationLinksAction
{
    use AsFake;
    use AsObject;

    public function handle(ResultsPaginationData $pagination, string $baseUrl): ResultsPaginationData
    {
        $baseUrl = PublicUrlSanitizer::sanitize($baseUrl);
        if ($baseUrl === null || ($pagination->lastPage <= 1 && $pagination->currentPage <= 1)) {
            return $pagination;
        }

        // Navigation carries only its own GET parameter, never preview or session values.
        $baseUrl = explode('?', explode('#', $baseUrl, 2)[0], 2)[0];
        $url = static fn (int $number): string => $baseUrl . '?page=' . $number;
        $current = min($pagination->currentPage, $pagination->lastPage);
        $links = [new ResultsPaginationLinkData(
            __('capell-theme-foundation::generic.previous'),
            $pagination->currentPage > 1 ? $url(max(1, $current - 1)) : null,
            relation: 'prev',
        )];
        $numbers = array_values(array_unique([1, ...range(max(1, $current - 2), min($pagination->lastPage, $current + 2)), $pagination->lastPage]));
        $previous = 0;
        foreach ($numbers as $number) {
            if ($previous > 0 && $number > $previous + 1) {
                $links[] = new ResultsPaginationLinkData('…', null);
            }

            $links[] = new ResultsPaginationLinkData((string) $number, $url($number), $number === $pagination->currentPage);
            $previous = $number;
        }

        $links[] = new ResultsPaginationLinkData(
            __('capell-theme-foundation::generic.next'),
            $pagination->currentPage < $pagination->lastPage ? $url($current + 1) : null,
            relation: 'next',
        );

        return new ResultsPaginationData($pagination->currentPage, $pagination->perPage, $pagination->total, $pagination->lastPage, $links);
    }
}
