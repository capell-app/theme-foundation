<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Support\Demo\FoundationDemoContent;
use Capell\FoundationTheme\Support\SearchSwitch;

it('has no opinion when the Search package is not installed', function (): void {
    CapellCore::shouldReceive('isPackageAvailable')->with('capell-app/search')->andReturn(false);
    $switch = new SearchSwitch;

    expect($switch->enabled())->toBeTrue()
        ->and($switch->blocksAction('/search'))->toBeFalse()
        ->and($switch->resultsUrl())->toBe('/search');
});

it('follows the Search package when it is installed', function (): void {
    config(['capell-search.enabled' => false, 'capell-search.route_path' => 'find']);
    app()->forgetScopedInstances();
    $switch = resolve(SearchSwitch::class);

    expect($switch->enabled())->toBeFalse()
        ->and($switch->resultsUrl())->toBe('/find')
        ->and($switch->blocksAction('/find?q=a'))->toBeTrue()
        ->and($switch->blocksAction('/field-notes'))->toBeFalse()
        ->and($switch->blocksAction('#latest-designs'))->toBeFalse();

    config(['capell-search.enabled' => true]);
    app()->forgetScopedInstances();

    expect($switch->blocksAction('/find'))->toBeFalse();
});

it('seeds demo search forms from the configured results path', function (): void {
    config(['capell-search.route_path' => 'find']);

    $section = (new ReflectionMethod(FoundationDemoContent::class, 'searchSection'));
    $content = $section->invoke(new FoundationDemoContent, 'Heading', 'Summary', []);

    expect(data_get($content, 'action'))->toBe('/find');
});
