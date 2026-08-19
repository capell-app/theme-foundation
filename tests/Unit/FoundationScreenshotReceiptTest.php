<?php

declare(strict_types=1);

it('binds the runner receipt to every Foundation chrome artifact', function (): void {
    $repositoryRoot = dirname(__DIR__, 4);
    $receiptPath = $repositoryRoot . '/docs/screenshot-receipts/cap0133/theme-foundation-chrome-homepage-matrix.json';
    $receipt = json_decode((string) file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($receipt)) {
        throw new RuntimeException('Foundation screenshot receipt must decode to an array.');
    }

    if (! is_array($receipt['provenance'] ?? null)) {
        throw new RuntimeException('Foundation screenshot receipt provenance must be an array.');
    }

    $provenance = $receipt['provenance'];
    if (! is_array($provenance['receipts'] ?? null)) {
        throw new RuntimeException('Foundation screenshot receipt artifacts must be an array.');
    }
    expect($receipt['captured'] ?? null)->toBe(4)
        ->and($receipt['skipped'] ?? null)->toBe(0)
        ->and($receipt['failed'] ?? null)->toBe(0)
        ->and($provenance['schemaVersion'] ?? null)->toBe(1)
        ->and($provenance['generator'] ?? null)->toBe('shared-capell-screenshot-runner')
        ->and($provenance['policy'] ?? null)->toBe('runner-only-v1')
        ->and($provenance['generatedAt'] ?? null)->toBeString();

    $receipts = collect($provenance['receipts'])->keyBy('id');
    $expected = [
        'foundation-chrome-homepage' => 'packages/theme-foundation/docs/screenshots/foundation-chrome-homepage.png',
        'foundation-chrome-homepage-dark' => 'packages/theme-foundation/docs/screenshots/foundation-chrome-homepage-dark.png',
        'foundation-chrome-homepage-mobile' => 'packages/theme-foundation/docs/screenshots/foundation-chrome-homepage-mobile.png',
        'foundation-chrome-homepage-mobile-dark' => 'packages/theme-foundation/docs/screenshots/foundation-chrome-homepage-mobile-dark.png',
    ];

    expect($receipts->keys()->sort()->values()->all())->toBe(collect(array_keys($expected))->sort()->values()->all());

    foreach ($expected as $id => $relativePath) {
        $artifactReceipt = $receipts->get($id);
        $artifactPath = $repositoryRoot . '/' . $relativePath;

        if (! is_array($artifactReceipt)) {
            throw new RuntimeException("Missing Foundation screenshot receipt for [{$id}].");
        }

        $artifactPackage = $artifactReceipt['package'] ?? null;
        $artifactOutput = $artifactReceipt['output'] ?? null;
        $artifactHash = $artifactReceipt['sha256'] ?? null;
        if (! is_string($artifactPackage) || ! is_string($artifactOutput) || ! is_string($artifactHash)) {
            throw new RuntimeException("Malformed Foundation screenshot receipt for [{$id}].");
        }

        expect($artifactPackage)->toBe('theme-foundation')
            // Receipt outputs are normalised to repository-relative paths by
            // scripts/screenshots/relativise-evidence-paths.mjs, so this asserts
            // exact equality rather than a suffix: an absolute machine-local
            // path must not satisfy it.
            ->and($artifactOutput)->toBe($relativePath)
            ->and($artifactHash)->toMatch('/\A[a-f0-9]{64}\z/')
            ->and(is_file($artifactPath))->toBeTrue()
            ->and(hash_file('sha256', $artifactPath))->toBe($artifactHash);
    }

    $receiptHashes = [];
    foreach (array_keys($expected) as $id) {
        $receipt = $receipts->get($id);
        if (! is_array($receipt) || ! is_string($receipt['sha256'] ?? null)) {
            throw new RuntimeException("Missing hash for Foundation screenshot receipt [{$id}].");
        }
        $receiptHashes[$id] = $receipt['sha256'];
    }

    expect($receiptHashes['foundation-chrome-homepage'])
        ->not->toBe($receiptHashes['foundation-chrome-homepage-dark'])
        ->and($receiptHashes['foundation-chrome-homepage-mobile'])
        ->not->toBe($receiptHashes['foundation-chrome-homepage-mobile-dark']);
});
