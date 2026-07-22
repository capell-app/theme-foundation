<?php

declare(strict_types=1);

test('theme screenshot files are unique across theme packages', function (): void {
    $packagesPath = dirname(__DIR__, 3);
    $manifestPaths = glob($packagesPath . '/theme-*/docs/screenshots.json') ?: [];
    $filesByHash = [];

    sort($manifestPaths);

    foreach ($manifestPaths as $manifestPath) {
        $packageDirectory = dirname($manifestPath, 2);
        $packageName = basename($packageDirectory);
        $screenshotsDirectory = dirname($manifestPath) . '/screenshots';

        if (! is_dir($screenshotsDirectory)) {
            continue;
        }

        $directoryIterator = new RecursiveDirectoryIterator($screenshotsDirectory, FilesystemIterator::SKIP_DOTS);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($fileIterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();

            if (! str_ends_with($filename, '.png') || str_ends_with($filename, '.failed.png')) {
                continue;
            }

            $hash = hash_file('sha256', $fileInfo->getPathname());

            if (! is_string($hash)) {
                throw new RuntimeException('Unable to hash theme screenshot ' . $fileInfo->getPathname() . '.');
            }

            $filesByHash[$hash][$packageName][] = str_replace(
                $packagesPath . '/',
                '',
                $fileInfo->getPathname(),
            );
        }
    }

    $duplicateGroups = [];

    foreach ($filesByHash as $filesByPackage) {
        if (count($filesByPackage) < 2) {
            continue;
        }

        $duplicatePaths = [];

        foreach ($filesByPackage as $paths) {
            array_push($duplicatePaths, ...$paths);
        }

        sort($duplicatePaths);
        $duplicateGroups[] = implode(', ', $duplicatePaths);
    }

    sort($duplicateGroups);

    expect($duplicateGroups)->toBe(
        [],
        'Theme screenshot files must not be byte-identical across packages. Remove contaminated captures and recapture each declared theme surface: ' . implode(' | ', $duplicateGroups),
    );
});
