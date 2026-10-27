<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
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
