<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Feature;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\Packages\PackagesTestCase;
use Illuminate\Support\Facades\Blade;

final class RegisteredFoundationComponentsTest extends PackagesTestCase
{
    public function test_shared_body_preserves_the_frontend_null_theme_fallback(): void
    {
        $template = '<x-capell::app.body :layout="$layout" :language="$language" :page-record="$pageRecord" :site="$site" :theme="$theme">Fallback content</x-capell::app.body>';
        $data = [
            'layout' => new Layout(['key' => 'default']),
            'language' => null,
            'pageRecord' => null,
            'site' => new Site,
            'theme' => null,
        ];

        expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeFalse()
            ->and(app()->providerIsLoaded(LayoutBuilderServiceProvider::class))->toBeTrue()
            ->and(CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName))->toBeTrue();

        view()->addNamespace('frontend-fallback', dirname(__DIR__, 4) . '/vendor/capell-app/frontend/resources/views');
        Blade::component('frontend-fallback::components.app.body', 'capell::app.body');
        $this->blade($template, $data)->assertSee('Fallback content');

        (new FoundationThemeServiceProvider(app()))->packageBooted();
        $this->blade('<!-- Shared rendering -->' . $template, $data)->assertSee('site-app-body')->assertSee('Fallback content');
    }

    public function test_it_renders_registered_footer_social_links_with_hydrated_urls(): void
    {
        $this->blade('<x-capell::footer.social-links :links="$links" />', [
            'links' => [['title' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => null]],
        ])->assertElementExists('a[href="https://facebook.com"]');
    }

    public function test_it_renders_registered_actions_with_their_supplied_links(): void
    {
        $this->blade('<x-capell::actions :actions="$actions" />', [
            'actions' => [['type' => 'url', 'label' => 'Resources', 'url' => 'https://example.test/resources']],
        ])->assertElementExists('a[href="https://example.test/resources"]');
    }

    public function test_it_renders_the_registered_app_body_and_its_slot(): void
    {
        $this->blade('<x-capell::app.body :layout="$layout" :language="$language" :page-record="$pageRecord" :site="$site" :theme="$theme">Body content</x-capell::app.body>', [
            'layout' => new Layout(['key' => 'default']),
            'language' => null,
            'pageRecord' => null,
            'site' => new Site,
            'theme' => new Theme,
        ])->assertSee('site-app-body')->assertSee('Body content');
    }

    public function test_it_renders_registered_sanitised_svg_contents(): void
    {
        $path = sys_get_temp_dir() . '/foundation-svg-' . bin2hex(random_bytes(8)) . '.svg';

        try {
            file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><path d="M0 0 L10 10"/></svg>');
            $this->blade('<x-capell::media.svg :path="$path" />', ['path' => $path])->assertElementExists('svg path');
        } finally {
            unlink($path);
        }
    }
}
