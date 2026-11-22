<?php

declare(strict_types=1);

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Demo\SelfHostedDemoMedia;
use Capell\FoundationTheme\Support\Demo\ThemeDemoMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

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

it('serves a pool a theme ships itself at the URL Foundation would use', function (): void {
    $file = $this->packages . '/theme-business/publishes/demo-media/local-services/proof-1.webp';
    File::ensureDirectoryExists(dirname($file));
    File::put($file, 'webp');
    File::put($this->packages . '/theme-business/demo-assets.json', json_encode(['assets' => [
        ['id' => 'pool/local-services/proof-1', 'file' => 'publishes/demo-media/local-services/proof-1.webp'],
        // A pool slot outside its own path could overwrite another published image.
        ['id' => 'pool/local-services/proof-2', 'file' => 'publishes/demo-media/first-screen.webp'],
    ]], JSON_THROW_ON_ERROR));
    SelfHostedDemoMedia::usePackagesDirectory($this->packages);

    expect(SelfHostedDemoMedia::poolUrl('local-services', 'proof', 1))
        ->toBe('/vendor/capell-theme-foundation/demo-media/local-services/proof-1.webp')
        ->and(SelfHostedDemoMedia::poolUrl('local-services', 'proof', 2))->toBeNull()
        ->and(SelfHostedDemoMedia::pools()['local-services']['proof'] ?? null)
        ->toBe(['/vendor/capell-theme-foundation/demo-media/local-services/proof-1.webp'])
        ->and(SelfHostedDemoMedia::themePoolFiles())
        ->toBe([$this->packages . '/theme-business/publishes/demo-media/local-services/proof-1.webp' => 'local-services/proof-1.webp']);
});

it('publishes the pools themes ship beside Foundation demo media', function (): void {
    $paths = ServiceProvider::pathsToPublish(FoundationThemeServiceProvider::class, 'capell-theme-foundation-assets');
    $target = public_path(SelfHostedDemoMedia::PUBLIC_PATH);
    $published = [];

    foreach ($paths as $source => $destination) {
        if (preg_match('#/(theme-[a-z-]+)/publishes/demo-media/([a-z-]+/[a-z]+-\d+\.webp)$#', $source, $match) === 1) {
            $published[$match[1] . ':' . $match[2]] = $destination;
        }
    }

    expect($published)->toMatchArray([
        'theme-business:local-services/proof-1.webp' => $target . '/local-services/proof-1.webp',
        'theme-bistro:bistro/listing-1.webp' => $target . '/bistro/listing-1.webp',
        'theme-paperdesk:paperdesk/detail-5.webp' => $target . '/paperdesk/detail-5.webp',
    ]);
});

it('keeps a pool slot Foundation records when a theme records it too', function (): void {
    $file = $this->packages . '/theme-rogue/publishes/demo-media/portfolio/listing-1.webp';
    File::ensureDirectoryExists(dirname($file));
    File::put($file, 'rogue');
    File::put($this->packages . '/theme-rogue/demo-assets.json', json_encode(['assets' => [
        ['id' => 'pool/portfolio/listing-1', 'file' => 'publishes/demo-media/portfolio/listing-1.webp'],
    ]], JSON_THROW_ON_ERROR));
    SelfHostedDemoMedia::usePackagesDirectory($this->packages);

    expect(SelfHostedDemoMedia::themePoolFiles())->toBe([]);
});
