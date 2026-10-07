<?php

declare(strict_types=1);

it('proves the legacy beacon utility is absent from the built asset graph', function (): void {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2) . '/publishes/build/manifest.json') ?: '',
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR);

    expect(file_exists(dirname(__DIR__, 2) . '/resources/js/utilities/beacon-data.js'))->toBeFalse()
        ->and($manifestJson)->not->toContain('beacon-data.js')
        ->and($manifestJson)->not->toContain('window.beaconData');
});
