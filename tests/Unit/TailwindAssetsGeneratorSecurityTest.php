<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\FoundationTheme\Tests\Fixtures\UnsafeTailwindAssetsFixtureProvider;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->tailwindProject = sys_get_temp_dir() . '/tailwind-security-' . bin2hex(random_bytes(8));
    (new Filesystem)->ensureDirectoryExists($this->tailwindProject);
    app()->setBasePath($this->tailwindProject);
    config([
        'capell-theme-foundation.tailwind.output_css' => $this->tailwindProject . '/frontend.css',
        'capell-theme-foundation.tailwind.split_theme_css' => false,
        'capell-theme-foundation.tailwind.validate_sources' => false,
    ]);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->tailwindProject);
});

test('default color keys and values are validated before registration', function (): void {
    config([
        'capell.default_colors' => [
            'primary' => '#123abc',
            'secondary' => 'rgb(12 34 56 / 50%)',
            'danger' => 'red; background: black',
        ],
    ]);

    $colors = (new TailwindAssetsGenerator(new Filesystem))->collect()->themeColors()->all();

    expect($colors)->toMatchArray([
        'primary' => 'rgb(18, 58, 188)',
        'secondary' => 'rgba(12, 34, 56, 0.5)',
    ])->not->toHaveKey('danger');
});

test('invalid provider-registered theme colors are skipped in generated output', function (): void {
    app()->register(UnsafeTailwindAssetsFixtureProvider::class);
    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate();
    $css = (new Filesystem)->get($paths[0]);

    expect($css)
        ->toContain('--color-safe-provider: #ffffff;')
        ->not->toContain('bad;color')
        ->not->toContain('--color-remote:')
        ->not->toContain('--color-injected:')
        ->not->toContain('url(https://example.test/color.svg)');
});

test('generated css is a standalone tailwind entrypoint', function (): void {
    config([
        'capell-theme-foundation.tailwind.imports' => ['swiper/css'],
        'capell-theme-foundation.tailwind.plugins' => ['@tailwindcss/typography'],
        'capell-theme-foundation.tailwind.sources' => ['views/**/*.blade.php'],
    ]);
    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate($this->tailwindProject . '/assets/frontend.css');
    $css = (new Filesystem)->get($paths[0]);

    expect($css)->toContain('@import "tailwindcss";', '@import "swiper/css";', '@plugin "@tailwindcss/typography";', '@source "../views/**/*.blade.php";');
});

test('directory output paths generate a frontend css entrypoint', function (): void {
    $directory = $this->tailwindProject . '/assets';
    $paths = (new TailwindAssetsGenerator(new Filesystem))->generate($directory);

    expect($paths)->toBe([$directory . '/frontend.css'])
        ->and((new Filesystem)->get($paths[0]))->toContain('@import "tailwindcss";');
});

test('output paths outside the project are rejected without creating a file', function (): void {
    $outsidePath = $this->tailwindProject . '-outside.css';

    expect(fn (): array => (new TailwindAssetsGenerator(new Filesystem))->generate($outsidePath))
        ->toThrow(InvalidArgumentException::class, 'Tailwind output CSS path must stay inside the project.');
    expect(is_file($outsidePath))->toBeFalse();
});

test('output path traversal is rejected without creating a file', function (): void {
    $outsidePath = '../' . basename($this->tailwindProject) . '-traversal.css';

    expect(fn (): array => (new TailwindAssetsGenerator(new Filesystem))->generate($outsidePath))
        ->toThrow(InvalidArgumentException::class, 'Tailwind output CSS path must stay inside the project.');
    expect(is_file(dirname($this->tailwindProject) . '/' . basename($outsidePath)))->toBeFalse();
});
