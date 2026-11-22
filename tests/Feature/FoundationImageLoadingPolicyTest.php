<?php

declare(strict_types=1);

use Capell\Core\Data\Rendering\FrontendRuntimeManifestData;
use Capell\Core\Enums\Rendering\RenderingStrategyEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction;
use Capell\FoundationTheme\Actions\ResolveFoundationImageLoadingAction;
use Capell\FoundationTheme\Data\FoundationImageLoadingData;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Render\FoundationImageLoadingContributor;
use Capell\FoundationTheme\View\Components\Widget\FoundationSection;
use Capell\Frontend\Data\Assets\FrontendResourcePlanData;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\Frontend\Support\Render\PublicRenderDataScope;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

it('uses the saved loading preference for secondary images and preserves the hero priority', function (bool $enabled, string $loading): void {
    $settings = new FoundationThemeSettings;
    $settings->enable_lazy_loading = $enabled;

    app()->instance(FoundationThemeSettings::class, $settings);
    $widget = new Widget(['key' => 'capell.widget.foundation.section', 'meta' => [
        'type' => 'features', 'heading' => 'Capabilities', 'summary' => '',
        'features' => [['title' => 'Example', 'image' => '/media/example.jpg']],
    ]]);
    $component = new FoundationSection([], 'main', 0, (object) ['index' => 0], $widget);
    $view = $component->render();
    expect($view)->toBeInstanceOf(View::class);
    throw_unless($view instanceof View, RuntimeException::class, 'Expected a prepared Foundation section view.');
    $html = $view->render();
    expect($html)->toContain('loading="' . $loading . '"');

    $hero = view('capell-theme-foundation::theme.sections.hero', ['section' => (object) [
        'heading' => 'Welcome', 'summary' => '', 'eyebrow' => '', 'mediaUrl' => '/media/hero.jpg', 'mediaAlt' => 'Welcome', 'actions' => [],
    ]])->render();
    expect($hero)->toContain('loading="eager"', 'fetchpriority="high"');
})->with([[true, 'lazy'], [false, 'eager']]);

it('reloads saved loading preferences and safely retains legacy minification values', function (): void {
    InstalledRuntimeState::run(function (): void {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true, sourceRoot: dirname(__DIR__, 2));
        foreach (glob(dirname(__DIR__, 2) . '/database/settings/*.php') ?: [] as $path) {
            $migration = require $path;
            throw_unless($migration instanceof SettingsMigration, RuntimeException::class, 'Expected a real Foundation settings migration.');
            $migration->up();
        }

        app()->forgetInstance(FoundationThemeSettings::class);
        $settings = resolve(FoundationThemeSettings::class);
        $settings->enable_lazy_loading = false;
        $settings->minify_assets = false;
        $settings->save();

        app()->forgetInstance(FoundationThemeSettings::class);
        expect(ResolveFoundationImageLoadingAction::run())->toBe('eager')
            ->and(resolve(FoundationThemeSettings::class)->minify_assets)->toBeFalse();
        $settings = resolve(FoundationThemeSettings::class);
        $settings->enable_lazy_loading = true;
        $settings->save();

        app()->forgetInstance(FoundationThemeSettings::class);
        expect(ResolveFoundationImageLoadingAction::run())->toBe('lazy');
    });
});

it('changes cached public image output after a saved preference change while preserving hero priority', function (): void {
    config()->set('capell-frontend.public_render_data_cache', true);
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    $provider = new FoundationThemeServiceProvider(app());
    $provider->register();

    resolve(InstalledRuntimeLifecycle::class)->refresh(FoundationThemeServiceProvider::$packageName);
    foreach (glob(dirname(__DIR__, 2) . '/database/settings/*.php') ?: [] as $path) {
        $migration = require $path;
        throw_unless($migration instanceof SettingsMigration, RuntimeException::class, 'Expected a real Foundation settings migration.');
        $migration->up();
    }

    app()->forgetInstance(FoundationThemeSettings::class);
    InstallFoundationThemeDemoAction::run(new ThemeDemoInstallData(siteNames: ['Image policy'], languageCodes: ['en'], baseUrl: 'http://image-policy.test'));
    $page = Page::query()->where('meta->theme_demo->theme_key', 'default')->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->loadMissing(['layout', 'pageUrl.siteDomain']);

    $keys = [];
    foreach ($page->layout->containers ?? [] as $container) {
        foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $placement) {
            $keys[] = LayoutWidgetData::key($placement);
        }
    }

    foreach (Widget::query()->whereIn('key', $keys)->get() as $widget) {
        $meta = $widget->meta ?? [];
        if ($widget->getMetaComponent() === FoundationSectionWidgetComponentEnum::Features->value) {
            $meta['items'] = [['title' => 'Secondary image', 'image' => '/media/policy-secondary.jpg']];
            $widget->update(['meta' => $meta]);
        }

        if ($widget->getMetaComponent() === FoundationSectionWidgetComponentEnum::Hero->value) {
            $meta['mediaUrl'] = '/media/policy-hero.jpg';
            $meta['mediaAlt'] = 'Priority image';
            $widget->update(['meta' => $meta]);
        }
    }

    $url = $page->pageUrl?->full_url;
    throw_unless(is_string($url), RuntimeException::class, 'The real public page needs a URL.');
    foreach ([false, true, false] as $enabled) {
        $settings = resolve(FoundationThemeSettings::class);
        $settings->enable_lazy_loading = $enabled;
        $settings->save();
        app()->forgetInstance(FoundationThemeSettings::class);
        foreach ([1, 2] as $request) {
            $html = $this->get($url)->assertOk()->getContent();
            throw_unless(is_string($html), RuntimeException::class, 'Expected public page HTML.');
            $document = new RenderedHtml($html);
            expect($document->count('//img[@src="/media/policy-secondary.jpg"]'))->toBe(1)
                ->and($document->element('//img[@src="/media/policy-secondary.jpg"]')->getAttribute('loading'))->toBe($enabled ? 'lazy' : 'eager', 'Saved policy must affect public request ' . $request)
                ->and($document->count('//img[@src="/media/policy-hero.jpg"][@loading="eager"][@fetchpriority="high"]'))->toBe(1);
        }
    }
});

/**
 * Catches the public render reading the image-loading payload as an untyped
 * object: Core hands contributions over as plain objects, so the typed payload
 * must be rebuilt from that shape, and a misshapen value must stay lazy.
 */
it('reads secondary image loading only from the typed Foundation payload in public render data', function (object $payload, string $loading): void {
    $widget = new Widget(['key' => 'capell.widget.foundation.section', 'meta' => [
        'type' => 'features', 'heading' => 'Capabilities', 'summary' => '',
        'features' => [['title' => 'Example', 'image' => '/media/example.jpg']],
    ]]);
    $renderData = new PublicPageRenderData(
        layoutGraph: null,
        runtimeManifest: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        resourcePlan: new FrontendResourcePlanData([], [], [], [], [], [], [], ''),
        surrogateKeys: [],
        extensionData: [FoundationImageLoadingContributor::KEY => $payload],
    );
    $preventedLazyLoading = Model::preventsLazyLoading();
    Model::preventLazyLoading();
    // Core settings may load during the render; the Foundation settings must not.
    $foundationSettingsQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$foundationSettingsQueries): void {
        if (in_array(FoundationThemeSettings::group(), $query->bindings, true)) {
            $foundationSettingsQueries++;
        }
    });

    try {
        $html = PublicRenderDataScope::during($renderData, function () use ($widget): string {
            $view = new FoundationSection([], 'main', 0, (object) ['index' => 0], $widget)->render();
            throw_unless($view instanceof View, RuntimeException::class, 'Expected a prepared Foundation section view.');

            return $view->render();
        });
    } finally {
        Model::preventLazyLoading($preventedLazyLoading);
    }

    $document = new RenderedHtml($html);
    expect($document->element('//img[@src="/media/example.jpg"]')->getAttribute('loading'))->toBe($loading)
        ->and($foundationSettingsQueries)->toBe(0);
})->with([
    'typed eager payload' => [new FoundationImageLoadingData('eager'), 'eager'],
    'typed lazy payload' => [new FoundationImageLoadingData('lazy'), 'lazy'],
    'normalised eager payload' => [(object) ['loading' => 'eager'], 'eager'],
    'unknown loading value' => [(object) ['loading' => 'auto'], 'lazy'],
    'non-string loading value' => [(object) ['loading' => ['eager']], 'lazy'],
]);
