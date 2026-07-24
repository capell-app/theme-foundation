<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\DesignSpec\CanonicalizeDesignSpecAction;
use Capell\FoundationTheme\Actions\DesignSpec\ReadDesignSpecAction;

function canonicalDesignSpecFixture(string $name): string
{
    $contents = file_get_contents(dirname(__DIR__, 2) . "/Fixtures/design-spec/{$name}.json");

    return $contents === false ? throw new RuntimeException('Unable to load canonical DesignSpec fixture.') : $contents;
}

it('freezes canonical v1 bytes and their sha256 boundary', function (): void {
    $canonical = CanonicalizeDesignSpecAction::run(canonicalDesignSpecFixture('v1-canonical'));
    $frozenBytes = rtrim(canonicalDesignSpecFixture('v1-canonical.canonical'), "\n");

    expect($canonical->schemaVersion)->toBe(1)
        ->and($canonical->bytes)->toBe($frozenBytes)
        ->and($canonical->sha256)->toBe('a3c6d539f062df8b458e6a6807e979e05ede430cca2e600ca3b00635511120ba')
        ->and(hash('sha256', $canonical->bytes))->toBe($canonical->sha256)
        ->and(ReadDesignSpecAction::run($canonical->bytes)->schemaVersion)->toBe(1);
});

it('produces identical bytes for equivalent object key order and typed input', function (): void {
    $payload = foundationThemeJsonObjectDocument(canonicalDesignSpecFixture('v1-canonical'));
    $reordered = array_reverse($payload, true);
    $fromOriginal = CanonicalizeDesignSpecAction::run($payload);
    $fromReordered = CanonicalizeDesignSpecAction::run($reordered);
    $fromTyped = CanonicalizeDesignSpecAction::run(ReadDesignSpecAction::run($payload));

    expect($fromReordered->bytes)->toBe($fromOriginal->bytes)
        ->and($fromReordered->sha256)->toBe($fromOriginal->sha256)
        ->and($fromTyped->bytes)->toBe($fromOriginal->bytes)
        ->and($fromTyped->sha256)->toBe($fromOriginal->sha256);
});

it('preserves list order as contract data and leaves input immutable', function (): void {
    $payload = foundationThemeJsonObjectDocument(canonicalDesignSpecFixture('v1-canonical'));
    $before = serialize($payload);
    $reorderedAssets = $payload;
    $reorderedAssets['assets'] = array_reverse(foundationThemeJsonList($reorderedAssets['assets'] ?? null));

    $canonical = CanonicalizeDesignSpecAction::run($payload);
    $differentOrder = CanonicalizeDesignSpecAction::run($reorderedAssets);

    expect(serialize($payload))->toBe($before)
        ->and($differentOrder->sha256)->not->toBe($canonical->sha256);
});

it('does not serialize server catalogue metadata or unsafe source fields', function (): void {
    $canonical = CanonicalizeDesignSpecAction::run(canonicalDesignSpecFixture('v1-canonical'));

    expect($canonical->bytes)
        ->not->toContain('"bytes"')
        ->not->toContain('"kind"')
        ->not->toContain('path')
        ->not->toContain('http://')
        ->not->toContain('https://');
});
