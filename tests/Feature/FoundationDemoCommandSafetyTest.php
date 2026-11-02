<?php

declare(strict_types=1);

use Capell\Core\Actions\FindPageUrlsMissingSiteDomainsAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\FoundationTheme\Console\Commands\DemoCommand;
use Illuminate\Support\Facades\Artisan;

it('keeps the Foundation singular-option command scoped and repeatable', function (): void {
    $command = resolve(DemoCommand::class);
    expect($command->getHelp())->toContain('--site=', '--language=', '--base-url=', '--force');
    Artisan::registerCommand($command);
    $options = ['--site' => ['Disposable Foundation'], '--language' => ['en'], '--base-url' => 'https://foundation.test'];
    expect(Artisan::call('capell:theme-foundation-demo', $options))->toBe(0);
    $site = Site::query()->where('name', 'Disposable Foundation')->sole();
    $pageIds = $site->pages()->orderBy('id')->pluck('id')->all();
    expect($pageIds)->not->toBeEmpty();

    expect(fn (): int => Artisan::call('capell:theme-foundation-demo', ['--force' => true]))->toThrow(InvalidArgumentException::class);
    expect(Artisan::call('capell:theme-foundation-demo', [...$options, '--force' => true]))->toBe(0);
    expect($site->pages()->orderBy('id')->pluck('id')->all())->toBe($pageIds)
        ->and($site->siteDomains()->sole()->domain)->toBe('foundation.test');
});

it('refuses blank lifecycle targets on an existing installation even with force', function (bool $force): void {
    $site = Site::factory()->create();
    $pageIds = $site->pages()->orderBy('id')->pluck('id')->all();
    Artisan::registerCommand(resolve(DemoCommand::class));

    expect(fn (): int => Artisan::call('capell:theme-foundation-demo', [
        '--sites' => ' , ',
        '--languages' => 'en,fr',
        '--url' => 'https://foundation.test',
        '--force' => $force,
    ]))->toThrow(InvalidArgumentException::class);

    expect($site->pages()->orderBy('id')->pluck('id')->all())->toBe($pageIds);
})->with([false, true]);

it('gives an existing site a domain for every demo language it seeds pages in, keeping its own domains', function (): void {
    $german = Language::factory()->create(['code' => 'de', 'locale' => 'de_DE', 'default' => true]);
    $site = Site::factory()->create(['name' => 'Capell Services', 'language_id' => $german->getKey()]);
    $original = SiteDomain::factory()->create([
        'site_id' => $site->getKey(),
        'language_id' => $german->getKey(),
        'scheme' => 'http',
        'domain' => 'localhost',
        'port' => 8000,
        'path' => null,
        'default' => true,
        'status' => true,
    ]);
    $originalAttributes = $original->only(['scheme', 'domain', 'port', 'path', 'routing_identity']);
    Artisan::registerCommand(resolve(DemoCommand::class));

    $options = ['--sites' => 'Capell Services', '--languages' => 'de,en,fr', '--url' => 'http://localhost:8000'];
    expect(Artisan::call('capell:theme-foundation-demo', $options))->toBe(0);

    expect(PageUrl::query()->where('site_id', $site->getKey())->distinct()->count('language_id'))->toBe(3)
        ->and(FindPageUrlsMissingSiteDomainsAction::run())->toBeEmpty()
        ->and($original->refresh()->only(['scheme', 'domain', 'port', 'path', 'routing_identity']))->toBe($originalAttributes)
        ->and($site->siteDomains()->where('status', true)->orderBy('id')->pluck('path')->all())->toBe([null, '/en', '/fr']);

    $identities = SiteDomain::query()->orderBy('id')->pluck('routing_identity')->all();

    expect(Artisan::call('capell:theme-foundation-demo', $options))->toBe(0)
        ->and(SiteDomain::query()->orderBy('id')->pluck('routing_identity')->all())->toBe($identities)
        ->and(collect($identities)->unique())->toHaveCount(3);
});

it('never publishes an existing site that has no active domain by inventing domains for demo languages', function (): void {
    $german = Language::factory()->create(['code' => 'de', 'locale' => 'de_DE', 'default' => true]);
    $site = Site::factory()->create(['name' => 'Customer Site', 'language_id' => $german->getKey()]);
    $disabled = SiteDomain::factory()->create([
        'site_id' => $site->getKey(),
        'language_id' => $german->getKey(),
        'scheme' => 'https',
        'domain' => 'customer.test',
        'path' => null,
        'default' => true,
        'status' => false,
    ]);
    Artisan::registerCommand(resolve(DemoCommand::class));

    expect(Artisan::call('capell:theme-foundation-demo', [
        '--sites' => 'Customer Site',
        '--languages' => 'de,en,fr',
        '--url' => 'https://customer.test',
    ]))->toBe(0);

    expect($site->siteDomains()->withTrashed()->pluck('id')->all())->toBe([$disabled->getKey()])
        ->and($disabled->refresh()->status)->toBeFalse()
        ->and(SiteDomain::query()->where('status', true)->exists())->toBeFalse();
});
