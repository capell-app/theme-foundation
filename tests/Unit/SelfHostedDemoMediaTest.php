<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\Demo\SelfHostedDemoMedia;
use Capell\FoundationTheme\Support\Demo\ThemeDemoMedia;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->packages = sys_get_temp_dir() . '/capell-demo-media-' . uniqid('', true);

    $record = function (string $package, string $id, string $file): void {
        $directory = $this->packages . '/' . $package;
        File::ensureDirectoryExists(dirname($directory . '/' . $file));
        File::put($directory . '/' . $file, 'webp');
        File::put($directory . '/demo-assets.json', json_encode(['assets' => [['id' => $id, 'file' => $file]]], JSON_THROW_ON_ERROR));
    };

    $record('theme-foundation', 'pool/portfolio/listing-1', 'publishes/demo-media/portfolio/listing-1.webp');
    $record('theme-minimalist', 'first-screen/minimalist', 'publishes/demo-media/first-screen.webp');
    $record('theme-portfolio', 'first-screen/portfolio', 'publishes/demo-media/first-screen.webp');

    SelfHostedDemoMedia::usePackagesDirectory($this->packages);
});

afterEach(function (): void {
    SelfHostedDemoMedia::usePackagesDirectory(null);
    File::deleteDirectory($this->packages);
});

it('serves recorded pool images and first screens from the site origin', function (): void {
    expect(SelfHostedDemoMedia::poolUrl('portfolio', 'listing', 1))
        ->toBe('/vendor/capell-theme-foundation/demo-media/portfolio/listing-1.webp')
        ->and(SelfHostedDemoMedia::firstScreenUrl('minimalist'))
        ->toBe('/vendor/capell-theme-foundation/demo-media/first-screen/minimalist.webp');
});

it('resolves nothing for unrecorded slots or recorded files that do not ship', function (): void {
    File::delete($this->packages . '/theme-portfolio/publishes/demo-media/first-screen.webp');
    SelfHostedDemoMedia::usePackagesDirectory($this->packages);

    expect(SelfHostedDemoMedia::poolUrl('portfolio', 'listing', 2))->toBeNull()
        ->and(SelfHostedDemoMedia::firstScreenUrl('portfolio'))->toBeNull()
        ->and(SelfHostedDemoMedia::firstScreenUrl('catalogue'))->toBeNull();
});

it('opens themes that share a pool on their own first screen and swaps recorded pool images', function (): void {
    $minimalist = ThemeDemoMedia::groupedForTheme('minimalist');
    $curated = ThemeDemoMedia::groupedForTheme('curated');
    $selfHostedListing = '/vendor/capell-theme-foundation/demo-media/portfolio/listing-1.webp';

    expect($minimalist['hero'])->toBe(['/vendor/capell-theme-foundation/demo-media/first-screen/minimalist.webp'])
        ->and($curated['hero'])->not->toBe($minimalist['hero'])
        ->and($minimalist['listing'][0])->toBe($selfHostedListing)
        ->and(ThemeDemoMedia::distinctPool('minimalist')[0])->toBe($minimalist['hero'][0]);
});
