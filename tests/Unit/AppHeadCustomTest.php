<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\FoundationTheme\Actions\ResolveFoundationThemeTokensAction;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Settings\FoundationThemeSettingsMigrationProvider;
use Capell\Frontend\Facades\Frontend;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../Support/PublicChromeFixture.php';

it('emits safe theme colours and prepared design tokens in the public head', function (): void {
    $theme = (new Theme)->forceFill(['meta' => ['colors' => ['primary' => '#123456'], 'link_color' => '#345678']]);
    $site = (new Site)->forceFill(['meta' => ['brand_color' => '#abcdef']]);
    $tokens = ResolveFoundationThemeTokensAction::run(theme: $theme, site: $site, resolveSettings: false);
    Frontend::setFrontendData('foundation.theme.tokens', $tokens);
    $html = Blade::render('<x-capell::app.head.custom />');
    expect($tokens->brandColor)->toBe('rgb(171, 205, 239)')
        ->and($tokens->linkColor)->toBe('rgb(52, 86, 120)')
        ->and($html)->toMatch('/--color-brand\s*:\s*rgb\(171, 205, 239\)\s*;/')
        ->toMatch('/--color-link\s*:\s*rgb\(52, 86, 120\)\s*;/')
        ->toMatch('/--color-primary\s*:\s*rgb\(18, 52, 86\)\s*;/');
});

it('applies saved dark preference and updates sticky headers only on Livewire pages', function (bool $usesLivewire): void {
    Frontend::setFrontendData('runtimeManifest', (object) ['usesLivewire' => $usesLivewire]);
    $process = new Process(['node', dirname(__DIR__, 4) . '/tests/Fixtures/BrowserScripts/head.cjs']);
    $process->setInput(Blade::render('<x-capell::app.head.custom />'))->setTimeout(10)->mustRun();
    $states = capell_json_array($process->getOutput());
    expect(data_get($states, '0.initialDark'))->toBeTrue()
        ->and(data_get($states, '1.initialDark'))->toBeFalse()
        ->and(data_get($states, '0.scrolledSticky'))->toBe($usesLivewire)
        ->and(data_get($states, '1.scrolledSticky'))->toBe($usesLivewire)
        ->and(data_get($states, '0.navigatedDark'))->toBe(! $usesLivewire)
        ->and(data_get($states, '1.navigatedDark'))->toBe($usesLivewire)
        ->and(data_get($states, '0.resetSticky'))->toBeFalse()
        ->and(data_get($states, '1.resetSticky'))->toBeFalse();
})->with([false, true]);

it('renders structured data as inert, script-safe JSON', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::structured-data :schema="$schema" />',
        ['schema' => ['@context' => 'https://schema.org', '@type' => 'Thing', 'name' => '</script><img src=x onerror=alert(1)>']],
    );

    expect($html)
        ->toContain('type="application/ld+json"')
        ->toContain('https://schema.org')
        ->toContain('\\u003C/script\\u003E')
        ->not->toContain('</script><img');
});

it('maps design settings to public render tokens', function (): void {
    $settings = new FoundationThemeSettings;
    $settings->page_background_color = '#123456';
    $settings->dark_page_background_color = '#234567';
    $settings->dark_primary_action_color = '#345678';
    $settings->image_radius = '0.25rem';
    $settings->heading_scale = 'expressive';
    $settings->section_spacing = 'spacious';
    $settings->widget_gap = 'compact';
    $settings->responsive_repeatable_layout = 'desktop-grid-mobile-carousel';
    $tokens = ResolveFoundationThemeTokensAction::run(settings: $settings);
    expect($tokens->pageBackground)->toBe('rgb(18, 52, 86)')
        ->and($tokens->darkPageBackground)->toBe('rgb(35, 69, 103)')
        ->and($tokens->darkPrimaryAction)->toBe('rgb(52, 86, 120)')
        ->and($tokens->imageRadius)->toBe('0.25rem')
        ->and($tokens->headingSizeH1)->toBe(FoundationThemeSettings::HEADING_SCALE_OPTIONS['expressive']['h1'])
        ->and($tokens->sectionSpacing)->toBe(FoundationThemeSettings::SECTION_SPACING_OPTIONS['spacious'])
        ->and($tokens->widgetGap)->toBe(FoundationThemeSettings::WIDGET_GAP_OPTIONS['compact'])
        ->and($tokens->responsiveRepeatableLayout)->toBe('desktop-grid-mobile-carousel');
});

it('adds design settings idempotently without overwriting saved choices', function (string $migrationName, string $key, string $default): void {
    $provider = new FoundationThemeSettingsMigrationProvider;
    expect($provider->getSettingMigrations())->toContain($migrationName);
    $name = substr($key, strlen('theme_foundation.'));
    DB::table('settings')->where('group', 'theme_foundation')->where('name', $name)->delete();
    $migration = require dirname(__DIR__, 2) . '/database/settings/' . $migrationName . '.php';
    throw_unless($migration instanceof SettingsMigration, RuntimeException::class);
    $migration->up();
    $read = static function () use ($name): mixed {
        $payload = DB::table('settings')->where('group', 'theme_foundation')->where('name', $name)->value('payload');
        throw_unless(is_string($payload), RuntimeException::class, 'A migrated setting must have a JSON payload.');

        return json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
    };
    expect($read())->toBe($default);
    app(SettingsMigrator::class)->update($key, static fn (): string => 'Saved choice');
    $migration->up();
    expect($read())->toBe('Saved choice')
        ->and(DB::table('settings')->where('group', 'theme_foundation')->where('name', $name)->count())->toBe(1);
})->with([
    'dark background' => ['2026_06_07_000001_add_theme_foundation_dark_design_tokens', 'theme_foundation.dark_page_background_color', '#0f172a'],
    'dark action' => ['2026_06_07_000001_add_theme_foundation_dark_design_tokens', 'theme_foundation.dark_primary_action_color', '#93c5fd'],
    'dark image border' => ['2026_06_07_000001_add_theme_foundation_dark_design_tokens', 'theme_foundation.dark_image_border_color', '#334155'],
    'typography' => ['2026_06_07_000002_add_theme_foundation_typography_tokens', 'theme_foundation.heading_scale', 'balanced'],
    'repeatable layout' => ['2026_07_15_210000_add_theme_foundation_responsive_repeatable_layout', 'theme_foundation.responsive_repeatable_layout', 'desktop-grid-mobile-carousel'],
]);

it('emits document direction and logical shell utilities for RTL support', function (string $code, string $direction): void {
    \Capell\FoundationTheme\Tests\Support\preparePublicChrome();
    Frontend::language()?->forceFill(['code' => $code]);
    $shell = view()->file(dirname(__DIR__, 2) . '/resources/views/app.blade.php', [
        'layout' => new Layout(['key' => 'customer-page', 'meta' => []]),
        'theme' => new Theme(['meta' => []]),
        'slot' => new HtmlString('Customer page'),
    ])->render();
    $document = new RenderedHtml($shell);
    expect($document->element('//html')->getAttribute('lang'))->toBe($code)
        ->and($document->element('//html')->getAttribute('dir'))->toBe($direction);

    $page = view('capell-theme-foundation::theme.page', [
        'brand' => new BrandProfileData,
        'content' => new HtmlString('Customer content'),
    ])->render();
    $navigation = view('capell-theme-foundation::theme.sections.navigation', ['section' => (object) [
        'brandName' => 'Customer site', 'items' => [['label' => 'Stories', 'url' => '/stories']],
        'ctaLabel' => null, 'ctaUrl' => null,
    ]])->render();
    $listing = '';
    foreach (['spotlight', 'gallery'] as $variant) {
        $listing .= view('capell-theme-foundation::theme.sections.content-listing', ['section' => (object) [
            'variant' => $variant, 'heading' => 'Explore', 'summary' => '',
            'items' => [['title' => 'Stories', 'url' => '/stories', 'summary' => 'Customer stories']],
        ]])->render();
    }

    $pageDocument = new RenderedHtml($page);
    $navigationDocument = new RenderedHtml($navigation);
    $listingDocument = new RenderedHtml($listing);
    expect(explode(' ', $pageDocument->element('//a[@href="#main-content"]')->getAttribute('class')))->toContain('focus:start-4')
        ->and(explode(' ', $navigationDocument->element('//details/div')->getAttribute('class')))->toContain('end-0')
        ->not->toContain('right-0')
        ->and($listingDocument->count('//*[contains(concat(" ", normalize-space(@class), " "), " text-start ")]'))->toBeGreaterThan(0)
        ->and($listingDocument->count('//*[contains(concat(" ", normalize-space(@class), " "), " pe-4 ")]'))->toBeGreaterThan(0)
        ->and($listingDocument->count('//*[contains(concat(" ", normalize-space(@class), " "), " text-left ") or contains(concat(" ", normalize-space(@class), " "), " pr-4 ")]'))->toBe(0);
})->with(['English' => ['en', 'ltr'], 'Arabic' => ['ar', 'rtl']]);
