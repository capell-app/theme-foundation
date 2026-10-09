<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;
use Symfony\Component\Process\Process;

/**
 * @param  list<string>  $committedPaths
 */
function assertFoundationScreenshotReceipts(string $repositoryRoot, array $committedPaths): void
{
    /** @return array<array-key, mixed> */
    $readJson = static function (string $relativePath) use ($repositoryRoot): array {
        $decoded = json_decode((string) file_get_contents($repositoryRoot . '/' . $relativePath), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException("Screenshot document [{$relativePath}] must decode to an array.");
        }

        return $decoded;
    };

    $manifest = $readJson('packages/theme-foundation/docs/screenshots.json');
    $entries = $manifest['entries'] ?? null;
    if (! is_array($entries)) {
        throw new RuntimeException('Foundation screenshot manifest must contain entries.');
    }

    /** @var array<string, array{generatedAt: DateTimeImmutable, provenance: array<array-key, mixed>, receipts: array<array-key, mixed>}> $reports */
    $reports = [];
    foreach ($committedPaths as $relativePath) {
        if (! str_starts_with($relativePath, 'docs/screenshot-receipts/') || ! str_starts_with(basename($relativePath), 'theme-foundation--')) {
            continue;
        }

        $report = $readJson($relativePath);
        $provenance = $report['provenance'] ?? null;
        if (! is_array($provenance) || ($provenance['schemaVersion'] ?? null) !== 2) {
            continue;
        }

        $generatedAt = $provenance['generatedAt'] ?? null;
        $receipts = $provenance['receipts'] ?? null;
        if (! is_string($generatedAt) || $generatedAt === '' || ! is_array($receipts)) {
            throw new RuntimeException("Malformed current-schema screenshot report [{$relativePath}].");
        }

        $reports[$relativePath] = [
            'generatedAt' => new DateTimeImmutable($generatedAt),
            'provenance' => $provenance,
            'receipts' => $receipts,
        ];
    }

    /** @var list<string> $missingReceipts */
    $missingReceipts = [];
    $verified = 0;
    foreach ($entries as $entry) {
        if (! is_array($entry) || ! is_string($entry['id'] ?? null)) {
            throw new RuntimeException('Foundation screenshot manifest entries must have string IDs.');
        }

        $entryHashes = [];
        foreach (['screenshotPath' => '', 'darkScreenshotPath' => '-dark'] as $pathKey => $suffix) {
            $relativePath = $entry[$pathKey] ?? null;
            if ($relativePath === null) {
                continue;
            }
            if (! is_string($relativePath)) {
                throw new RuntimeException("Malformed screenshot path for [{$entry['id']}].");
            }
            if (! in_array($relativePath, $committedPaths, true)) {
                continue;
            }

            $id = $entry['id'] . $suffix;
            Assert::assertFileExists($repositoryRoot . '/' . $relativePath, "Committed screenshot [{$id}].");
            $newest = null;
            $newestPath = '';
            foreach ($reports as $reportPath => $report) {
                // Per-entry reports are copies of whole batches. Only the named
                // entry (and its dark variant) establishes image promotion;
                // incidental batch captures may never have been committed.
                if (! in_array(basename($reportPath), ['theme-foundation--' . $entry['id'] . '.json', 'theme-foundation--' . $id . '.json'], true)) {
                    continue;
                }
                if ($newest === null || $report['generatedAt'] > $newest['generatedAt']) {
                    $newest = $report;
                    $newestPath = $reportPath;
                }
            }

            if ($newest === null) {
                $missingReceipts[] = $id;

                continue;
            }

            $context = "Screenshot [{$id}] in [{$newestPath}]";
            Assert::assertSame('shared-capell-screenshot-runner', $newest['provenance']['generator'] ?? null, $context);
            Assert::assertSame('runner-only-v2', $newest['provenance']['policy'] ?? null, $context);
            Assert::assertSame(false, $newest['provenance']['dryRun'] ?? null, $context);

            $matching = array_values(array_filter($newest['receipts'], static fn (mixed $receipt): bool => is_array($receipt) && ($receipt['id'] ?? null) === $id));
            Assert::assertCount(1, $matching, $context);
            $receipt = $matching[0];
            if (! is_array($receipt) || ! is_array($receipt['output'] ?? null) || ! is_string($receipt['output']['sha256'] ?? null)) {
                throw new RuntimeException("Malformed output for {$context}.");
            }

            $hash = $receipt['output']['sha256'];
            Assert::assertSame('theme-foundation', $receipt['package'] ?? null, $context);
            Assert::assertSame('accepted', $receipt['acceptance'] ?? null, $context);
            // Exact repository-relative equality rejects machine-local paths.
            Assert::assertSame($relativePath, $receipt['output']['path'] ?? null, $context);
            Assert::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $hash, $context);
            Assert::assertSame($hash, hash_file('sha256', $repositoryRoot . '/' . $relativePath), $context . ' must match the committed image.');
            $entryHashes[$suffix] = $hash;
            $verified++;
        }

        if (isset($entryHashes[''], $entryHashes['-dark'])) {
            Assert::assertNotSame($entryHashes[''], $entryHashes['-dark'], "Light and dark screenshots for [{$entry['id']}] must differ.");
        }
    }

    // The receipt gap is closed. Recaptures must retain complete coverage.
    $coverage = sprintf('%d images verified; %d committed images lack current-schema per-entry receipts: %s', $verified, count($missingReceipts), implode(', ', $missingReceipts));
    Assert::assertLessThanOrEqual(0, count($missingReceipts), $coverage);
    Assert::assertGreaterThan(0, $verified, $coverage);
}

it('binds the newest committed runner receipt to every receipted Foundation screenshot', function (): void {
    $repositoryRoot = dirname(__DIR__, 4);
    $process = new Process(['git', 'ls-tree', '-r', '--name-only', '-z', 'HEAD', '--', 'packages/theme-foundation/docs/screenshots', 'docs/screenshot-receipts'], $repositoryRoot);
    $process->mustRun();
    $committedPaths = array_values(array_filter(explode("\0", $process->getOutput()), static fn (string $path): bool => $path !== ''));

    assertFoundationScreenshotReceipts($repositoryRoot, $committedPaths);
});
