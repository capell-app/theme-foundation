<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Feature;

use Capell\Admin\Actions\Extensions\ResolveExtensionAssetPathAction;
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\AIOrchestrator\Providers\AIOrchestratorServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Settings\CoreSettings;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\Frontend\Contracts\FrontendOutputCacheInvalidator;
use Capell\HtmlCache\Actions\BuildHtmlCacheEligibilityReportAction;
use Capell\HtmlCache\Actions\PurgeEdgeCacheAction;
use Capell\HtmlCache\Data\EdgeCachePurgeData;
use Capell\HtmlCache\Support\Cache\HtmlCacheStore;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

#[Group('theme-foundation')]
#[Group('package')]
#[Group('feature')]
final class FoundationFullPageCacheTest extends PackagesTestCase
{
    use CreatesAdminUser;

    public function test_saved_loading_policy_invalidates_warm_full_page_html(): void
    {
        // The Extensions screen decodes every marketplace screenshot. Thumbnails are
        // incidental to the real settings form/save and have a separate asset contract.
        app()->instance(ResolveExtensionAssetPathAction::class, Mockery::mock(new ResolveExtensionAssetPathAction)->shouldReceive('handle')->andReturn(null)->getMock());
        CapellCore::forcePackageInstalled('capell-app/seo-suite', false);
        config()->set('capell-frontend.public_render_data_cache', true);
        config()->set('capell-html-cache.enabled', true);
        config()->set('capell-html-cache.write_enabled', true);
        config()->set('capell-html-cache.cache_ttl', '3600');
        config()->set('capell-html-cache.filesystem_ttl_seconds', 3600);
        Storage::fake('page_cache');
        app()->forgetInstance(HtmlCacheStore::class);
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
        Queue::fake();
        foreach ([false, true, false] as $enabled) {
            $this->actingAsAdmin();
            Livewire::withQueryParams(['manage' => 'capell-app/theme-foundation', 'surface' => 'theme_foundation'])
                ->test(ExtensionsPage::class)
                ->assertSuccessful()
                ->setActionData(['enable_lazy_loading' => $enabled])
                ->callMountedAction()->assertHasNoActionErrors();
            auth()->logout();
            app()->forgetInstance(FoundationThemeSettings::class);
            expect(resolve(FoundationThemeSettings::class)->enable_lazy_loading)->toBe($enabled);
            app()->forgetInstance(FoundationThemeSettings::class);
            foreach ([1, 2] as $request) {
                $response = $this->get($url)->assertOk();
                expect($response->headers->get('X-Frontend-Cache'))->toBe($request === 1 ? 'MISS' : 'HIT');
                $html = $response->getContent();
                throw_unless(is_string($html), RuntimeException::class, 'Expected public page HTML.');
                expect(Storage::disk('page_cache')->allFiles())->not->toBeEmpty('Anonymous page must really warm the full-page HTML cache: ' . json_encode(BuildHtmlCacheEligibilityReportAction::run(request(), response($html, 200, ['Content-Type' => 'text/html']))->toArray()));
                $document = new RenderedHtml($html);
                expect($document->count('//img[@src="/media/policy-secondary.jpg"]'))->toBe(1)
                    ->and($document->element('//img[@src="/media/policy-secondary.jpg"]')->getAttribute('loading'))->toBe($enabled ? 'lazy' : 'eager', 'Saved policy must affect public request ' . $request)
                    ->and($document->count('//img[@src="/media/policy-hero.jpg"][@loading="eager"][@fetchpriority="high"]'))->toBe(1);
            }
        }
        Queue::assertPushed(JobDecorator::class, static function (JobDecorator $job): bool {
            $purge = $job->getParameters()[0] ?? null;

            return $job->getAction() instanceof PurgeEdgeCacheAction
                && $purge instanceof EdgeCachePurgeData && $purge->purgeAll;
        });
    }

    public function test_only_committed_foundation_settings_invalidate_generated_output(): void
    {
        CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
        $provider = new FoundationThemeServiceProvider(app());
        $provider->register();
        resolve(InstalledRuntimeLifecycle::class)->refresh(FoundationThemeServiceProvider::$packageName);
        foreach (glob(dirname(__DIR__, 2) . '/database/settings/*.php') ?: [] as $path) {
            $migration = require $path;
            throw_unless($migration instanceof SettingsMigration, RuntimeException::class);
            $migration->up();
        }
        app()->forgetInstance(FoundationThemeSettings::class);
        $invalidator = Mockery::spy(FrontendOutputCacheInvalidator::class);
        app()->instance(FrontendOutputCacheInvalidator::class, $invalidator);
        DB::beginTransaction();
        resolve(FoundationThemeSettings::class)->save();
        $invalidator->shouldNotHaveReceived('invalidateAll');
        DB::rollBack();
        $invalidator->shouldNotHaveReceived('invalidateAll');
        DB::transaction(static function (): void {
            resolve(FoundationThemeSettings::class)->save();
        });
        $invalidator->shouldHaveReceived('invalidateAll')->once();
        resolve(CoreSettings::class)->save();
        $invalidator->shouldHaveReceived('invalidateAll')->once();
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        // SEO's dynamic head hook opts out of shared HTML caching.
        return array_values(array_filter(parent::getPackageProviders($app), static fn (string $provider): bool => ! in_array($provider, [SeoSuiteServiceProvider::class, AIOrchestratorServiceProvider::class], true)));
    }
}
