<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\BlockLibrary\Tests\Fixtures\BuilderBlockTestUrlGenerator;
use Capell\Blog\Models\Article;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Translation;
use Capell\Events\Models\Event;
use Capell\Events\Providers\EventsServiceProvider;
use Capell\Frontend\Actions\GenerateStaticPageArtifactsAction;
use Capell\HtmlCache\Support\Cache\HtmlCachePublicationGuard;
use Capell\HtmlCache\Support\Cache\HtmlCacheStore;
use Capell\HtmlCache\Support\Cache\PageCache;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';
require_once dirname(__DIR__, 4) . '/tests/Packages/Support/PublicOutputSafety.php';

uses(CreatesAdminUser::class);

it('renders saved blocks through the foundation blade consumer without editor internals', function (bool $pageContentWidget, bool $signedIn): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Saved blocks rendering');
    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->loadMissing(['layout', 'blueprint', 'translations', 'site.theme', 'site.language']);

    $blueprint = $page->blueprint ?? throw new LogicException('The saved Blocks fixture requires a blueprint.');
    $layout = $page->layout ?? throw new LogicException('The saved Blocks fixture requires a layout.');
    $page->forceFill(['content_structure_override' => ContentStructure::Blocks->value])->save();
    $blueprint->forceFill(['meta' => [
        ...(is_array($blueprint->meta) ? $blueprint->meta : []),
        'content_structure' => ContentStructure::Blocks->value,
    ]])->save();
    $blocks = [['type' => 'title', 'data' => [
        'title' => 'Saved block title',
        '__capell' => ['instance_id' => 'saved-block-identity', 'state_version' => 1],
    ]]];
    $translation = $page->translations->firstOrFail();
    $translation->forceFill(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)])->save();
    $page->setRelation('translation', $translation);
    $layout->forceFill(['containers' => $pageContentWidget ? [
        'main' => [
            'meta' => ['colspan' => 12],
            'widgets' => [['widget_key' => 'page-content', 'occurrence' => 1]],
        ],
    ] : null])->save();
    CapellLayoutManager::clearContainerWidgets();

    if ($signedIn) {
        $this->actingAsUser();
    }

    $response = get($pageUrl->full_url);
    $response->assertOk()->assertSee('Saved block title')->assertSee('id="main"', false);
    $html = $response->getContent();
    if (! is_string($html) || $html === '') {
        throw new LogicException('The saved Blocks response must contain HTML.');
    }

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

    expect(new DOMXPath($document)->query('//*[contains(concat(" ", normalize-space(@class), " "), " widget-page-content ")]'))
        ->toHaveCount($pageContentWidget ? 1 : 0);
    expect($html)->not->toContain('__capell')->not->toContain('saved-block-identity')->not->toContain('state_version')->not->toContain('content-blocks.')->not->toContain('data-field=')->not->toContain('data-page=');
    assertCapellPublicOutputIsSafe($response, 'Saved blocks page');
})->with([
    'empty layout anonymous' => [false, false],
    'empty layout non-admin' => [false, true],
    'page-content layout anonymous' => [true, false],
    'page-content layout non-admin' => [true, true],
]);

it('renders catalogue blocks and translation-owned media through the public HTTP consumer', function (bool $pageContentWidget, bool $signedIn): void {
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.public_view_query_guard.enabled' => true, 'capell-frontend.public_view_query_guard.mode' => 'exception']);
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Catalogue blocks');
    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->loadMissing(['layout', 'blueprint', 'translations', 'site.theme', 'site.language']);

    $layout = $page->layout ?? throw new LogicException('Expected a layout.');
    $translation = $page->translations->firstOrFail();
    throw_unless($translation instanceof Translation, LogicException::class);
    $page->forceFill(['content_structure_override' => ContentStructure::Blocks->value])->save();
    $keys = [(string) Str::uuid(), (string) Str::uuid()];
    $images = [];
    foreach ($keys as $index => $key) {
        $images[] = $translation->addMedia(UploadedFile::fake()->image('portrait-' . $index . '.png'))
            ->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key) ?? throw new LogicException('Expected a block collection.'), 'public');
    }

    $blocks = [
        ['type' => 'hero', 'data' => ['title' => 'Public catalogue hero', 'summary' => '<p>Public summary</p>', 'url' => '/about', '__capell' => ['instance_id' => 'private-builder-instance']]],
        ['type' => 'logos', 'data' => ['media_key' => $keys[0], 'logos' => ['private-logo-row' => ['name' => 'Partner', 'image' => $images[0]->uuid]]]],
        ['type' => 'team', 'data' => ['media_key' => $keys[1], 'members' => ['private-team-row' => ['name' => 'Ada', 'image' => $images[1]->uuid]]]],
        ['type' => 'pricing', 'data' => ['plans' => [['name' => 'Standard', 'price' => '10', 'action_url' => 'javascript:alert(1)']]]],
        ['type' => 'faq', 'data' => ['questions' => [['question' => 'Question', 'answer' => '<p>Answer</p>']]]],
    ];
    $translation->forceFill(['content' => json_encode($blocks, JSON_THROW_ON_ERROR)])->save();
    $layout->forceFill(['containers' => $pageContentWidget ? ['main' => ['meta' => ['colspan' => 12], 'widgets' => [['widget_key' => 'page-content', 'occurrence' => 1]]]] : null])->save();
    CapellLayoutManager::clearContainerWidgets();
    if ($signedIn) {
        $this->actingAsUser();
    }

    $response = get($pageUrl->full_url);
    $response->assertOk();

    $html = $response->getContent();
    throw_unless(is_string($html) && $html !== '', LogicException::class);
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    foreach (['logos', 'team'] as $type) {
        expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " section-' . $type . ' ")]//img'))->toHaveCount(1);
    }

    expect($xpath->query('//h1[normalize-space(.)="Public catalogue hero"]'))->toHaveCount(1);
    foreach ([$keys[0], $keys[1], $images[0]->uuid, $images[1]->uuid, 'private-builder-instance', 'private-logo-row', 'private-team-row', 'javascript:', 'media_key', 'block_images'] as $forbidden) {
        expect($html)->not->toContain($forbidden);
    }

    assertCapellPublicOutputIsSafe($response, 'Catalogue builder blocks');
})->with([
    'fallback anonymous' => [false, false], 'fallback non-admin' => [false, true],
    'page-content anonymous' => [true, false], 'page-content non-admin' => [true, true],
]);

it('publishes credential-free builder output to the actual static page cache', function (string $url): void {
    Storage::fake('page_cache');
    config(['capell-html-cache.enabled' => true, 'capell-html-cache.write_enabled' => true, 'capell-html-cache.request_coalescing.enabled' => false]);
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Cached builder credentials');
    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->update(['content_structure_override' => ContentStructure::Blocks->value]);

    $layout = $page->layout ?? throw new LogicException('Expected a layout.');
    $layout->update(['containers' => null]);

    $translation = $page->translations()->firstOrFail();
    $key = (string) Str::uuid();
    $image = Media::factory()->model($translation)->create(['collection_name' => ResolveBuilderBlockMediaAction::collection($key), 'disk' => 'public', 'mime_type' => 'image/png']);
    config(['filesystems.disks.public.visibility' => 'public', 'media-library.url_generator' => BuilderBlockTestUrlGenerator::class, 'testing.builder_block_media_url' => $url]);
    $translation->update(['content' => json_encode([
        ['type' => 'hero', 'data' => ['title' => 'Original credential guard', 'url' => '/photo?access_token=private-structured']],
        ['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Unsafe image', 'image' => $image->uuid, 'url' => $url]]]],
        ['type' => 'hero', 'data' => ['title' => 'Cached public heading', 'url' => $url, 'summary' => '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Read</a><img src="' . htmlspecialchars($url, ENT_QUOTES) . '"><a href="/photo?X-Amz-Credential=private-anchor">Original anchor</a><img src="/photo?X-Goog-Signature=private-image">']],
    ], JSON_THROW_ON_ERROR)]);
    CapellLayoutManager::clearContainerWidgets();
    $response = get($pageUrl->full_url)->assertOk()->assertSee('Cached public heading');
    app()->forgetInstance(HtmlCacheStore::class);
    $request = Request::create($pageUrl->full_url);
    app()->instance('request', $request);
    resolve(HtmlCachePublicationGuard::class)->capture($request);
    expect(resolve(PageCache::class)->cache($request, $response->baseResponse))->toBeTrue();
    $files = array_values(array_filter(Storage::disk('page_cache')->allFiles(), static fn (string $path): bool => str_ends_with($path, '.html')));
    expect($files)->not->toBeEmpty();
    $cached = implode('', array_map(static fn (string $path): string => Storage::disk('page_cache')->get($path) ?? throw new LogicException('Missing cached HTML.'), $files));
    expect($cached)->toContain('<h1', 'Cached public heading')->not->toContain('private-builder-credential')->not->toContain('private-structured')->not->toContain('private-anchor')->not->toContain('private-image')->not->toContain('href=""');
})->with([
    '/photo?caption=%23&access_token=private-builder-credential',
    '/photo?caption=%2523&X-Amz-Signature=private-builder-credential',
    '/photo?caption=%23&credentials%5Btoken%5D=private-builder-credential',
    '#access_token=private-builder-credential',
    '/photo?accessToken=private-builder-credential',
    '/photo?redirect=https%3A%2F%2Fexample.test%2Fimage%3Faccess_token%3Dprivate-builder-credential',
]);

it('renders Article and Event layouts with prepared Widget media in exception mode', function (string $modelClass): void {
    Storage::fake('public');
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.public_view_query_guard.enabled' => true, 'capell-frontend.public_view_query_guard.mode' => 'exception']);
    if ($modelClass === Event::class) {
        CapellCore::forcePackageInstalled('capell-app/events');
        app()->register(EventsServiceProvider::class);
        $this->artisan('migrate', ['--path' => dirname(__DIR__, 3) . '/events/database/migrations', '--realpath' => true])->assertSuccessful();
    }

    layoutNativeThemeCreatePage('default', 'Pageable layouts');
    $demo = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $demo->load('site.language', 'blueprint', 'layout');

    $blueprint = $demo->blueprint ?? throw new LogicException('Expected a blueprint.');
    $layout = $demo->layout ?? throw new LogicException('Expected a layout.');
    $site = $demo->site ?? throw new LogicException('Expected a site.');
    $blueprint->update(['meta' => ['content_structure' => ContentStructure::Blocks->value]]);
    $subject = $modelClass::factory()->create(['site_id' => $demo->site_id, 'blueprint_id' => $demo->blueprint_id, 'layout_id' => $demo->layout_id, 'visible_from' => now()->subDay()]);
    $subject->translations()->create(['language_id' => $site->language_id, 'title' => 'Pageable public title', 'content' => '[]']);
    $widget = Widget::factory()->create(['key' => 'pageable-media', 'component' => WidgetComponentEnum::Default->value, 'visible_from' => now()->subDay()]);
    $widget->blueprint->update(['meta' => ['content_structure' => ContentStructure::Blocks->value]]);

    $translation = $widget->translations()->create(['language_id' => $site->language_id, 'title' => 'Pageable Widget']);
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('pageable.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key) ?? throw new LogicException('Expected a collection.'), 'public');
    $translation->update(['content' => json_encode([
        ['type' => 'hero', 'data' => ['title' => 'Leftover media ignored', 'media_key' => (string) Str::uuid()]],
        ['type' => 'logos', 'data' => ['media_key' => $key, 'logos' => [['name' => 'Pageable partner', 'image' => $image->uuid]]]],
    ], JSON_THROW_ON_ERROR)]);
    $layout->update(['containers' => ['main' => ['meta' => ['colspan' => 12], 'widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $url = PageUrl::factory()->site($demo->site)->page($subject)->language($site->language)->create(['url' => '/pageable-' . class_basename($subject)]);
    CapellLayoutManager::clearContainerWidgets();
    $response = get($url->full_url)->assertOk()->assertSee('Leftover media ignored');
    $document = new DOMDocument;
    $html = $response->getContent();
    throw_unless(is_string($html) && $html !== '', LogicException::class);
    @$document->loadHTML($html);
    expect(new DOMXPath($document)->query('//img[@src="' . $image->getUrl() . '"]'))->toHaveCount(1);
})->with([Article::class, Event::class]);

it('evicts a real static page artifact through the media invalidation observer', function (string $operation): void {
    Storage::fake('public');
    $artifactRoot = storage_path('framework/testing/builder-static-' . Str::uuid());
    config(['filesystems.disks.public.visibility' => 'public', 'capell-frontend.static_artifacts_path' => $artifactRoot]);
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Media eviction');
    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->update(['content_structure_override' => ContentStructure::Blocks->value]);

    $layout = $page->layout ?? throw new LogicException('Expected a layout.');
    $layout->update(['containers' => null]);

    $translation = $page->translations()->firstOrFail();
    $key = (string) Str::uuid();
    $image = $translation->addMedia(UploadedFile::fake()->image('evict.png'))->toMediaCollection(ResolveBuilderBlockMediaAction::collection($key) ?? throw new LogicException('Expected a collection.'), 'public');
    $translation->update(['content' => json_encode([['type' => 'logos', 'data' => ['title' => 'Eviction logos', 'media_key' => $key, 'logos' => [['name' => 'Partner', 'image' => $image->uuid]]]]], JSON_THROW_ON_ERROR)]);
    CapellLayoutManager::clearContainerWidgets();
    try {
        $manifest = GenerateStaticPageArtifactsAction::run($page->site_id, [$pageUrl->url]);
        expect($manifest['artifacts'])->toHaveCount(1);
        $file = data_get($manifest, 'artifacts.0.file');
        throw_unless(is_string($file), LogicException::class);
        $path = $artifactRoot . '/' . $file;
        expect(is_file($path))->toBeTrue()->and(file_get_contents($path))->toContain($image->getUrl());
        DB::transaction(static function () use ($image, $operation): void {
            if ($operation === 'delete') {
                $image->delete();
            } else {
                $image->update(['file_name' => 'replacement.png']);
            }
        });
        expect(is_file($path))->toBeFalse();
    } finally {
        File::deleteDirectory($artifactRoot);
    }
})->with(['rename', 'delete']);
