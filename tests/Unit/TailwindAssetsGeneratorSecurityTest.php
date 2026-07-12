<?php

declare(strict_types=1);

use Capell\Core\Support\Tailwind\TailwindAssetsRegistry;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Filesystem\Filesystem;

require_once dirname(__DIR__, 2) . '/src/Support/Tailwind/TailwindAssetsGenerator.php';

/**
 * @param  array<array-key, mixed>  $parameters
 */
function invokeFoundationThemeTailwindGeneratorMethod(
    TailwindAssetsGenerator $generator,
    string $methodName,
    array $parameters = [],
): mixed {
    $reflectionMethod = new ReflectionMethod($generator, $methodName);

    return $reflectionMethod->invokeArgs($generator, $parameters);
}

test('default color keys and values are validated before registration', function (): void {
    config([
        'capell.default_colors' => [
            'primary' => '#123abc',
            'secondary' => 'rgb(12 34 56 / 50%)',
            'danger' => 'red; background: black',
        ],
    ]);

    $generator = new TailwindAssetsGenerator(new Filesystem);
    $registry = new TailwindAssetsRegistry;

    invokeFoundationThemeTailwindGeneratorMethod($generator, 'registerDefaultThemeColors', [$registry]);

    expect($registry->themeColors()->all())->toBe([
        'primary' => 'rgb(18, 58, 188)',
        'secondary' => 'rgba(12, 34, 56, 0.5)',
    ]);
});

test('invalid provider-registered theme colors are skipped during render', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $registry = new TailwindAssetsRegistry;
    $registry->registerThemeColor('primary', '#ffffff');
    $registry->registerThemeColor('bad;color', '#000000');
    $registry->registerThemeColor('remote', 'url(https://example.com/color.svg)');
    $registry->registerThemeColor('injected', 'red; background: black');

    $css = invokeFoundationThemeTailwindGeneratorMethod($generator, 'renderCss', [$registry]);

    expect($css)
        ->toContain('--color-primary: #ffffff;')
        ->not->toContain('bad;color')
        ->not->toContain('remote')
        ->not->toContain('injected')
        ->not->toContain('url(');
});

test('generated css is a standalone tailwind entrypoint', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $registry = new TailwindAssetsRegistry;
    $registry->registerImport('swiper/css');
    $registry->registerPlugin('@tailwindcss/typography');
    $registry->registerSource('../views/**/*.blade.php');

    $css = invokeFoundationThemeTailwindGeneratorMethod($generator, 'renderCss', [$registry]);

    expect($css)->toStartWith('@import "tailwindcss";' . PHP_EOL)
        ->and($css)->toContain('@import "swiper/css";')
        ->and($css)->toContain('@plugin "@tailwindcss/typography";')
        ->and($css)->toContain('@source "../views/**/*.blade.php";');
});

test('directory output paths generate a frontend css entrypoint', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $directory = storage_path('framework/testing/capell-theme-foundation-tailwind');

    $path = invokeFoundationThemeTailwindGeneratorMethod(
        $generator,
        'targetPath',
        [$directory],
    );

    expect($path)->toBe($directory . '/frontend.css');
});

test('output paths outside the project are rejected', function (): void {
    $outsidePath = dirname(base_path()) . '/capell-theme-foundation-tailwind-' . uniqid() . '.css';

    expect(fn (): array => (new TailwindAssetsGenerator(new Filesystem))->generate($outsidePath))
        ->toThrow(InvalidArgumentException::class, 'Tailwind output CSS path must stay inside the project.');
});

test('output path traversal is rejected', function (): void {
    expect(fn (): mixed => invokeFoundationThemeTailwindGeneratorMethod(
        new TailwindAssetsGenerator(new Filesystem),
        'targetPath',
        ['../capell-theme-foundation-tailwind-' . uniqid() . '.css'],
    ))->toThrow(InvalidArgumentException::class, 'Tailwind output CSS path must stay inside the project.');
});
