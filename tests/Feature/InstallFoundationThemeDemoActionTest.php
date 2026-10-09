<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction;
use Capell\FoundationTheme\Actions\InstallFoundationThemeLayoutDefaultsAction;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;

it('installs one default layout per fresh site and keeps it stable on repeated installs', function (bool $force): void {
    InstallFoundationThemeLayoutDefaultsAction::run();

    $data = new ThemeDemoInstallData(
        siteNames: ['Primary site', 'Secondary site'],
        languageCodes: ['en'],
        baseUrl: 'https://fresh.test',
        force: $force,
    );

    expect(Layout::query()->whereNotNull('site_id')->exists())->toBeFalse();

    InstallFoundationThemeDemoAction::run($data);

    $sites = Site::query()->orderBy('id')->get();
    $defaultSite = Site::query()->default()->sole();
    $layoutIds = Layout::query()->orderBy('id')->pluck('id')->all();
    $pageIds = Page::query()->orderBy('id')->pluck('id')->all();

    expect($sites)->toHaveCount(2)
        ->and($defaultSite->name)->toBe('Primary site');

    foreach ($sites as $site) {
        expect($site->layouts()->default()->count())->toBe(1);

        $defaultLayout = Layout::query()->where('site_id', $site->id)->where('default', true)->firstOrFail();
        $homepage = $site->pages()->where('meta->theme_demo->surface', 'homepage')->sole();

        expect($defaultLayout->id)->toBe($homepage->layout_id);
    }

    InstallFoundationThemeDemoAction::run($data);

    expect(Layout::query()->orderBy('id')->pluck('id')->all())->toBe($layoutIds)
        ->and(Page::query()->orderBy('id')->pluck('id')->all())->toBe($pageIds);

    foreach ($sites as $site) {
        expect($site->layouts()->default()->count())->toBe(1)
            ->and($site->layouts()->default()->sole()->id)
            ->toBe($site->pages()->where('meta->theme_demo->surface', 'homepage')->sole()->layout_id);
    }
})->with([false, true]);

it('preserves a chosen site default when demo layouts are installed again', function (bool $custom): void {
    $data = new ThemeDemoInstallData(['Existing site'], ['en'], 'https://existing.test');

    InstallFoundationThemeDemoAction::run($data);

    $site = Site::query()->default()->sole();
    $site->layouts()->default()->update(['default' => false]);
    $chosenLayout = $custom
        ? Layout::factory()->create(['site_id' => $site->id, 'key' => 'custom-page', 'default' => true])
        : $site->layouts()->where('meta->theme_demo->surface', 'directory')->sole();
    $chosenLayout->update(['default' => true]);

    InstallFoundationThemeDemoAction::run($data);

    expect($chosenLayout->refresh()->default)->toBeTrue()
        ->and($site->layouts()->default()->count())->toBe(1)
        ->and($site->layouts()->default()->sole()->id)->toBe($chosenLayout->id);
})->with([false, true]);
