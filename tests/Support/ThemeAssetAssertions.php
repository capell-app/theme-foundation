<?php

declare(strict_types=1);

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/DomQueries.php';

/** @param view-string $viewName */
function assertThemeModuleAssetRendered(string $viewName): void
{
    $directory = sys_get_temp_dir() . '/theme-module-' . bin2hex(random_bytes(8));
    $buildDirectory = 'vendor/capell-theme-foundation';
    File::ensureDirectoryExists($directory . '/' . $buildDirectory);
    File::put($directory . '/' . $buildDirectory . '/manifest.json', json_encode([
        'resources/js/capell-frontend.js' => ['file' => 'assets/public-module.js', 'src' => 'resources/js/capell-frontend.js', 'isEntry' => true],
    ], JSON_THROW_ON_ERROR));
    $previousPublicPath = public_path();
    $previousVite = app(Vite::class);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    app()->usePublicPath($directory);
    app()->instance(Vite::class, (new Vite)->useHotFile($directory . '/hot'));
    try {
        $html = view($viewName, ['section' => []])->render();
        expect(domQueryCount($html, '//script[@type="module"][contains(@src, "/vendor/capell-theme-foundation/assets/public-module.js")]'))->toBe(1);
    } finally {
        app()->usePublicPath($previousPublicPath);
        app()->instance(Vite::class, $previousVite);
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
        File::deleteDirectory($directory);
    }
}
