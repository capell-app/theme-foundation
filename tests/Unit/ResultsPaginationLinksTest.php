<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveResultsPaginationLinksAction;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\Tests\Support\RenderedHtml;

it('renders bounded real GET navigation without propagating sensitive query values', function (string $variant): void {
    $pagination = ResolveResultsPaginationLinksAction::run(
        new ResultsPaginationData(500, 6, 6000, 1000),
        '/directory?preview=opaque&token=secret#fragment',
    );
    $section = (object) [
        'heading' => 'Directory', 'summary' => null, 'variant' => null, 'items' => [],
        'resolvedResults' => ['items' => [], 'pagination' => $pagination->toArray()],
    ];
    /** @var view-string $view */
    $view = implode('', ['capell-theme-foundation::theme.sections.content-listing', $variant]);
    $html = view($view, ['section' => $section])->render();
    $document = new RenderedHtml($html);

    expect(count($pagination->links))->toBeLessThanOrEqual(11)
        ->and($document->count('//nav//a[@href="/directory?page=499" and @rel="prev"]'))->toBe(1)
        ->and($document->count('//nav//a[@href="/directory?page=501" and @rel="next"]'))->toBe(1)
        ->and($document->count('//nav//a[@href="/directory?page=500" and @aria-current="page"]'))->toBe(1)
        ->and($html)->not->toContain('opaque', 'secret', 'wire:click');
})->with(['default' => '', 'grid' => '--grid', 'rows' => '--rows', 'masonry' => '--masonry-safe']);

it('omits navigation when all results fit on one page', function (): void {
    $pagination = ResolveResultsPaginationLinksAction::run(new ResultsPaginationData(1, 6, 4, 1), '/directory');

    expect($pagination->links)->toBe([]);
});

it('offers a real recovery link for an out of range request even with a single result page', function (): void {
    $pagination = ResolveResultsPaginationLinksAction::run(new ResultsPaginationData(PHP_INT_MAX, 6, 1, 1), '/directory');

    expect(array_column($pagination->links, 'url'))->toContain('/directory?page=1')
        ->and(array_filter($pagination->links, static fn ($link): bool => $link->current))->toBe([]);
});
