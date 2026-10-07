<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\FormBuilder\Models\Form;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Console\Kernel;
use Livewire\Livewire;
use PHPUnit\Framework\Assert;
use Spatie\LaravelData\DataCollection;
use Workbench\App\Console\Commands\PrepareFoundationScreenshotEvidenceCommand;
use Workbench\App\Providers\ScreenshotWorkbenchServiceProvider;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

it('boots Foundation form dependencies even without a selected theme environment variable', function (): void {
    $previousWorkbench = getenv('CAPELL_SCREENSHOT_WORKBENCH');
    $previousTheme = getenv('CAPELL_SCREENSHOT_THEME_PACKAGE');
    putenv('CAPELL_SCREENSHOT_WORKBENCH=true');
    putenv('CAPELL_SCREENSHOT_THEME_PACKAGE');
    CapellCore::forcePackageInstalled('capell-app/form-builder', false);

    try {
        new ScreenshotWorkbenchServiceProvider(app())->register();
        expect(CapellCore::isPackageInstalled('capell-app/form-builder'))->toBeTrue();
        [$pageUrl] = layoutNativeThemeCreatePage('default', 'Workbench Enquiry', 'contact');
        $this->get($pageUrl->full_url)->assertOk()->assertDontSee('data-theme-form-fallback', false);
        $form = Form::query()->where('handle', 'foundation-enquiry')->firstOrFail();
        expect($form->is_active)->toBeTrue();
        Livewire::test('public-form', ['handle' => 'foundation-enquiry'])
            ->call('loadForm')->assertSeeHtml('<form')->assertSee('Email');
    } finally {
        putenv($previousWorkbench === false ? 'CAPELL_SCREENSHOT_WORKBENCH' : 'CAPELL_SCREENSHOT_WORKBENCH=' . $previousWorkbench);
        putenv($previousTheme === false ? 'CAPELL_SCREENSHOT_THEME_PACKAGE' : 'CAPELL_SCREENSHOT_THEME_PACKAGE=' . $previousTheme);
    }
});

it('prepares the same authored Foundation homepage and footer for shared chrome and error pages', function (): void {
    layoutNativeThemeCreatePage('default', 'Workbench Chrome');
    $site = Site::query()->firstOrFail();
    $site->update(['default' => true]);
    $home = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $installerHome = $home->replicate();
    $installerHome->name = 'Welcome';
    $installerHome->meta = [];
    $installerHome->save();
    foreach ($home->translations as $translation) {
        $installerTranslation = $translation->replicate();
        $installerTranslation->translatable_id = $installerHome->id;
        $installerTranslation->title = 'Welcome to Capell';
        $installerTranslation->meta = ['slug' => 'welcome'];
        $installerTranslation->save();
    }

    app(Kernel::class)->registerCommand(new PrepareFoundationScreenshotEvidenceCommand);
    $this->artisan('capell:prepare-foundation-screenshot-evidence')->assertSuccessful();
    $installerHome->refresh()->load('translations');
    $installerTranslation = $installerHome->translation;
    $homeTranslation = $home->translation;
    Assert::assertInstanceOf(Translation::class, $installerTranslation);
    Assert::assertInstanceOf(Translation::class, $homeTranslation);
    $layout = $installerHome->layout;
    Assert::assertInstanceOf(Layout::class, $layout);
    $layoutMeta = $layout->meta;
    Assert::assertIsArray($layoutMeta);
    Assert::assertArrayHasKey('header', $layoutMeta);
    Assert::assertArrayHasKey('footer', $layoutMeta);

    expect($installerTranslation->title)->toBe($homeTranslation->title)
        ->and($installerHome->meta)->toBe($home->meta)
        ->and($layoutMeta['header'])->toBeNull()
        ->and($layoutMeta['footer'])->toBeNull();
    $navigation = Navigation::query()->where('key', NavigationHandle::Footer->value)->firstOrFail();
    $items = $navigation->items;
    Assert::assertInstanceOf(DataCollection::class, $items);
    expect($items->toCollection()->pluck('label')->all())->toBe(['Explore', 'Contact']);
    $theme = $site->theme;
    Assert::assertInstanceOf(Theme::class, $theme);
    $theme->update(['meta' => [...($theme->meta ?? []), 'header' => true, 'footer' => true]]);
    $pageUrl = $installerHome->pageUrl;
    Assert::assertInstanceOf(PageUrl::class, $pageUrl);
    $response = $this->get($pageUrl->full_url)->assertOk();
    $html = $response->getContent();
    Assert::assertIsString($html);
    if ($html === '') {
        throw new RuntimeException('Expected rendered Foundation chrome HTML.');
    }
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $dom = new DOMXPath($document);
    expect($dom->query('//header'))->toHaveCount(1)
        ->and($dom->query('//footer//*[normalize-space(.)="Explore"]'))->not->toBeEmpty()
        ->and($dom->query('//footer//a[normalize-space(.)="How we work"]'))->toHaveCount(1)
        ->and($dom->query('//footer//*[normalize-space(.)="Welcome" or normalize-space(.)="Blog"]'))->toHaveCount(0);
    $response->assertDontSee('Welcome to Capell')->assertDontSee('fixture')
        ->assertDontSee('model_id')->assertDontSee('field_path');
});
