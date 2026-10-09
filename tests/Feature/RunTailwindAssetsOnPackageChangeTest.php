<?php

declare(strict_types=1);

use Capell\Core\Data\PackageData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\Tests\Support\OwnedTestbenchSkeleton;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    OwnedTestbenchSkeleton::useStorageProject(app());
});

function foundationTailwindPackageChange(bool $installed): void
{
    $package = new PackageData(
        name: FoundationThemeServiceProvider::$packageName,
        type: PackageTypeEnum::Theme,
        serviceProviderClass: FoundationThemeServiceProvider::class,
        path: __DIR__,
    );
    $listener = resolve(RunTailwindAssetsOnPackageChange::class);

    if ($installed) {
        $listener->handleInstalled(new PackageInstalled($package));
    } else {
        $listener->handleUninstalled(new PackageUninstalled($package));
    }
}

describe('package change without a writable source tree', function (): void {
    beforeEach(function (): void {
        $this->filesystem = new Filesystem;
        $this->root = storage_path('framework/testing/capell-theme-foundation-package-change');
        $this->previousEnvironment = app()->environment();
        $this->filesystem->deleteDirectory($this->root);
        $this->filesystem->ensureDirectoryExists($this->root . '/css');
        $this->filesystem->ensureDirectoryExists($this->root . '/themes');

        config([
            'capell-theme-foundation.tailwind.output_css' => $this->root . '/css/frontend.css',
            'capell-theme-foundation.tailwind.theme_css_output_directory' => $this->root . '/themes',
            'capell-theme-foundation.tailwind.split_theme_css' => true,
        ]);
        CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
        $this->log = Mockery::spy(LoggerInterface::class);
        Log::swap($this->log);
    });

    afterEach(function (): void {
        app()->instance('env', $this->previousEnvironment);

        foreach ([$this->root, $this->root . '/css', $this->root . '/themes', $this->root . '/css/frontend.css'] as $path) {
            if (file_exists($path)) {
                chmod($path, is_dir($path) ? 0755 : 0644);
            }
        }

        $this->filesystem->deleteDirectory($this->root);
    });

    it('generates the assets in a writable tree', function (bool $installed): void {
        app()->instance('env', 'testing');

        foundationTailwindPackageChange($installed);

        expect($this->filesystem->exists($this->root . '/css/frontend.css'))->toBeTrue();
        $this->log->shouldNotHaveReceived('notice');
    })->with(['installed' => [true], 'uninstalled' => [false]]);

    it('skips generation in production even when the tree is writable', function (bool $installed): void {
        app()->instance('env', 'production');

        foundationTailwindPackageChange($installed);

        expect($this->filesystem->exists($this->root . '/css/frontend.css'))->toBeFalse();
        $this->log->shouldHaveReceived('notice')->once()->with('Skipping Tailwind asset generation after a package change in production.');
    })->with(['installed' => [true], 'uninstalled' => [false]]);

    it('skips generation instead of failing when an output path is not writable', function (string $unwritable, string $reported, bool $installed): void {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Root ignores file permissions.');
        }

        app()->instance('env', 'testing');

        if ($unwritable === 'existing file') {
            $this->filesystem->put($this->root . '/css/frontend.css', 'unchanged');
            chmod($this->root . '/css/frontend.css', 0444);
        }

        if ($unwritable === 'missing parent') {
            config(['capell-theme-foundation.tailwind.output_css' => $this->root . '/css/new/frontend.css']);
        }

        if ($unwritable !== 'themes directory' && $unwritable !== 'existing file') {
            chmod($this->root . '/css', 0555);
        }

        if ($unwritable === 'themes directory') {
            chmod($this->root . '/themes', 0555);
        }

        foundationTailwindPackageChange($installed);

        expect($this->filesystem->exists($this->root . '/css/frontend.css') ? $this->filesystem->get($this->root . '/css/frontend.css') : null)
            ->toBe($unwritable === 'existing file' ? 'unchanged' : null)
            ->and($this->filesystem->exists($this->root . '/css/new'))->toBeFalse();
        $this->log->shouldHaveReceived('notice')->once()->with(
            'Skipping Tailwind asset generation after a package change because an output path is not writable.',
            ['path' => $this->root . $reported],
        );
    })->with([
        'css directory' => ['css directory', '/css', true],
        'themes directory' => ['themes directory', '/themes', true],
        'existing file' => ['existing file', '/css/frontend.css', true],
        'missing parent' => ['missing parent', '/css', true],
        'css directory on uninstall' => ['css directory', '/css', false],
    ]);
});

it('regenerates Tailwind assets when the running Artisan application predates the theme command', function (): void {
    $filesystem = new Filesystem;
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-late-artisan');
    $target = $targetDirectory . '/frontend.css';
    $filesystem->deleteDirectory($targetDirectory);

    config(['capell-theme-foundation.tailwind.output_css' => $target]);
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);

    // The first `capell:install` run requires this package through a Composer
    // child process. Its provider is registered after the running Artisan
    // application was built, so commands it queues never reach that application.
    ConsoleApplication::forgetBootstrappers();
    resolve(Kernel::class)->setArtisan(new ConsoleApplication(app(), resolve(Dispatcher::class), app()->version()));

    expect(Artisan::all())->not->toHaveKey('capell:frontend-tailwind-assets');

    try {
        resolve(RunTailwindAssetsOnPackageChange::class)->handleInstalled(new PackageInstalled(new PackageData(
            name: FoundationThemeServiceProvider::$packageName,
            type: PackageTypeEnum::Theme,
            serviceProviderClass: FoundationThemeServiceProvider::class,
            path: __DIR__,
        )));

        expect($filesystem->exists($target))->toBeTrue()
            ->and(Str::contains($filesystem->get($target), [
                TailwindAssetsGenerator::SPLIT_GENERATION_MARKER,
                TailwindAssetsGenerator::COMBINED_GENERATION_MARKER,
            ]))->toBeTrue();
    } finally {
        $filesystem->deleteDirectory($targetDirectory);
    }
});
