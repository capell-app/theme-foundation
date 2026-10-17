<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Support\Creator\PageCreator;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\FoundationDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageInstaller;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Support\Arr;

it('adopts the generated error page when installing and reinstalling themed recovery content', function (): void {
    $language = LanguageFactory::new()->english()->create();
    $site = SiteFactory::new()->withTranslations($language)->create(['name' => 'Recovery adoption', 'language_id' => $language->id]);
    $original = resolve(PageCreator::class)->createErrorPage($site);
    $translation = $original->translations()->firstOrFail();
    $meta = $translation->meta ?? [];
    Arr::set($meta, 'error_status_copy.500.headline', 'Authored service failure');
    $errorCopy = data_get($meta, 'error_status_copy');
    expect($errorCopy)->toHaveCount(8);
    $translation->update(['meta' => $meta]);
    $data = new ThemeDemoInstallData([$site->name], ['en'], 'https://recovery.test');

    for ($install = 0; $install < 2; $install++) {
        ThemeDemoPageInstaller::run($data, 'default', 'Foundation', new FoundationDemoContent);
        $pages = Page::query()->where('site_id', $site->id)
            ->where('blueprint_id', $original->blueprint_id)->get();
        expect($pages)->toHaveCount(1);
        $recovery = $pages->sole();

        expect($recovery->id)->toBe($original->id)
            ->and(data_get($recovery->meta, 'theme_demo.surface'))->toBe('not-found')
            ->and($recovery->layout?->containers)->not->toBeEmpty()
            ->and(data_get($recovery->translations()->firstOrFail()->meta, 'error_status_copy'))->toBe($errorCopy)
            ->and(PageLoader::getSystemPage($site->refresh(), $language, PageTypeEnum::NotFound->value)?->getKey())->toBe($original->id);
    }
});
