<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Support\CapellOptionalExtensionAvailability;
use Livewire\LivewireManager;

afterEach(function (): void {
    CapellCore::clearResolvedInstances();
});

it('reports optional packages unavailable until Capell installs them', function (): void {
    CapellCore::shouldReceive('isPackageInstalled')
        ->twice()
        ->with('capell-app/example')
        ->andReturnFalse();
    $livewire = Mockery::mock(LivewireManager::class);
    $livewire->shouldNotReceive('exists');

    $availability = new CapellOptionalExtensionAvailability($livewire);

    expect($availability->packageAvailable('capell-app/example'))->toBeFalse()
        ->and($availability->livewireComponentAvailable('capell-app/example', 'public-example'))->toBeFalse();
});

it('requires both the installed package and registered public component', function (): void {
    CapellCore::shouldReceive('isPackageInstalled')
        ->twice()
        ->with('capell-app/example')
        ->andReturnTrue();

    $livewire = Mockery::mock(LivewireManager::class);
    $livewire->shouldReceive('exists')
        ->once()
        ->with('public-example')
        ->andReturnTrue();

    $availability = new CapellOptionalExtensionAvailability($livewire);

    expect($availability->packageAvailable('capell-app/example', [DateTimeImmutable::class]))->toBeTrue()
        ->and($availability->livewireComponentAvailable('capell-app/example', 'public-example'))->toBeTrue();
});
