<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\Diagnostics\DoctorCheckOutcome;
use Capell\Core\Events\FrontendSurrogateKeysInvalidated;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Core\Support\TableName;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Actions\MigrateSitesToThemeAction;
use Capell\FoundationTheme\Actions\PlanThemeMigrationAction;
use Capell\FoundationTheme\Actions\RefreshMigratedThemeOutputAction;
use Capell\FoundationTheme\Health\FoundationThemeHealthCheck;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Themes\RetiredThemeReferenceScanner;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSectionMap;
use Capell\FoundationTheme\Support\Themes\RetiredThemeSuccessors;
use Capell\FoundationTheme\Support\Themes\ThemeStudioActiveTheme;
use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Models\Widget;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

use function Pest\Laravel\get;

use Symfony\Component\Finder\SplFileInfo;

uses(TestingFrontend::class);

/*
 * Seven themes were withdrawn from sale and merged into a successor. Sites
 * already built on one keep rendering, and `capell:theme-migrate` moves them
 * onto the successor without restyling sites already on it, leaving a report
 * that moves exactly those sites back.
 */
/**
 * @param  list<string>  $except  theme keys to leave unregistered, as if their package were removed
 */
function retiredMigrationRegisterThemes(array $except = []): void
{
    $registry = resolve(ThemeRegistry::class);
    $keys = array_unique([...array_keys(resolve(RetiredThemeSuccessors::class)->all()), ...array_values(resolve(RetiredThemeSuccessors::class)->all())]);

    // Minimal fixtures: each theme renders through Foundation and only
    // differs by key and presets.
    $foundation = FoundationThemeServiceProvider::definition();

    if (! $registry->has($foundation->key)) {
        $registry->register($foundation);
    }

    foreach (array_diff($keys, $except) as $key) {
        if (! $registry->has($key)) {
            $registry->register(new ThemeDefinitionData(
                key: $key,
                name: ucfirst($key),
                description: 'Fixture theme.',
                package: $foundation->package,
                previewImage: '',
                tags: [],
                bestFit: [],
                presets: [
                    new ThemePresetData(key: $key . '-first', name: 'First', description: 'First preset.', previewImage: ''),
                    new ThemePresetData(key: $key . '-second', name: 'Second', description: 'Second preset.', previewImage: ''),
                ],
            ));
        }
    }

}

beforeEach(function (): void {
    retiredMigrationRegisterThemes();

    File::deleteDirectory(storage_path('app/capell/theme-migrations'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/capell/theme-migrations'));
});

dataset('retired themes', [
    'magazine into editorial' => ['magazine', 'editorial'],
    'showreel into portfolio' => ['showreel', 'portfolio'],
    'submissions into portfolio' => ['submissions', 'portfolio'],
    'curated into portfolio' => ['curated', 'portfolio'],
    'saas into portfolio' => ['saas', 'portfolio'],
    'agency into portfolio' => ['agency', 'portfolio'],
    'onepage into portfolio' => ['onepage', 'portfolio'],
    'paperdesk into portfolio' => ['paperdesk', 'portfolio'],
    'catalogue into portfolio' => ['catalogue', 'portfolio'],
    'directory into portfolio' => ['directory', 'portfolio'],
    'minimalist into portfolio' => ['minimalist', 'portfolio'],
    'brutalist into portfolio' => ['brutalist', 'portfolio'],
    'awards into portfolio' => ['awards', 'portfolio'],
    'blog into editorial' => ['blog', 'editorial'],
    'broadsheet into editorial' => ['broadsheet', 'editorial'],
    'folio into editorial' => ['folio', 'editorial'],
    'bistro into business' => ['bistro', 'business'],
    'events into business' => ['events', 'business'],
    'grounds into business' => ['grounds', 'business'],
    'almanac into business' => ['almanac', 'business'],
    'knowledge into platform' => ['knowledge', 'platform'],
    'switchboard into platform' => ['switchboard', 'platform'],
    'liquid-glass into platform' => ['liquid-glass', 'platform'],
]);

/**
 * @param  array<string, mixed>  $meta
 */
function retiredMigrationTheme(string $key, array $meta = [], bool $status = true): Theme
{
    return Theme::factory()->defaultMeta()->create([
        'key' => $key,
        'name' => ucfirst($key),
        'status' => $status,
        // Foundation's stylesheet is served from the application build.
        'meta' => [...Theme::factory()->defaultMeta()->make()->meta, 'footer' => false, 'assets_path' => 'build', 'assets' => [], ...$meta],
    ]);
}

/**
 * @return array{site: Site, page: Page}
 */
function retiredMigrationSite(Theme $theme, string $name): array
{
    $site = Site::factory()->withTranslations()->create(['name' => $name, 'theme_id' => $theme->id]);
    $page = Page::factory()->site($site)->home()->withTranslations()->create();

    return ['site' => $site, 'page' => $page];
}

function retiredMigrationUrl(Page $page): string
{
    $url = $page->refresh()->pageUrl?->full_url;

    throw_unless(is_string($url) && $url !== '', RuntimeException::class, 'Expected the page to have a public URL.');

    return $url;
}

/**
 * @return array<string, mixed>
 */
function retiredMigrationReportData(): array
{
    $report = json_decode(File::get(retiredMigrationReport()), true, 64, JSON_THROW_ON_ERROR);

    throw_unless(is_array($report), RuntimeException::class, 'Expected the rollback report to be a JSON object.');

    /** @var array<string, mixed> $report */
    return $report;
}

function retiredMigrationReport(): string
{
    $reports = File::glob(storage_path('app/capell/theme-migrations/*.json'));

    expect($reports)->toHaveCount(1);

    return $reports[0];
}

it('keeps a retired-theme site rendering, previews by default, moves it with --force and moves it back', function (string $retired, string $successor): void {
    $tokens = ['primary' => '#123456', 'heading_font' => 'fraunces'];
    $retiredTheme = retiredMigrationTheme($retired, ['editor' => ['tokens' => $tokens], 'active_preset' => $retired . '-second']);
    ['site' => $site, 'page' => $page] = retiredMigrationSite($retiredTheme, 'Retired theme site');

    get(retiredMigrationUrl($page))->assertOk();

    expect(Artisan::call('capell:theme-migrate', ['from' => $retired, 'to' => $successor]))->toBe(0);
    $preview = Artisan::output();

    expect($preview)->toContain('Site ' . $site->id . ': Retired theme site')
        ->toContain('--force')
        ->and($site->refresh()->theme_id)->toBe($retiredTheme->id)
        ->and(Theme::query()->where('key', $successor)->exists())->toBeFalse()
        ->and(File::glob(storage_path('app/capell/theme-migrations/*.json')))->toBe([]);

    expect(Artisan::call('capell:theme-migrate', ['from' => $retired, 'to' => $successor, '--force' => true]))->toBe(0);

    $migrated = $site->refresh()->theme;

    throw_unless($migrated instanceof Theme, RuntimeException::class, 'Expected the site to have a theme.');

    expect($migrated->key)->toBe($successor)
        ->and($migrated->status)->toBeTrue()
        ->and(data_get($migrated->meta, 'editor.preset.active'))->toBe($successor . '-first')
        ->and(data_get($migrated->meta, 'active_preset'))->toBe($successor . '-first')
        ->and(data_get($migrated->meta, 'editor.tokens'))->toBe($tokens)
        ->and(Theme::query()->whereKey($retiredTheme->id)->exists())->toBeTrue();

    get(retiredMigrationUrl($page))->assertOk();

    $report = retiredMigrationReportData();

    expect(data_get($report, 'from.theme_id'))->toBe($retiredTheme->id)
        ->and(data_get($report, 'to.theme_id'))->toBe($migrated->id)
        ->and($report['sites'] ?? null)->toBe([['id' => $site->id, 'name' => 'Retired theme site']]);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->toBe($retiredTheme->id);

    get(retiredMigrationUrl($page))->assertOk();
})->with('retired themes');

it('refuses to move a theme that is not retired or to a theme that is not its successor', function (): void {
    $editorial = retiredMigrationTheme('editorial');
    ['site' => $editorialSite] = retiredMigrationSite($editorial, 'Editorial site');
    $saas = retiredMigrationTheme('saas');
    ['site' => $saasSite] = retiredMigrationSite($saas, 'SaaS site');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'editorial', 'to' => 'awards', '--force' => true]))->toBe(1)
        ->and(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'catalogue', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('portfolio')
        ->and($editorialSite->refresh()->theme_id)->toBe($editorial->id)
        ->and($saasSite->refresh()->theme_id)->toBe($saas->id);
});

it('refuses a preset the successor theme does not have', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--preset' => 'no-such-preset', '--force' => true]))->toBe(1)
        ->and($site->refresh()->theme_id)->toBe($saas->id);
});

it('never restyles sites already on a used and customised successor', function (): void {
    $portfolioMeta = ['editor' => ['tokens' => ['primary' => '#000000', 'radius' => 'none'], 'preset' => ['active' => 'portfolio-second']], 'active_preset' => 'portfolio-second'];
    $portfolio = retiredMigrationTheme('portfolio', $portfolioMeta);
    ['site' => $existing] = retiredMigrationSite($portfolio, 'Existing portfolio site');
    $agency = retiredMigrationTheme('agency', ['editor' => ['tokens' => ['primary' => '#ff0000', 'radius' => 'none', 'body_font' => 'inter']]]);
    ['site' => $agencySite] = retiredMigrationSite($agency, 'Agency site');
    $onepage = retiredMigrationTheme('onepage', ['editor' => ['tokens' => ['primary' => '#00ff00']]]);
    ['site' => $onepageSite] = retiredMigrationSite($onepage, 'One-page site');
    $meta = $portfolio->refresh()->meta;

    // Without an explicit preset, with a different preset, or without the
    // confirmation flag, nothing moves.
    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true, '--allow-shared-successor' => true]))->toBe(1)
        ->and(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--preset' => 'portfolio-first', '--force' => true, '--allow-shared-successor' => true]))->toBe(1)
        ->and(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--preset' => 'portfolio-second', '--force' => true]))->toBe(1)
        ->and($agencySite->refresh()->theme_id)->toBe($agency->id);

    // The preview lists every token that differs.
    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--preset' => 'portfolio-second', '--allow-shared-successor' => true]);

    expect(Artisan::output())->toContain('Design token body_font differs')
        ->toContain('Design token primary differs')
        ->not->toContain('Design token radius differs');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--preset' => 'portfolio-second', '--allow-shared-successor' => true, '--force' => true]))->toBe(0)
        ->and(Artisan::call('capell:theme-migrate', ['from' => 'onepage', 'to' => 'portfolio', '--preset' => 'portfolio-second', '--allow-shared-successor' => true, '--force' => true]))->toBe(0);

    expect($portfolio->refresh()->meta)->toBe($meta)
        ->and($agencySite->refresh()->theme_id)->toBe($portfolio->id)
        ->and($onepageSite->refresh()->theme_id)->toBe($portfolio->id)
        ->and($existing->refresh()->theme_id)->toBe($portfolio->id);
});

it('moves back only the sites the migration moved', function (): void {
    $portfolio = retiredMigrationTheme('portfolio', ['active_preset' => 'portfolio-first', 'editor' => ['preset' => ['active' => 'portfolio-first']]]);
    ['site' => $existing] = retiredMigrationSite($portfolio, 'Existing portfolio site');
    $agency = retiredMigrationTheme('agency');
    ['site' => $agencySite] = retiredMigrationSite($agency, 'Agency site');

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--preset' => 'portfolio-first', '--allow-shared-successor' => true, '--force' => true]);

    // A site moved onto the successor afterwards, by hand, stays where it is.
    ['site' => $later] = retiredMigrationSite($portfolio, 'Later portfolio site');

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport()]))->toBe(0)
        ->and($agencySite->refresh()->theme_id)->toBe($portfolio->id);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($agencySite->refresh()->theme_id)->toBe($agency->id)
        ->and($existing->refresh()->theme_id)->toBe($portfolio->id)
        ->and($later->refresh()->theme_id)->toBe($portfolio->id);
});

it('restores the successor record on rollback only when nobody has edited it since', function (): void {
    $agency = retiredMigrationTheme('agency', ['editor' => ['tokens' => ['primary' => '#ff0000']]]);
    retiredMigrationSite($agency, 'Agency site');
    $portfolio = retiredMigrationTheme('portfolio', status: false);
    $previous = $portfolio->meta;

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]);

    expect($portfolio->refresh()->status)->toBeTrue();

    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect($portfolio->refresh()->meta)->toEqual($previous)
        ->and($portfolio->status)->toBeFalse();

    // Edited after the migration: the edit is kept.
    File::deleteDirectory(storage_path('app/capell/theme-migrations'));
    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]);
    $edited = is_array($portfolio->refresh()->meta) ? $portfolio->meta : [];
    data_set($edited, 'editor.tokens.primary', '#abcdef');
    $portfolio->forceFill(['meta' => $edited])->save();

    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect(Artisan::output())->toContain('edited after the migration')
        ->and($portfolio->refresh()->meta)->toEqual($edited);
});

it('reports customisations that are not carried across', function (): void {
    $saas = retiredMigrationTheme('saas', ['header_position' => 'sticky', 'dark_mode_toggle' => true]);
    $saas->forceFill(['custom_css' => '.hero { color: red; }'])->save();
    retiredMigrationSite($saas, 'SaaS site');

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('Not carried across: custom_css')
        ->toContain('Not carried across: meta:header_position')
        ->toContain('Not carried across: meta:dark_mode_toggle');
});

it('purges caches only after the migration commits', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    $baseLevel = DB::transactionLevel();
    $levels = [];

    Event::listen(FrontendSurrogateKeysInvalidated::class, function (FrontendSurrogateKeysInvalidated $event) use (&$levels, $site): void {
        if (in_array('site-' . $site->id, $event->surrogateKeys, true)) {
            $levels[] = DB::transactionLevel();
        }
    });

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);

    expect($levels)->not->toBeEmpty()
        ->and(array_unique($levels))->toBe([$baseLevel]);
});

it('refuses to run while another theme migration holds the lock', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    $lock = Cache::lock(MigrateSitesToThemeAction::LOCK_KEY, 60);
    $lock->get();

    try {
        expect(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]))->toBe(1)
            ->and($site->refresh()->theme_id)->toBe($saas->id);
    } finally {
        $lock->release();
    }
});

it('warns while sites remain on an installed retired theme and fails once its package is gone', function (): void {
    $check = new FoundationThemeHealthCheck;

    expect($check->retiredThemeSitesCheck()->outcome())->toBe(DoctorCheckOutcome::Passed);

    $saas = retiredMigrationTheme('saas');
    retiredMigrationSite($saas, 'SaaS site');

    $installed = $check->retiredThemeSitesCheck();

    expect($installed->passed)->toBeTrue()
        ->and($installed->outcome())->toBe(DoctorCheckOutcome::Warning)
        ->and($installed->evidence['sites_by_retired_theme'])->toBe(['saas' => 1]);

    resolve(ThemeRegistry::class)->reset();

    $removed = $check->retiredThemeSitesCheck();

    expect($removed->passed)->toBeFalse()
        ->and($removed->isCriticalFailure())->toBeTrue()
        ->and($removed->message)->toContain('saas');
});

it('keeps a successor other sites still use when rolling back the migration that created it', function (): void {
    $agency = retiredMigrationTheme('agency', ['editor' => ['tokens' => ['primary' => '#ff0000']]]);
    ['site' => $agencySite] = retiredMigrationSite($agency, 'Agency site');
    $onepage = retiredMigrationTheme('onepage');
    ['site' => $onepageSite] = retiredMigrationSite($onepage, 'One-page site');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0);
    $agencyReport = retiredMigrationReport();
    $portfolio = Theme::query()->where('key', 'portfolio')->firstOrFail();

    expect(Artisan::call('capell:theme-migrate', ['from' => 'onepage', 'to' => 'portfolio', '--preset' => 'portfolio-first', '--allow-shared-successor' => true, '--force' => true]))->toBe(0);
    $meta = $portfolio->refresh()->meta;

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => $agencyReport, '--force' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('still used by other sites')
        ->and($agencySite->refresh()->theme_id)->toBe($agency->id)
        ->and($onepageSite->refresh()->theme_id)->toBe($portfolio->id)
        ->and($portfolio->refresh()->meta)->toBe($meta)
        ->and(data_get($portfolio->meta, 'editor.tokens.primary'))->toBe('#ff0000')
        ->and($portfolio->status)->toBeTrue();
});

it('refuses to roll back onto a retired theme whose package has been removed', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    $catalogueId = $site->refresh()->theme_id;

    resolve(ThemeRegistry::class)->reset();
    retiredMigrationRegisterThemes(except: ['saas']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('no longer installed')
        ->and($site->refresh()->theme_id)->toBe($catalogueId)
        ->and(data_get(retiredMigrationReportData(), 'rolled_back_at'))->toBeNull();
});

it('refuses a rollback report whose themes do not match this installation', function (string $path, mixed $value): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    $editorial = retiredMigrationTheme('editorial');
    $editorialMeta = $editorial->meta;
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    $catalogueId = $site->refresh()->theme_id;

    $report = retiredMigrationReportData();
    data_set($report, $path, $value === 'editorial-id' ? $editorial->id : $value);
    File::put(retiredMigrationReport(), json_encode($report, JSON_THROW_ON_ERROR));

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('does not match')
        ->and($site->refresh()->theme_id)->toBe($catalogueId)
        ->and($editorial->refresh()->meta)->toEqual($editorialMeta);
})->with([
    'retired theme id points at another theme' => ['from.theme_id', 'editorial-id'],
    'successor theme id points at another theme' => ['to.theme_id', 'editorial-id'],
    'retired theme key is not the one the successor replaces' => ['from.key', 'agency'],
]);

it('uses a rollback report only once, so an old report cannot undo a later migration', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    $firstReport = retiredMigrationReport();

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => $firstReport, '--force' => true]))->toBe(0)
        ->and(data_get(retiredMigrationReportData(), 'rolled_back_at'))->not->toBeNull()
        ->and(Artisan::call('capell:theme-migrate', ['--rollback' => $firstReport, '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('already been used');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]))->toBe(0);
    $catalogueId = $site->refresh()->theme_id;

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => $firstReport, '--force' => true]))->toBe(1)
        ->and($site->refresh()->theme_id)->toBe($catalogueId);
});

it('keeps a default theme chosen after the migration when rolling back', function (): void {
    $saas = retiredMigrationTheme('saas');
    $saas->forceFill(['default' => true])->save();
    retiredMigrationSite($saas, 'SaaS site');
    $editorial = retiredMigrationTheme('editorial');

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    $catalogue = Theme::query()->where('key', 'portfolio')->firstOrFail();

    expect($catalogue->default)->toBeTrue()
        ->and($saas->refresh()->default)->toBeFalse();

    $catalogue->forceFill(['default' => false])->save();
    $editorial->forceFill(['default' => true])->save();

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('current default was kept')
        ->and($editorial->refresh()->default)->toBeTrue()
        ->and($saas->refresh()->default)->toBeFalse();
});

it('moves the default flag back when the successor is still the default', function (): void {
    $saas = retiredMigrationTheme('saas');
    $saas->forceFill(['default' => true])->save();
    retiredMigrationSite($saas, 'SaaS site');

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect($saas->refresh()->default)->toBeTrue()
        ->and(Theme::query()->where('key', 'portfolio')->firstOrFail()->default)->toBeFalse();
});

it('moves soft-deleted sites too and counts them in the doctor check', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'Archived SaaS site');
    $site->delete();

    expect((new FoundationThemeHealthCheck)->retiredThemeSitesCheck()->evidence['sites_by_retired_theme'])->toBe(['saas' => 1]);

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);

    expect(Site::withTrashed()->findOrFail($site->id)->theme_id)->toBe(Theme::query()->where('key', 'portfolio')->firstOrFail()->id)
        ->and((new FoundationThemeHealthCheck)->retiredThemeSitesCheck()->evidence['sites_by_retired_theme'])->toBe([]);
});

it('warns in the preview that a disabled successor record will be enabled', function (): void {
    retiredMigrationTheme('portfolio', status: false);
    retiredMigrationSite(retiredMigrationTheme('saas'), 'SaaS site');

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('record is disabled');
});

it('moves layouts that name the retired theme, so their pages render the successor, and moves them back', function (): void {
    $agency = retiredMigrationTheme('agency', ['main_class' => 'retired-agency-marker']);
    $portfolio = retiredMigrationTheme('portfolio', ['main_class' => 'successor-portfolio-marker']);
    $editorial = retiredMigrationTheme('editorial');
    $site = Site::factory()->withTranslations()->create(['name' => 'Editorial site', 'theme_id' => $editorial->id]);
    $layout = Layout::factory()->site($site)->create(['name' => 'Agency layout', 'theme_id' => $agency->id]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get(retiredMigrationUrl($page))->assertOk()->assertSee('retired-agency-marker', false);

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('Layout ' . $layout->id . ': Agency layout');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($layout->refresh()->theme_id)->toBe($portfolio->id)
        ->and($site->refresh()->theme_id)->toBe($editorial->id)
        ->and(data_get(retiredMigrationReportData(), 'layouts'))->toBe([['id' => $layout->id, 'name' => 'Agency layout']]);

    get(retiredMigrationUrl($page))->assertOk()->assertSee('successor-portfolio-marker', false)->assertDontSee('retired-agency-marker', false);

    // A layout moved onto the successor by hand afterwards stays there.
    $later = Layout::factory()->site($site)->create(['name' => 'Later layout', 'theme_id' => $portfolio->id]);

    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport()]);

    expect(Artisan::output())->toContain('Layout ' . $layout->id . ': Agency layout')
        ->not->toContain('Later layout');

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($layout->refresh()->theme_id)->toBe($agency->id)
        ->and($later->refresh()->theme_id)->toBe($portfolio->id);

    get(retiredMigrationUrl($page))->assertOk()->assertSee('retired-agency-marker', false);
});

it('counts layouts on a retired theme in the doctor check', function (): void {
    $saas = retiredMigrationTheme('saas');
    $site = Site::factory()->withTranslations()->create(['theme_id' => retiredMigrationTheme('editorial')->id]);
    Layout::factory()->site($site)->create(['theme_id' => $saas->id]);

    $check = (new FoundationThemeHealthCheck)->retiredThemeSitesCheck();

    expect($check->outcome())->toBe(DoctorCheckOutcome::Warning)
        ->and($check->evidence['layouts_by_retired_theme'])->toBe(['saas' => 1])
        ->and($check->evidence['sites_by_retired_theme'])->toBe([]);
});

it('switches a Theme Studio active theme that names the retired theme, and switches it back', function (): void {
    $settings = resolve(ThemeStudioActiveTheme::class);
    $settings->switchTo('saas', 'saas-first');
    retiredMigrationSite(retiredMigrationTheme('saas'), 'SaaS site');

    expect((new FoundationThemeHealthCheck)->retiredThemeSitesCheck()->evidence['theme_studio_active_retired_theme'])->toBe('saas');

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('Theme Studio active theme is saas')
        ->and($settings->current())->toBe(['theme' => 'saas', 'preset' => 'saas-first']);

    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);

    expect($settings->current())->toBe(['theme' => 'portfolio', 'preset' => 'portfolio-first']);

    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect($settings->current())->toBe(['theme' => 'saas', 'preset' => 'saas-first']);
});

it('refuses a rollback report written on another installation', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    $catalogueId = $site->refresh()->theme_id;

    // A cloned environment keeps the theme ids and keys but not the app key.
    config(['app.key' => 'base64:' . base64_encode(str_repeat('c', 32))]);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport()]))->toBe(1)
        ->and(Artisan::output())->toContain('another environment')
        ->and(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(1)
        ->and($site->refresh()->theme_id)->toBe($catalogueId);
});

it('validates a rollback report before previewing it', function (): void {
    retiredMigrationSite(retiredMigrationTheme('saas'), 'SaaS site');
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);
    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport()]))->toBe(1)
        ->and(Artisan::output())->toContain('already been used');

    File::put(retiredMigrationReport(), '{not json');

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport()]))->toBe(1)
        ->and(Artisan::output())->toContain('is not a theme migration rollback report');
});

it('marks a report used as soon as the rollback commits, even if refreshing caches then fails', function (): void {
    $saas = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($saas, 'SaaS site');
    Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]);

    RefreshMigratedThemeOutputAction::mock()->shouldReceive('handle')->andThrow(new RuntimeException('CDN unavailable'));

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(1)
        ->and($site->refresh()->theme_id)->toBe($saas->id)
        ->and(data_get(retiredMigrationReportData(), 'rolled_back_at'))->not->toBeNull();
});

/**
 * Registers fixture section renderables that print their own key, as a theme
 * package's provider registers `capell.widget.<theme>.<section>`.
 *
 * @param  list<string>  $keys
 */
function retiredMigrationRenderables(array $keys): void
{
    $directory = storage_path('framework/testing/retired-theme-renderables');
    File::ensureDirectoryExists($directory);
    Blade::anonymousComponentPath($directory, 'retired-fixture');

    foreach ($keys as $key) {
        $view = str_replace('.', '-', $key);
        File::put($directory . '/' . $view . '.blade.php', '<p class="fixture-renderable">rendered-' . $view . '</p>');
        resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: $key, type: 'layout-widget', blade: 'retired-fixture::' . $view));
    }
}

/**
 * Rebuilds the renderable registry without the given theme's renderables, as
 * if its package had been removed.
 */
function retiredMigrationRemoveRenderables(string $themeKey): void
{
    $remaining = new RenderableRegistry;

    foreach (resolve(RenderableRegistry::class)->all() as $definitions) {
        foreach ($definitions as $key => $definition) {
            if (! str_starts_with($key, RetiredThemeReferenceScanner::renderablePrefix($themeKey))) {
                $remaining->register($definition);
            }
        }
    }

    app()->instance(RenderableRegistry::class, $remaining);
}

/**
 * A section widget naming its renderable in the component column and, as rows
 * written before the column existed do, in meta.component.
 */
function retiredMigrationSectionWidget(string $key, string $component): Widget
{
    $widget = Widget::factory()->create(['key' => $key, 'name' => ucfirst($key), 'component' => $component]);
    Widget::withTrashed()->whereKey($widget->id)->toBase()->update(['meta' => json_encode(['component' => $component])]);

    return $widget->refresh();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function retiredMigrationSnapshot(array $attributes): int
{
    return (int) DB::table(TableName::resolve('public_widget_snapshots'))->insertGetId([
        'pageable_type' => new Page()->getMorphClass(),
        'pageable_id' => 1,
        'render_profile' => 'blade',
        'owner_revision' => 'revision',
        'context_fingerprint' => fake()->sha256(),
        'current_key' => fake()->sha256(),
        'target_instance_id' => fake()->uuid(),
        'widget_key' => 'fixture',
        'definition_state_version' => 1,
        'encrypted_payload' => 'payload',
        'created_at' => now(),
        'updated_at' => now(),
        ...$attributes,
    ]);
}

function retiredMigrationSnapshotRevoked(int $id): bool
{
    return DB::table(TableName::resolve('public_widget_snapshots'))->where('id', $id)->value('revoked_at') !== null;
}

it('remaps theme section widgets onto the successor, renders after the retired renderables are gone, and restores them on rollback', function (): void {
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.portfolio.hero']);
    $agency = retiredMigrationTheme('agency');
    $portfolio = retiredMigrationTheme('portfolio');
    $site = Site::factory()->withTranslations()->create(['name' => 'Agency site', 'theme_id' => $agency->id]);
    $hero = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    $edited = retiredMigrationSectionWidget('agency-hero-edited', 'capell.widget.agency.hero');
    $layout = Layout::factory()->site($site)->create([
        'name' => 'Agency layout',
        'containers' => ['main' => ['label' => 'Main', 'widgets' => [['widget_key' => 'agency-hero', 'occurrence' => 1]]]],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get(retiredMigrationUrl($page))->assertOk()->assertSee('rendered-capell-widget-agency-hero', false);

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('Widget ' . $hero->id . ': Agency-hero (capell.widget.agency.hero -> capell.widget.portfolio.hero)');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('package can be removed')
        ->and($hero->refresh()->component)->toBe('capell.widget.portfolio.hero')
        ->and(data_get($hero->meta, 'component'))->toBe('capell.widget.portfolio.hero')
        ->and(data_get(retiredMigrationReportData(), 'widgets.0'))->toBe([
            'id' => $hero->id,
            'name' => 'Agency-hero',
            'component_from' => 'capell.widget.agency.hero',
            'component_to' => 'capell.widget.portfolio.hero',
            'meta_component_from' => 'capell.widget.agency.hero',
            'meta_component_to' => 'capell.widget.portfolio.hero',
            'section_type_from' => null,
            'section_type_to' => null,
            'items_from' => null,
            'section_type_added' => false,
            'hidden_fields' => [],
        ])
        ->and(resolve(RetiredThemeReferenceScanner::class)->blocking(resolve(RetiredThemeReferenceScanner::class)->scan('agency')))->toBe([]);

    retiredMigrationRemoveRenderables('agency');
    // Each request starts with fresh request-scoped loaders.
    app()->forgetScopedInstances();

    get(retiredMigrationUrl($page))->assertOk()
        ->assertSee('rendered-capell-widget-portfolio-hero', false)
        ->assertDontSee('rendered-capell-widget-agency-hero', false);

    // A widget an editor pointed elsewhere after the move stays where they put it.
    $edited->update(['component' => 'capell.widget.portfolio.cta', 'meta' => null]);
    retiredMigrationRenderables(['capell.widget.agency.hero']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($hero->refresh()->component)->toBe('capell.widget.agency.hero')
        ->and(data_get($hero->meta, 'component'))->toBe('capell.widget.agency.hero')
        ->and($edited->refresh()->component)->toBe('capell.widget.portfolio.cta')
        ->and($edited->meta)->toBeNull()
        ->and($site->refresh()->theme_id)->toBe($agency->id)
        ->and($portfolio->refresh()->exists)->toBeTrue();

    app()->forgetScopedInstances();

    get(retiredMigrationUrl($page))->assertOk()->assertSee('rendered-capell-widget-agency-hero', false);
});

it('refuses the whole move when the successor lacks a section a widget uses, naming the widgets', function (): void {
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.agency.gallery', 'capell.widget.portfolio.hero']);
    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $hero = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    $gallery = retiredMigrationSectionWidget('agency-gallery', 'capell.widget.agency.gallery');
    $trashedGallery = retiredMigrationSectionWidget('agency-gallery-old', 'capell.widget.agency.gallery');
    $trashedGallery->delete();

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('capell.widget.portfolio.gallery')
        ->toContain($gallery->id . ', ' . $trashedGallery->id)
        ->and($site->refresh()->theme_id)->toBe($agency->id)
        ->and($hero->refresh()->component)->toBe('capell.widget.agency.hero')
        ->and(File::glob(storage_path('app/capell/theme-migrations/*.json')))->toBe([]);
});

it('revokes widget snapshots for moved sites, layouts and remapped widgets, forward and on rollback', function (): void {
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.portfolio.hero']);
    $agency = retiredMigrationTheme('agency');
    $editorial = retiredMigrationTheme('editorial');
    ['site' => $agencySite, 'page' => $agencyPage] = retiredMigrationSite($agency, 'Agency site');
    ['site' => $editorialSite] = retiredMigrationSite($editorial, 'Editorial site');
    $language = $agencyPage->translations()->firstOrFail()->language_id;
    retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    // An editorial-site layout that places the theme widget is not on the retired theme.
    $placing = Layout::factory()->site($editorialSite)->create([
        'containers' => ['main' => ['label' => 'Main', 'widgets' => [['widget_key' => 'agency-hero', 'occurrence' => 1]]]],
    ]);

    $onRetiredSite = retiredMigrationSnapshot(['site_id' => $agencySite->id, 'language_id' => $language, 'theme_id' => $agency->id]);
    $placingWidget = retiredMigrationSnapshot(['site_id' => $editorialSite->id, 'language_id' => $language, 'layout_id' => $placing->id, 'theme_id' => $editorial->id]);
    $unrelated = retiredMigrationSnapshot(['site_id' => $editorialSite->id, 'language_id' => $language, 'theme_id' => $editorial->id]);

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]);

    expect(retiredMigrationSnapshotRevoked($onRetiredSite))->toBeTrue()
        ->and(retiredMigrationSnapshotRevoked($placingWidget))->toBeTrue()
        ->and(retiredMigrationSnapshotRevoked($unrelated))->toBeFalse();

    $portfolio = Theme::query()->where('key', 'portfolio')->firstOrFail();
    $rebuiltOnSuccessor = retiredMigrationSnapshot(['site_id' => $agencySite->id, 'language_id' => $language, 'theme_id' => $portfolio->id]);
    $rebuiltPlacing = retiredMigrationSnapshot(['site_id' => $editorialSite->id, 'language_id' => $language, 'layout_id' => $placing->id, 'theme_id' => $editorial->id]);

    Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]);

    expect(retiredMigrationSnapshotRevoked($rebuiltOnSuccessor))->toBeTrue()
        ->and(retiredMigrationSnapshotRevoked($rebuiltPlacing))->toBeTrue()
        ->and(retiredMigrationSnapshotRevoked($unrelated))->toBeFalse();
});

it('purges every site that renders pages through a moved global layout', function (): void {
    $agency = retiredMigrationTheme('agency');
    $editorial = retiredMigrationTheme('editorial');
    $site = Site::factory()->withTranslations()->create(['name' => 'Editorial site', 'theme_id' => $editorial->id]);
    $global = Layout::factory()->create(['name' => 'Global agency layout', 'site_id' => null, 'theme_id' => $agency->id]);
    Page::factory()->site($site)->layout($global)->withTranslations()->create();
    $purged = [];

    Event::listen(FrontendSurrogateKeysInvalidated::class, function (FrontendSurrogateKeysInvalidated $event) use (&$purged): void {
        $purged = [...$purged, ...$event->surrogateKeys];
    });

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($global->refresh()->site_id)->toBeNull()
        ->and($purged)->toContain('site-' . $site->id);
});

it('lists every remaining reference location in the doctor check and fails once the package is gone', function (): void {
    $agency = retiredMigrationTheme('agency');
    retiredMigrationSite($agency, 'Agency site');
    retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    $global = Layout::factory()->create(['site_id' => null, 'theme_id' => $agency->id]);
    $global->delete();

    $check = (new FoundationThemeHealthCheck)->retiredThemeSitesCheck();

    expect($check->evidence['references_by_retired_theme'])->toBe(['agency' => [
        'sites.theme_id' => 1,
        'layouts.theme_id' => 1,
        'widgets.component' => 1,
        'widgets.meta.component' => 1,
    ]])
        ->and($check->outcome())->toBe(DoctorCheckOutcome::Warning);

    resolve(ThemeRegistry::class)->reset();
    retiredMigrationRegisterThemes(except: ['agency']);

    expect((new FoundationThemeHealthCheck)->retiredThemeSitesCheck()->outcome())->toBe(DoctorCheckOutcome::Failed);
});

/*
 * Scope: column names only, over the schema of the packages this test case
 * loads. It catches a new theme-named column; references in other columns are
 * the schema-wide search's job at run time.
 */
it('classifies every column named like a theme reference in the packages test schema', function (): void {
    $classified = [];

    foreach (RetiredThemeReferenceScanner::SCHEMA_LOCATIONS as $table => $columns) {
        foreach (array_keys($columns) as $column) {
            $classified[] = TableName::resolve($table) . '.' . $column;
        }
    }

    $found = [];

    foreach (Schema::getTables() as $table) {
        foreach (Schema::getColumns($table['name']) as $column) {
            if (preg_match('/(^|_)theme(_id|_key)?$|^component(_item)?$|_component$/', $column['name']) === 1) {
                $found[] = $table['name'] . '.' . $column['name'];
            }
        }
    }

    sort($found);
    sort($classified);

    // A new column that can name a theme or a theme renderable must be added
    // to the scanner's registry (and moved, scanned or explained) before it ships.
    expect(array_values(array_diff($found, $classified)))->toBe([])
        ->and(array_values(array_diff($classified, $found)))->toBe([]);

    // Theme-keyed container settings are inert for a theme with no reader.
    foreach (app()->tagged(LayoutContainerThemePresentationProjector::TAG) as $projector) {
        expect(resolve(RetiredThemeSuccessors::class)->successorFor($projector->themeKey()))->toBeNull();
    }
});

it('reports a reference in any other column through the schema-wide search on a no-op rerun', function (): void {
    $agency = retiredMigrationTheme('agency');
    retiredMigrationSite($agency, 'Agency site');
    $layout = Layout::factory()->create(['site_id' => null]);
    DB::table(TableName::resolve('layouts'))->where('id', $layout->id)->update(['meta' => json_encode(['note' => 'capell.widget.agency.hero'])]);

    // The move succeeds, but a reference it cannot reach fails the run.
    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Site::query()->where('theme_id', $agency->id)->exists())->toBeFalse()
        ->and(Artisan::output())->toContain('not yet safe to remove')
        ->toContain('schema:' . TableName::resolve('layouts') . '.meta: 1');

    // A no-op rerun still fails while the reference remains.
    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']))->toBe(1)
        ->and(Artisan::output())->toContain('not yet safe to remove');

    DB::table(TableName::resolve('layouts'))->where('id', $layout->id)->update(['meta' => null]);

    // Rerunning after the move is a no-op that still gives the verdict.
    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']))->toBe(0)
        ->and(Artisan::output())->toContain('package can be removed')
        ->toContain('view:clear');
});

it('counts a Foundation theme config that names the retired theme in the removal verdict', function (): void {
    config(['capell-frontend.foundation_theme' => 'agency']);

    expect(resolve(RetiredThemeReferenceScanner::class)->removalVerdict('agency'))->toBe(['config.capell-frontend.foundation_theme' => 1]);
});

it('blocks the move on retired section references in meta.component_item', function (string $table): void {
    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $widget = retiredMigrationSectionWidget('agency-list', 'capell.widget.default');
    $id = $table === 'widgets' ? $widget->id : (int) $widget->blueprint_id;
    DB::table(TableName::resolve($table))->where('id', $id)->update(['meta' => json_encode(['component_item' => 'capell.widget.agency.card'])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain($table . '.meta.component_item')
        ->and($site->refresh()->theme_id)->toBe($agency->id);
})->with(['widgets', 'blueprints']);

it('tells the operator how to fix section widgets left behind with no retired theme record', function (): void {
    retiredMigrationRenderables(['capell.widget.portfolio.hero']);
    $widget = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1);

    expect(Artisan::output())->toContain('Widget ' . $widget->id . ': Agency-hero (capell.widget.agency.hero -> capell.widget.portfolio.hero)')
        ->toContain('No agency theme record exists')
        ->and($widget->refresh()->component)->toBe('capell.widget.agency.hero');
});

it('refuses a move whose successor section view cannot draw the widget section type', function (): void {
    $directory = storage_path('framework/testing/retired-theme-sections');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/hero.blade.php', 'hero');
    View::addNamespace('retired-sections', $directory);
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.agency.gallery']);
    foreach (['hero', 'gallery'] as $section) {
        resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.' . $section, type: 'layout-widget', blade: 'retired-sections::widget.section'));
    }

    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $hero = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    $gallery = retiredMigrationSectionWidget('agency-gallery', 'capell.widget.agency.gallery');
    DB::table(TableName::resolve('widgets'))->where('id', $hero->id)->update(['meta' => json_encode(['type' => 'hero'])]);
    DB::table(TableName::resolve('widgets'))->where('id', $gallery->id)->update(['meta' => json_encode(['type' => 'gallery'])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('retired-sections::sections.gallery')
        ->not->toContain('retired-sections::sections.hero')
        ->and($site->refresh()->theme_id)->toBe($agency->id);
});

it('maps a retired section the successor offers under another name, keeps its data and maps it back on rollback', function (): void {
    $directory = storage_path('framework/testing/retired-theme-mapped-sections');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/project-feed.blade.php', 'feed');
    View::addNamespace('retired-mapped-sections', $directory);
    retiredMigrationRenderables(['capell.widget.agency.creators']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.creators', type: 'layout-widget', blade: 'retired-mapped-sections::widget.section'));

    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $creators = retiredMigrationSectionWidget('agency-creators', 'capell.widget.agency.creators');
    $stories = [['title' => 'Owner creator', 'url' => '/creators/owner', 'stat_value' => '12', 'stat_label' => 'Projects']];
    DB::table(TableName::resolve('widgets'))->where('id', $creators->id)->update(['meta' => json_encode(['type' => 'creator-directory', 'heading' => 'Owner creators', 'stories' => $stories])]);

    Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']);

    expect(Artisan::output())->toContain('the creator-directory section becomes the project-feed section')
        ->toContain('items.*.stat_label, items.*.stat_value')
        ->toContain("items without their own label show the project-feed section's default text")
        ->not->toContain('items.*.category');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($agency->id)
        ->and($creators->refresh()->meta)->toMatchArray([
            'type' => 'project-feed',
            'heading' => 'Owner creators',
            'stories' => $stories,
            'items' => $stories,
        ]);

    retiredMigrationRenderables(['capell.widget.agency.creators']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($creators->refresh()->meta)->toMatchArray(['type' => 'creator-directory', 'stories' => $stories])
        ->and($creators->meta)->not->toHaveKey('items')
        ->and($site->refresh()->theme_id)->toBe($agency->id);
});

it('points a mapped widget at the successor renderable for its new section, and back on rollback', function (): void {
    $directory = storage_path('framework/testing/retired-theme-mapped-renderable');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/project-feed.blade.php', 'feed');
    View::addNamespace('retired-mapped-renderable', $directory);
    retiredMigrationRenderables(['capell.widget.directory.portfolio-grid']);
    // Portfolio registers one renderable per section, so it has project-feed and no portfolio-grid.
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.project-feed', type: 'layout-widget', blade: 'retired-mapped-renderable::widget.section'));

    $directoryTheme = retiredMigrationTheme('directory');
    ['site' => $site] = retiredMigrationSite($directoryTheme, 'Directory site');
    $grid = retiredMigrationSectionWidget('directory-grid', 'capell.widget.directory.portfolio-grid');
    $items = [['title' => 'Owner profile', 'url' => '/profiles/owner', 'location' => 'Leeds']];
    DB::table(TableName::resolve('widgets'))->where('id', $grid->id)->update(['meta' => json_encode(['type' => 'portfolio-grid', 'heading' => 'Owner grid', 'items' => $items])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'directory', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($directoryTheme->id)
        ->and($grid->refresh()->component)->toBe('capell.widget.portfolio.project-feed')
        ->and($grid->meta)->toMatchArray(['type' => 'project-feed', 'items' => $items]);

    retiredMigrationRenderables(['capell.widget.directory.portfolio-grid']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($grid->refresh()->component)->toBe('capell.widget.directory.portfolio-grid')
        ->and($grid->meta)->toMatchArray(['type' => 'portfolio-grid', 'items' => $items]);
});

it('draws a section the successor has no renderable for through Foundation, and restores it on rollback', function (): void {
    retiredMigrationRenderables(['capell.widget.bistro.review-wall', 'capell.widget.bistro.hero']);

    $bistro = retiredMigrationTheme('bistro');
    ['site' => $site] = retiredMigrationSite($bistro, 'Bistro site');
    $reviews = retiredMigrationSectionWidget('bistro-reviews', 'capell.widget.bistro.review-wall');
    $hero = retiredMigrationSectionWidget('bistro-hero', 'capell.widget.bistro.hero');
    $items = [['quote' => 'Owner review', 'name' => 'Owner guest', 'source' => 'Owner source']];
    DB::table(TableName::resolve('widgets'))->where('id', $reviews->id)->update(['meta' => json_encode(['type' => 'review-wall', 'heading' => 'Owner reviews', 'items' => $items])]);
    DB::table(TableName::resolve('widgets'))->where('id', $hero->id)->update(['meta' => json_encode(['type' => 'hero', 'heading' => 'Owner welcome'])]);

    // Business ships no proof or hero renderable of its own, so both land on Foundation's.
    expect(Artisan::call('capell:theme-migrate', ['from' => 'bistro', 'to' => 'business', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($bistro->id)
        ->and($reviews->refresh()->component)->toBe('capell.widget.foundation.proof')
        ->and($reviews->meta)->toMatchArray(['type' => 'proof', 'items' => $items])
        ->and($hero->refresh()->component)->toBe('capell.widget.foundation.hero')
        ->and($hero->meta)->toMatchArray(['type' => 'hero', 'heading' => 'Owner welcome']);

    retiredMigrationRenderables(['capell.widget.bistro.review-wall', 'capell.widget.bistro.hero']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($reviews->refresh()->component)->toBe('capell.widget.bistro.review-wall')
        ->and($reviews->meta)->toMatchArray(['type' => 'review-wall', 'items' => $items])
        ->and($hero->refresh()->component)->toBe('capell.widget.bistro.hero');
});

it('moves a widget that saves no section type by its renderable name, and removes the added type on rollback', function (): void {
    $directory = storage_path('framework/testing/retired-theme-implied-type');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/changelog-integrations.blade.php', 'changelog');
    View::addNamespace('retired-implied-type', $directory);
    // Knowledge names its widget renderables capell.widget.docs.*, not after its theme key.
    retiredMigrationRenderables(['capell.widget.docs.version-changelog-surfaces']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.platform.changelog-integrations', type: 'layout-widget', blade: 'retired-implied-type::widget.section'));

    $knowledge = retiredMigrationTheme('knowledge');
    ['site' => $site] = retiredMigrationSite($knowledge, 'Knowledge site');
    $changelog = retiredMigrationSectionWidget('docs-changelog', 'capell.widget.docs.version-changelog-surfaces');
    $entries = [['title' => 'Owner release', 'summary' => 'Owner notes', 'version' => '2.1.0']];
    DB::table(TableName::resolve('widgets'))->where('id', $changelog->id)->update(['meta' => json_encode(['heading' => 'Owner changelog', 'entries' => $entries])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'knowledge', 'to' => 'platform']))->toBe(0)
        ->and(Artisan::output())->toContain('the version-changelog-surfaces section becomes the changelog-integrations section');

    expect(Artisan::call('capell:theme-migrate', ['from' => 'knowledge', 'to' => 'platform', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($knowledge->id)
        ->and($changelog->refresh()->component)->toBe('capell.widget.platform.changelog-integrations')
        ->and($changelog->meta)->toMatchArray(['type' => 'changelog-integrations', 'entries' => $entries, 'items' => $entries]);

    retiredMigrationRenderables(['capell.widget.docs.version-changelog-surfaces']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($changelog->refresh()->component)->toBe('capell.widget.docs.version-changelog-surfaces')
        ->and($changelog->meta)->toMatchArray(['heading' => 'Owner changelog', 'entries' => $entries])
        ->and($changelog->meta)->not->toHaveKey('type')
        ->and($changelog->meta)->not->toHaveKey('items');
});

it('maps every section of a retired theme without a same-named successor view to a successor section that shows its items', function (string $successor): void {
    $packages = dirname(__DIR__, 3);
    View::addNamespace('capell-theme-' . $successor, $packages . '/theme-' . $successor . '/resources/views');
    $successorSections = collect(File::files($packages . '/theme-' . $successor . '/resources/views/sections'))->map(static fn (SplFileInfo $file): string => $file->getBasename('.blade.php'))->all();
    $map = resolve(RetiredThemeSectionMap::class);
    $item = ['title' => 'Owner item title', 'summary' => 'Owner item summary', 'label' => 'Owner item label', 'value' => '42', 'url' => '/owner-item'];
    $retiredThemes = array_keys(array_filter(resolve(RetiredThemeSuccessors::class)->all(), static fn (string $key): bool => $key === $successor));

    foreach ($retiredThemes as $retired) {
        // Folio keeps its section views under widget/sections, and some themes draw theirs straight from widget/.
        $views = $packages . '/theme-' . $retired . '/resources/views';
        $directories = array_filter([$views . '/sections', $views . '/widget/sections', ...(in_array($retired, ['knowledge', 'switchboard', 'liquid-glass'], true) ? [$views . '/widget'] : [])], is_dir(...));

        foreach ($directories as $directory) {
            foreach (File::files($directory) as $file) {
                $type = $file->getBasename('.blade.php');

                // A variant is saved in meta.variant, so only base section types are stored in meta.type.
                // Paperdesk draws `empty` and Folio `generic` for a section type they do not support, so no widget saves either.
                if (str_contains($type, '--') || in_array($type, $successorSections, true) || in_array($type, ['empty', 'generic', 'section', 'resolved-section'], true)) {
                    continue;
                }

                $mapping = $map->for($retired, $type);

                expect($mapping)->not->toBeNull($retired . ' section ' . $type . ' has no ' . $successor . ' counterpart');

                /** @var array{type: string, items_from: ?string, not_shown: list<string>, requires_items?: bool} $mapping */
                // A section that shows no list moves without items, so there is nothing to draw.
                if (! ($mapping['requires_items'] ?? true)) {
                    continue;
                }

                /** @var view-string $view */
                $view = sprintf('capell-theme-%s::sections.%s', $successor, $mapping['type']);
                $html = view($view, ['section' => ['heading' => 'Owner heading', ($mapping['items_from'] ?? 'items') => [$item], 'items' => [$item]]])->render();

                expect($html)->toContain('Owner item')->and($html)->toMatch('/Owner item (title|label)/');
            }
        }
    }
})->with(['portfolio', 'editorial', 'platform']);

dataset('retired section routes', [
    'agency into portfolio' => ['agency', 'portfolio', 'creator-directory', 'project-feed', 'stories', ['items.*.category', 'items.*.stat_label']],
    'onepage into portfolio' => ['onepage', 'portfolio', 'tools-sponsors', 'credits-tools', 'stories', ['items.*.category', 'items.*.meta']],
    'showreel into portfolio' => ['showreel', 'portfolio', 'winner-list', 'project-feed', 'items', ['items.*.tier', 'items.*.credit']],
    'submissions into portfolio' => ['submissions', 'portfolio', 'profiles-resources', 'content-listing', 'stories', []],
    'magazine into editorial' => ['magazine', 'editorial', 'columnists', 'opinion-grid-with-bylines', 'items', ['items.*.latest_title', 'items.*.latest_url']],
    'magazine radio into the editorial listing' => ['magazine', 'editorial', 'radio-audio', 'content-listing', 'items', []],
    'curated into portfolio' => ['curated', 'portfolio', 'curation-feed', 'project-feed', 'entries', ['items.*.author', 'items.*.maker']],
    'saas into portfolio' => ['saas', 'portfolio', 'paid-templates', 'project-feed', 'items', ['items.*.price', 'items.*.framework']],
    'paperdesk guestbook into portfolio proof' => ['paperdesk', 'portfolio', 'guestbook-wall', 'proof', 'testimonials', ['items.*.quote', 'items.*.attribution']],
    'catalogue blog mission into the portfolio listing' => ['catalogue', 'portfolio', 'blog-mission', 'content-listing', 'stories', []],
    'directory grid into the portfolio feed' => ['directory', 'portfolio', 'portfolio-grid', 'project-feed', 'items', ['items.*.location', 'items.*.available']],
    'minimalist gallery into the portfolio feed' => ['minimalist', 'portfolio', 'gallery', 'project-feed', 'items', ['items.*.caption']],
    'brutalist archive dates into the portfolio timeline' => ['brutalist', 'portfolio', 'archive-dates', 'process-notes-timeline', 'stories', ['items.*.category']],
    'awards nominees into the portfolio feed' => ['awards', 'portfolio', 'newest-nominees', 'project-feed', 'items', ['items.*.score', 'items.*.category']],
    'blog essays into the editorial listing' => ['blog', 'editorial', 'essay-index', 'content-listing', 'essays', ['items.*.dek', 'items.*.byline']],
    'broadsheet sessions into the editorial timeline' => ['broadsheet', 'editorial', 'session-clock', 'live-event-timeline', 'sessions', ['items.*.start', 'items.*.end']],
    'folio timeline into the editorial timeline' => ['folio', 'editorial', 'timeline-folio', 'live-event-timeline', 'entries', ['items.*.date']],
    'bistro reviews into the business proof' => ['bistro', 'business', 'review-wall', 'proof', 'items', ['items.*.source', 'items.*.meta']],
    'events speakers into the business proof' => ['events', 'business', 'speaker-wall-hover-bios', 'proof', 'speakers', ['items.*.bio', 'items.*.role']],
    'grounds trail into the business features' => ['grounds', 'business', 'trail-story', 'features', 'stops', ['items.*.heading']],
    'almanac sessions into the business listing' => ['almanac', 'business', 'next-sessions-strip', 'content-listing', 'sessions', ['items.*.date', 'items.*.capacityBand']],
    'knowledge changelog into the platform changelog' => ['knowledge', 'platform', 'version-changelog-surfaces', 'changelog-integrations', 'entries', ['items.*.version', 'items.*.changeType']],
    'switchboard status into the platform proof' => ['switchboard', 'platform', 'status-strip', 'proof', 'items', ['items.*.figure', 'items.*.trend']],
    'liquid glass stats into the platform proof' => ['liquid-glass', 'platform', 'translucent-stat-band', 'proof', 'stats', ['items.*.prefix', 'items.*.suffix']],
]);

it('moves a section the retired theme alone offered onto its successor, keeps its data and restores the original section on rollback', function (string $retired, string $successor, string $type, string $successorType, string $listKey, array $hidden): void {
    $namespace = 'retired-route-' . $retired;
    $directory = storage_path('framework/testing/' . $namespace);
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/' . $successorType . '.blade.php', 'successor');
    View::addNamespace($namespace, $directory);
    retiredMigrationRenderables([RetiredThemeReferenceScanner::renderablePrefix($retired) . 'probe']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.' . $successor . '.probe', type: 'layout-widget', blade: $namespace . '::widget.section'));

    $theme = retiredMigrationTheme($retired);
    ['site' => $site] = retiredMigrationSite($theme, 'Retired section site');
    $widget = retiredMigrationSectionWidget($retired . '-probe', RetiredThemeReferenceScanner::renderablePrefix($retired) . 'probe');
    $item = ['title' => 'Owner item', 'summary' => 'Owner summary', 'url' => '/owner-item'];

    foreach ($hidden as $field) {
        $item[substr($field, strlen('items.*.'))] = 'Owner ' . $field;
    }

    $meta = ['type' => $type, 'heading' => 'Owner heading', $listKey => [$item]];
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update(['meta' => json_encode($meta)]);

    expect(Artisan::call('capell:theme-migrate', ['from' => $retired, 'to' => $successor]))->toBe(0);
    $preview = Artisan::output();

    expect($preview)->toContain('the ' . $type . ' section becomes the ' . $successorType . ' section');

    // Default labels belong to the successor theme's own section, so the
    // editorial listing names its kicker and the portfolio listing names none.
    $defaultLabels = resolve(RetiredThemeSectionMap::class)->successorDefaultLabels($successor, $successorType);

    if ($defaultLabels === []) {
        expect($preview)->not->toContain('items without their own label');
    } else {
        foreach ($defaultLabels as $labelKey) {
            expect($preview)->toContain('"' . __($labelKey) . '"');
        }
    }

    foreach ($hidden as $field) {
        expect($preview)->toContain($field);
    }

    expect(Artisan::call('capell:theme-migrate', ['from' => $retired, 'to' => $successor, '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($theme->id)
        ->and($widget->refresh()->meta)->toMatchArray(['type' => $successorType, 'heading' => 'Owner heading', $listKey => [$item], 'items' => [$item]]);

    retiredMigrationRenderables([RetiredThemeReferenceScanner::renderablePrefix($retired) . 'probe']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->toBe($theme->id)
        ->and($widget->refresh()->meta)->toMatchArray(['type' => $type, 'heading' => 'Owner heading', $listKey => [$item]]);

    if ($listKey !== 'items') {
        expect($widget->meta)->not->toHaveKey('items');
    }
})->with('retired section routes');

dataset('retired curated sections without items', [
    'form stays a form' => ['form', 'form', ['eyebrow' => 'Owner eyebrow', 'checklist' => ['Owner step']], ['eyebrow', 'checklist']],
    'lightbox viewer becomes the project feed' => ['lightbox-carousel-viewer', 'project-feed', ['label' => 'Owner viewer label', 'ariaLabel' => 'Owner viewer aria'], ['label', 'ariaLabel']],
]);

it('moves a curated section that holds no items onto Portfolio, keeps its data and restores the original section on rollback', function (string $type, string $successorType, array $data, array $hidden): void {
    $namespace = 'retired-route-curated-' . $type;
    $directory = storage_path('framework/testing/' . $namespace);
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/' . $successorType . '.blade.php', 'successor');
    View::addNamespace($namespace, $directory);
    retiredMigrationRenderables(['capell.widget.curated.probe']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.probe', type: 'layout-widget', blade: $namespace . '::widget.section'));

    $theme = retiredMigrationTheme('curated');
    ['site' => $site] = retiredMigrationSite($theme, 'Curated section site');
    $widget = retiredMigrationSectionWidget('curated-probe', 'capell.widget.curated.probe');
    $meta = ['type' => $type, 'heading' => 'Owner heading', ...$data];
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update(['meta' => json_encode($meta)]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'curated', 'to' => 'portfolio']))->toBe(0);
    $preview = Artisan::output();

    expect($preview)->toContain('the ' . $type . ' section becomes the ' . $successorType . ' section');

    foreach ($hidden as $field) {
        expect($preview)->toContain($field);
    }

    expect(Artisan::call('capell:theme-migrate', ['from' => 'curated', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($theme->id)
        ->and($widget->refresh()->meta)->toMatchArray(['type' => $successorType, 'heading' => 'Owner heading', ...$data]);

    retiredMigrationRenderables(['capell.widget.curated.probe']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->toBe($theme->id)
        ->and($widget->refresh()->meta)->toMatchArray(['type' => $type, 'heading' => 'Owner heading', ...$data]);
})->with('retired curated sections without items');

it('blocks a mapped section that has no items of its own, because the successor section would disappear', function (): void {
    $directory = storage_path('framework/testing/retired-theme-empty-sections');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/related-projects.blade.php', 'related');
    View::addNamespace('retired-empty-sections', $directory);
    retiredMigrationRenderables(['capell.widget.agency.featured']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.featured', type: 'layout-widget', blade: 'retired-empty-sections::widget.section'));

    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $featured = retiredMigrationSectionWidget('agency-featured', 'capell.widget.agency.featured');
    DB::table(TableName::resolve('widgets'))->where('id', $featured->id)->update(['meta' => json_encode(['type' => 'featured-portfolios', 'heading' => 'Owner heading'])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('Widget(s) ' . $featured->id . ' use the featured-portfolios section without items')
        ->and($site->refresh()->theme_id)->toBe($agency->id)
        ->and($featured->refresh()->meta)->toMatchArray(['type' => 'featured-portfolios']);
});

it('leaves a mapped widget alone when its section type changes between the plan and the write', function (): void {
    $directory = storage_path('framework/testing/retired-theme-raced-sections');
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/project-feed.blade.php', 'feed');
    View::addNamespace('retired-raced-sections', $directory);
    retiredMigrationRenderables(['capell.widget.agency.grid']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.grid', type: 'layout-widget', blade: 'retired-raced-sections::widget.section'));

    $agency = retiredMigrationTheme('agency');
    retiredMigrationSite($agency, 'Agency site');
    $grid = retiredMigrationSectionWidget('agency-grid', 'capell.widget.agency.grid');
    DB::table(TableName::resolve('widgets'))->where('id', $grid->id)->update(['meta' => json_encode(['type' => 'portfolio-grid', 'items' => [['title' => 'Owner project']]])]);

    $plan = PlanThemeMigrationAction::run('agency', 'portfolio');
    DB::table(TableName::resolve('widgets'))->where('id', $grid->id)->update(['meta' => json_encode(['type' => 'gallery', 'items' => [['title' => 'Owner project']]])]);
    PlanThemeMigrationAction::shouldRun()->andReturn($plan);

    MigrateSitesToThemeAction::run('agency', 'portfolio');

    expect($grid->refresh()->component)->toBe('capell.widget.agency.grid')
        ->and(data_get($grid->meta, 'type'))->toBe('gallery');
});

it('purges the sites of layouts placing remapped widgets beyond one query chunk', function (): void {
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.portfolio.hero']);
    $agency = retiredMigrationTheme('agency');
    $editorial = retiredMigrationTheme('editorial');
    retiredMigrationSite($agency, 'Agency site');
    ['site' => $editorialSite] = retiredMigrationSite($editorial, 'Editorial site');

    foreach (range(1, 60) as $index) {
        retiredMigrationSectionWidget('agency-hero-' . $index, 'capell.widget.agency.hero');
    }

    Layout::factory()->site($editorialSite)->create([
        'containers' => ['main' => ['label' => 'Main', 'widgets' => [['widget_key' => 'agency-hero-58', 'occurrence' => 1]]]],
    ]);
    $purged = [];

    Event::listen(FrontendSurrogateKeysInvalidated::class, function (FrontendSurrogateKeysInvalidated $event) use (&$purged): void {
        $purged = [...$purged, ...$event->surrogateKeys];
    });

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($purged)->toContain('site-' . $editorialSite->id);
});

it('fails a no-op rerun while a reference the move cannot reach blocks it', function (): void {
    retiredMigrationTheme('agency');
    $widget = retiredMigrationSectionWidget('agency-list', 'capell.widget.default');
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update(['component_item' => 'capell.widget.agency.card']);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']))->toBe(1)
        ->and(Artisan::output())->toContain('widgets.component_item')
        ->toContain('not yet safe to remove');
});

it('reports a meta component reference once in the removal verdict', function (): void {
    retiredMigrationSectionWidget('agency-hero', 'capell.widget.default');
    $widget = Widget::query()->where('key', 'agency-hero')->firstOrFail();
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update(['meta' => json_encode(['component_item' => 'capell.widget.agency.card'])]);

    expect(resolve(RetiredThemeReferenceScanner::class)->removalVerdict('agency'))->toBe(['schema:' . TableName::resolve('widgets') . '.meta' => 1]);
});

it('treats LIKE wildcards in a theme key literally', function (): void {
    retiredMigrationSectionWidget('look-alike', 'capell.widget.agencyx.hero');

    expect(resolve(RetiredThemeReferenceScanner::class)->scan('agency_')['widgets.component'])->toBe(0)
        ->and(resolve(RetiredThemeReferenceScanner::class)->schemaWide('agenc%'))->toBe([]);
});

it('skips history, metric and event tables in the schema-wide search', function (): void {
    Schema::create('retired_scan_page_events', function (Blueprint $table): void {
        $table->id();
        $table->text('payload');
    });
    DB::table('retired_scan_page_events')->insert(['payload' => 'capell.widget.agency.hero']);

    expect(resolve(RetiredThemeReferenceScanner::class)->schemaWide('agency'))->toBe([]);
});

it('searches only this connection\'s prefixed tables and finds references in them', function (): void {
    $default = DB::getDefaultConnection();
    config(['database.connections.retired_prefixed' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'cap_',
        'foreign_key_constraints' => false,
    ]]);
    DB::setDefaultConnection('retired_prefixed');

    try {
        $schema = Schema::connection('retired_prefixed');
        $schema->create('themes', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->softDeletes();
        });
        $schema->create('page_blocks', function (Blueprint $table): void {
            $table->id();
            $table->json('content');
            $table->unsignedBigInteger('theme_id')->nullable();
        });
        DB::connection('retired_prefixed')->table('themes')->insert(['id' => 7, 'key' => 'agency']);
        DB::connection('retired_prefixed')->table('page_blocks')->insert([
            ['content' => json_encode(['component' => 'capell.widget.agency.hero']), 'theme_id' => null],
            ['content' => '{}', 'theme_id' => 7],
        ]);
        // Another application's table in the same database, without the prefix.
        DB::connection('retired_prefixed')->statement('CREATE TABLE other_app_blocks (body TEXT)');
        DB::connection('retired_prefixed')->statement("INSERT INTO other_app_blocks (body) VALUES ('capell.widget.agency.hero')");

        expect(resolve(RetiredThemeReferenceScanner::class)->schemaWide('agency'))->toBe([
            'schema:page_blocks.content' => 1,
            'schema:page_blocks.theme_id' => 1,
        ]);
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('retired_prefixed');
    }
});

it('refuses the move and reports the package unsafe while workspace drafts stay on the retired theme', function (): void {
    retiredMigrationRenderables(['capell.widget.agency.hero', 'capell.widget.portfolio.hero']);
    $agency = retiredMigrationTheme('agency');
    ['site' => $site] = retiredMigrationSite($agency, 'Agency site');
    $layout = Layout::factory()->create(['site_id' => null, 'theme_id' => $agency->id]);
    $widget = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    // Publishing Studio drafts: invisible to the console's live-only queries.
    DB::table(TableName::resolve('layouts'))->where('id', $layout->id)->update(['workspace_id' => 7]);
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update(['workspace_id' => 9]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('7, 9')
        ->and($site->refresh()->theme_id)->toBe($agency->id)
        ->and(DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->value('component'))->toBe('capell.widget.agency.hero');

    expect(resolve(RetiredThemeReferenceScanner::class)->removalVerdict('agency'))
        ->toHaveKey('schema:' . TableName::resolve('layouts') . '.theme_id')
        ->toHaveKey('schema:' . TableName::resolve('widgets') . '.component');

    // With the live site moved by hand, the drafts still block the package.
    DB::table(TableName::resolve('sites'))->where('id', $site->id)->update(['theme_id' => retiredMigrationTheme('portfolio')->id]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio']))->toBe(1)
        ->and(Artisan::output())->toContain('7, 9')
        ->and(resolve(RetiredThemeReferenceScanner::class)->removalVerdict('agency'))
        ->toHaveKey('schema:' . TableName::resolve('layouts') . '.theme_id');
});

it('finds references to a retired theme record that exists only as a workspace draft', function (): void {
    $agency = retiredMigrationTheme('agency');
    $layout = Layout::factory()->create(['site_id' => null, 'theme_id' => $agency->id]);
    DB::table(TableName::resolve('themes'))->where('id', $agency->id)->update(['workspace_id' => 7]);
    DB::table(TableName::resolve('layouts'))->where('id', $layout->id)->update(['workspace_id' => 7]);

    expect(resolve(RetiredThemeReferenceScanner::class)->removalVerdict('agency'))
        ->toBe(['schema:' . TableName::resolve('layouts') . '.theme_id' => 1]);
});

it('reports every reference the known-location scan counts through the schema-wide search', function (): void {
    $agency = retiredMigrationTheme('agency');
    $site = Site::factory()->create(['theme_id' => $agency->id]);
    Layout::factory()->create(['site_id' => null, 'theme_id' => $agency->id, 'containers' => ['main' => ['label' => 'Main', 'component' => 'capell.widget.agency.hero']]]);
    $widget = retiredMigrationSectionWidget('agency-hero', 'capell.widget.agency.hero');
    DB::table(TableName::resolve('widgets'))->where('id', $widget->id)->update([
        'component_item' => 'capell.widget.agency.card',
        'meta' => json_encode(['component' => 'capell.widget.agency.hero', 'component_item' => 'capell.widget.agency.card']),
    ]);
    DB::table(TableName::resolve('blueprints'))->where('id', $widget->blueprint_id)->update([
        'component' => 'capell.widget.agency.hero',
        'component_item' => 'capell.widget.agency.card',
        'meta' => json_encode(['component' => 'capell.widget.agency.hero', 'component_item' => 'capell.widget.agency.card']),
    ]);
    // Drafts and soft-deleted rows count in both searches.
    DB::table(TableName::resolve('sites'))->where('id', $site->id)->update(['workspace_id' => 3, 'deleted_at' => now()]);

    $scanner = resolve(RetiredThemeReferenceScanner::class);
    $schemaWide = $scanner->schemaWide('agency');
    $counted = array_filter($scanner->scan('agency'), static fn (int $count, string $location): bool => $count > 0 && ! in_array($location, RetiredThemeReferenceScanner::SETTING_LOCATIONS, true), ARRAY_FILTER_USE_BOTH);

    expect(array_keys($counted))->toHaveCount(11);

    foreach (array_keys($counted) as $location) {
        [$table, $column] = explode('.', $location, 3);

        expect($schemaWide)->toHaveKey('schema:' . TableName::resolve($table) . '.' . $column);
    }
});

it('casts JSON columns to text for a LIKE search on PostgreSQL', function (): void {
    config(['database.connections.retired_pgsql' => ['driver' => 'pgsql', 'host' => '127.0.0.1', 'database' => 'unused', 'username' => 'unused', 'password' => '', 'prefix' => '']]);

    $sql = RetiredThemeReferenceScanner::whereLike(Layout::on('retired_pgsql')->withoutGlobalScopes(), 'containers', '%x%')->toSql();

    expect($sql)->toContain('CAST("containers" AS TEXT) LIKE ?');
});

it('counts a theme id column only when it is theme_id or a foreign key to the themes table', function (): void {
    $agency = retiredMigrationTheme('agency');
    Schema::create('retired_scan_variants', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('retired_scan_blocks', function (Blueprint $table): void {
        $table->id();
        // Another table's id that happens to equal the retired theme's id.
        $table->foreignId('variant_theme_id')->constrained('retired_scan_variants');
        $table->foreignId('fallback_theme_id')->nullable()->constrained(TableName::resolve('themes'));
    });
    DB::table('retired_scan_variants')->insert(['id' => $agency->id]);
    DB::table('retired_scan_blocks')->insert(['variant_theme_id' => $agency->id, 'fallback_theme_id' => $agency->id]);

    expect(resolve(RetiredThemeReferenceScanner::class)->schemaWide('agency'))
        ->toBe(['schema:retired_scan_blocks.fallback_theme_id' => 1]);
});

it('skips framework tables that are created without the Capell table prefix', function (): void {
    Schema::dropIfExists('failed_jobs');
    Schema::create('failed_jobs', function (Blueprint $table): void {
        $table->id();
        $table->longText('payload');
    });
    DB::table('failed_jobs')->insert(['payload' => 'capell.widget.agency.hero']);
    config(['capell.database.table_prefix' => 'cp_']);

    expect(resolve(RetiredThemeReferenceScanner::class)->schemaWide('agency'))->toBe([]);
});

it('tells the operator to prune an abandoned workspace that still holds retired theme drafts', function (): void {
    $agency = retiredMigrationTheme('agency');
    retiredMigrationSite($agency, 'Agency site');
    $workspace = Workspace::factory()->abandoned()->create();
    $layout = Layout::factory()->create(['site_id' => null, 'theme_id' => $agency->id]);
    DB::table(TableName::resolve('layouts'))->where('id', $layout->id)->update(['workspace_id' => $workspace->id]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'agency', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain($workspace->id . ' (abandoned)')
        ->toContain('capell:publishing-studio:prune --id=' . $workspace->id);
});

it('moves an item-less section onto a successor hero but still refuses an empty list section', function (): void {
    $namespace = 'retired-route-saas-hero';
    $directory = storage_path('framework/testing/' . $namespace);
    File::ensureDirectoryExists($directory . '/widget');
    File::ensureDirectoryExists($directory . '/sections');
    File::put($directory . '/widget/section.blade.php', 'section');
    File::put($directory . '/sections/hero.blade.php', 'successor');
    File::put($directory . '/sections/project-feed.blade.php', 'successor');
    View::addNamespace($namespace, $directory);
    retiredMigrationRenderables(['capell.widget.saas.probe']);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(key: 'capell.widget.portfolio.probe', type: 'layout-widget', blade: $namespace . '::widget.section'));

    $theme = retiredMigrationTheme('saas');
    ['site' => $site] = retiredMigrationSite($theme, 'Utility site');
    $hero = retiredMigrationSectionWidget('saas-hero-probe', 'capell.widget.saas.probe');
    DB::table(TableName::resolve('widgets'))->where('id', $hero->id)->update(['meta' => json_encode(['type' => 'utility-hero', 'heading' => 'Find a template'])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]))->toBe(0)
        ->and($site->refresh()->theme_id)->not->toBe($theme->id)
        ->and($hero->refresh()->meta)->toMatchArray(['type' => 'hero', 'heading' => 'Find a template']);

    expect(Artisan::call('capell:theme-migrate', ['--rollback' => retiredMigrationReport(), '--force' => true]))->toBe(0)
        ->and($hero->refresh()->meta)->toMatchArray(['type' => 'utility-hero']);

    File::deleteDirectory(storage_path('app/capell/theme-migrations'));
    $listing = retiredMigrationSectionWidget('saas-list-probe', 'capell.widget.saas.probe');
    DB::table(TableName::resolve('widgets'))->where('id', $listing->id)->update(['meta' => json_encode(['type' => 'paid-templates', 'heading' => 'Templates'])]);

    expect(Artisan::call('capell:theme-migrate', ['from' => 'saas', 'to' => 'portfolio', '--force' => true]))->toBe(1)
        ->and($site->refresh()->theme_id)->toBe($theme->id);
});
