<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\FoundationPreparedPageData;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

it('transfers only prepared page data between distinct matching stores', function (string $value): void {
    $state = foundationPreparedPageState();
    $state->setFrontendData('unrelated', 'retained');
    $snapshot = $state->snapshot();
    $snapshot->params['route-only'] = 'not frontend data';
    $contact = $value === 'populated' ? (new Page)->forceFill(['id' => 9]) : null;
    $ancestors = match ($value) {
        'populated' => new Collection([(new Page)->forceFill(['id' => 8])]),
        'empty' => new Collection,
        default => null,
    };
    $snapshot->setFrontendData('foundation.footer.contact_page', $contact);
    $snapshot->setFrontendData('foundation.page.ancestors', $ancestors);
    $before = $snapshot->params;

    $prepared = FoundationPreparedPageData::fromContext($snapshot);
    expect($prepared)->toBeInstanceOf(FoundationPreparedPageData::class);
    $prepared?->transferTo($state);

    $frontendData = $state->getFrontendData();
    expect(array_keys(is_array($frontendData) ? $frontendData : []))->toBe(['unrelated', 'foundation.footer.contact_page', 'foundation.page.ancestors']);
    expect($state->getFrontendData('unrelated'))->toBe('retained');
    expect($state->getFrontendData('foundation.footer.contact_page'))->toBe($contact);
    expect($state->getFrontendData('foundation.page.ancestors'))->toEqual($ancestors);
    expect($snapshot->params)->toBe($before);
    expect($state->params())->toBe([]);
})->with(['null', 'empty', 'populated'])->group('foundation-prepared-handoff');

it('does not turn absent snapshot values into prepared nulls', function (): void {
    $state = foundationPreparedPageState();
    $snapshot = $state->snapshot();
    $snapshot->setFrontendData('foundation.footer.contact_page', null);

    $prepared = FoundationPreparedPageData::fromContext($snapshot);
    expect($prepared?->contactPrepared)->toBeTrue();
    expect($prepared?->ancestorsPrepared)->toBeFalse();
    $prepared?->transferTo($state);

    expect($state->getFrontendData())->toBe(['foundation.footer.contact_page' => null]);
})->group('foundation-prepared-handoff');

it('rejects prepared data for a different model identity or connection', function (string $part, bool $differentConnection): void {
    $state = foundationPreparedPageState();
    $snapshot = $state->snapshot();
    $snapshot->setFrontendData('foundation.footer.contact_page', null);
    $snapshot->setFrontendData('foundation.page.ancestors', new Collection);
    $prepared = FoundationPreparedPageData::fromContext($snapshot);
    $source = match ($part) {
        'page' => $snapshot->page,
        'site' => $snapshot->site,
        'language' => $snapshot->language,
        default => throw new InvalidArgumentException('Unknown snapshot part.'),
    };

    if (! $source instanceof Model) {
        throw new RuntimeException('Expected a model snapshot part.');
    }

    $other = clone $source;

    if ($differentConnection) {
        $defaultConnection = config('database.default');
        config(['database.connections.foundation_handoff_other' => config('database.connections.' . (is_string($defaultConnection) ? $defaultConnection : 'sqlite'))]);
        $other->setConnection('foundation_handoff_other');
    } else {
        $other->setAttribute('id', 99);
    }

    if ($other instanceof Page) {
        $state->withPage($other);
    } elseif ($other instanceof Site) {
        $state->withSite($other);
    } elseif ($other instanceof Language) {
        $state->withLanguage($other);
    }
    $prepared?->transferTo($state);

    expect($state->getFrontendData())->toBe([]);
})->with([
    'page' => ['page', false],
    'site' => ['site', false],
    'language' => ['language', false],
    'page connection' => ['page', true],
    'site connection' => ['site', true],
    'language connection' => ['language', true],
])->group('foundation-prepared-handoff');

function foundationPreparedPageState(): FrontendState
{
    return (new FrontendState)
        ->withPage((new Page)->forceFill(['id' => 1]))
        ->withSite((new Site)->forceFill(['id' => 2]))
        ->withLanguage((new Language)->forceFill(['id' => 3]));
}
