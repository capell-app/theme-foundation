<?php

declare(strict_types=1);

use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\FormBuilder\Models\Submission;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendKernelInterface;
use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Data\RenderHookContributionData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\Frontend\Support\Loader\SiteResolver;
use Capell\Frontend\Support\Render\RenderHookFragmentRegistry;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Frontend\Support\State\FrontendState;
use Capell\HtmlCache\Http\Middleware\HtmlCacheMiddleware;
use Capell\HtmlCache\Support\Cache\PageCache;
use Capell\Tests\Packages\PackagesTestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\Checksum;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Console\Commands\PrepareFoundationScreenshotEvidenceCommand;
use Workbench\App\Providers\ScreenshotWorkbenchServiceProvider;

beforeEach(function (): void {
    $this->formRequestEnvironment = [
        'CAPELL_SCREENSHOT_WORKBENCH' => getenv('CAPELL_SCREENSHOT_WORKBENCH'),
        'CAPELL_SCREENSHOT_FIXTURE' => getenv('CAPELL_SCREENSHOT_FIXTURE'),
        'CAPELL_SCREENSHOT_APP_PATH' => getenv('CAPELL_SCREENSHOT_APP_PATH'),
    ];
    foreach (['CAPELL_SCREENSHOT_WORKBENCH' => 'true', 'CAPELL_SCREENSHOT_FIXTURE' => 'record-state', 'CAPELL_SCREENSHOT_APP_PATH' => base_path()] as $name => $value) {
        putenv($name . '=' . $value);
    }

    new ScreenshotWorkbenchServiceProvider(app())->register();
    resolve(Kernel::class)->registerCommand(new PrepareFoundationScreenshotEvidenceCommand);
    $this->artisan('capell:theme-foundation-demo', ['--base-url' => 'http://localhost', '--force' => true])->assertSuccessful();
    $this->artisan('capell:prepare-foundation-screenshot-evidence')->assertSuccessful();

    $pageUrl = PageUrl::query()->where('url', '/theme-default-contact')->sole();
    $this->formRequestSite = $pageUrl->site;
    $html = $this->get($pageUrl->full_url)->assertOk()->getContent();
    Assert::assertIsString($html);
    $this->formRequestParent = foundationFormRequestSnapshot($html, 'public-form');
    $loaded = foundationFormRequestUpdate($this, $this->formRequestParent, 'http://localhost', 'loadForm');
    $loadedHtml = $loaded->json('components.0.effects.html');
    Assert::assertIsString($loadedHtml);
    $this->formRequestChild = foundationFormRequestSnapshot($loadedHtml, 'public-form-fields');
    $this->formRequestLoadedParent = $loaded->json('components.0.snapshot');
    Assert::assertIsString($this->formRequestLoadedParent);
});

afterEach(function (): void {
    foreach ($this->formRequestEnvironment as $name => $value) {
        putenv($value === false ? $name : $name . '=' . $value);
    }
});

it('rejects a page snapshot at every HTTP form entry point when its request site is invalid', function (string $invalidSite, string $entryPoint): void {
    $origin = 'http://localhost';
    $site = $this->formRequestSite;
    Assert::assertInstanceOf(Site::class, $site);

    if ($invalidSite === 'another host' || $invalidSite === 'another scheme') {
        $origin = $invalidSite === 'another host' ? 'http://tenant-b.example.test' : 'https://localhost';
        Site::factory()->withTranslations(siteDomainData: [
            'domain' => $invalidSite === 'another host' ? 'tenant-b.example.test' : 'localhost',
            'scheme' => $invalidSite === 'another host' ? 'http' : 'https',
            'path' => '/', 'status' => true, 'default' => true,
        ])->create();
        // The source domain must not also match the destination scheme.
        $site->siteDomains()->update(['scheme' => 'http']);
    } elseif ($invalidSite === 'unknown host') {
        $origin = 'http://unknown.example.test';
    } elseif ($invalidSite === 'disabled') {
        $site->update(['status' => false]);
    } elseif ($invalidSite === 'deleted') {
        $site->delete();
        expect(Site::query()->find($site->getKey()))->toBeNull();
    }

    $snapshot = match ($entryPoint) {
        'load' => $this->formRequestParent,
        'parent update' => $this->formRequestLoadedParent,
        default => $this->formRequestChild,
    };
    $method = match ($entryPoint) {
        'load' => 'loadForm',
        'submit' => 'submit',
        default => null,
    };
    $response = foundationFormRequestUpdate($this, $snapshot, $origin, $method, $entryPoint === 'submit' ? foundationFormRequestInput() : []);
    $html = $response->json('components.0.effects.html');
    Assert::assertIsString($html);
    expect(Submission::query()->count())->toBe(0);
    expect($html)->not->toContain('<form')->not->toContain('data-capell-editor')->not->toContain('signed-editor')
        ->toContain('role="status"');
})->with(['another host', 'another scheme', 'unknown host', 'disabled', 'deleted'])
    ->with(['load', 'parent update', 'child update', 'submit']);

it('renders and submits a legitimate deferred HTTP form on its own site', function (): void {
    $parent = foundationFormRequestUpdate($this, $this->formRequestLoadedParent, 'http://localhost');
    expect($parent->json('components.0.effects.html'))->toContain('wire:id')->not->toContain('role="status"');
    $child = foundationFormRequestUpdate($this, $this->formRequestChild, 'http://localhost');
    expect($child->json('components.0.effects.html'))->toContain('<form');
    $response = foundationFormRequestUpdate($this, $this->formRequestChild, 'http://localhost', 'submit', foundationFormRequestInput());
    expect($response->json('components.0.effects.html'))->toContain('capell-form__message');
    $encodedSnapshot = $response->json('components.0.snapshot');
    Assert::assertIsString($encodedSnapshot);
    $snapshot = json_decode($encodedSnapshot, true, flags: JSON_THROW_ON_ERROR);
    expect(data_get($snapshot, 'data.submitted'))->toBeTrue();
    $submission = Submission::query()->sole();
    expect($submission->site_id)->toBe($this->formRequestSite->getKey());
});

it('refuses wildcard snapshots replayed to a host with another mounted site', function (string $entryPoint): void {
    foundationFormUpdateDomains($this->formRequestSite, ['domain' => null, 'scheme' => 'http', 'path' => '/']);
    $other = Site::factory()->withTranslations(siteDomainData: [
        'domain' => 'tenant-b.example.test', 'scheme' => 'http', 'path' => '/tenant-b',
        'status' => true, 'default' => true,
    ])->create();
    [$resolved] = SiteResolver::resolve('http://tenant-b.example.test/tenant-b/contact', SiteLoader::getSites());
    expect($resolved->getKey())->toBe($other->getKey());

    $response = foundationFormEntryRequest($this, $entryPoint, 'http://tenant-b.example.test');
    expect($response->json('components.0.effects.html'))->toContain('role="status"')->not->toContain('<form');
    expect(Submission::query()->count())->toBe(0);
})->with(['load', 'parent update', 'child update', 'submit']);

it('resolves mounted forms at every HTTP entry point using their original page path', function (string $entryPoint): void {
    foundationFormUpdateDomains($this->formRequestSite, ['path' => '/tenant-a']);
    foundationFormIssueSnapshots($this, 'http://localhost/tenant-a/theme-default-contact');
    $response = foundationFormEntryRequest($this, $entryPoint, 'http://localhost');
    expect($response->json('components.0.effects.html'))->not->toContain(__('capell-form-builder::message.form_unavailable'));
    if ($entryPoint === 'submit') {
        $submission = Submission::query()->sole();
        expect($submission->site_id)->toBe($this->formRequestSite->getKey())
            ->and($submission->meta->url)->toBe('http://localhost/tenant-a/theme-default-contact');
    }
})->with(['load', 'parent update', 'child update', 'submit']);

it('performs one persisted site check on rejected HTTP form requests', function (string $entryPoint): void {
    Site::factory()->withTranslations(siteDomainData: [
        'domain' => 'tenant-b.example.test', 'scheme' => 'http', 'path' => '/',
        'status' => true, 'default' => true,
    ])->create();
    SiteResolver::resolve('http://tenant-b.example.test/contact', SiteLoader::getSites());
    DB::enableQueryLog();
    DB::flushQueryLog();
    foundationFormEntryRequest($this, $entryPoint, 'http://tenant-b.example.test');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    $siteQueries = array_filter($queries, static fn (array $query): bool => preg_match('/^select .* from ["`]sites["`]/i', $query['query']) === 1);
    expect(count($siteQueries))->toBe(1);
    expect(Submission::query()->count())->toBe(0);
})->with(['load', 'parent update', 'child update', 'submit']);

it('accepts only the issuing origin and resolved site across the HTTP topology matrix', function (string $topology, string $entryPoint): void {
    $site = $this->formRequestSite;
    $source = 'http://localhost';
    $target = $source;
    $path = '/theme-default-contact';
    $accepted = true;

    if (in_array($topology, ['alias', 'www', 'staging', 'port'], true)) {
        $host = match ($topology) {
            'alias' => 'alias.example.test', 'www' => 'www.example.test',
            'staging' => 'staging.example.test', default => 'localhost',
        };
        $domain = $site->siteDomains()->firstOrFail()->replicate();
        $domain->fill(['domain' => $host, 'scheme' => 'http', 'port' => $topology === 'port' ? 8088 : null]);
        $domain->save();
        $source = 'http://' . $host . ($topology === 'port' ? ':8088' : '');
        $target = $source;
    } elseif (in_array($topology, ['wildcard', 'wildcard replay'], true)) {
        foundationFormUpdateDomains($site, ['domain' => null, 'scheme' => 'http']);
        $source = 'http://wildcard.example.test';
        $target = $topology === 'wildcard' ? $source : 'http://different.example.test';
        $accepted = $topology === 'wildcard';
    } elseif (in_array($topology, ['shared mount', 'reassigned mount'], true)) {
        foundationFormUpdateDomains($site, ['path' => '/tenant-a']);
        Site::factory()->withTranslations(siteDomainData: [
            'domain' => 'localhost', 'scheme' => 'http', 'path' => '/',
            'status' => true, 'default' => true,
        ])->create();
        $path = '/tenant-a/theme-default-contact';
        $accepted = $topology === 'shared mount';
    } elseif (str_starts_with($topology, 'default ')) {
        config(['capell-frontend.redirect_default_site' => $topology === 'default on']);
        $target = 'http://unknown.example.test';
        $accepted = false;
    }

    foundationFormIssueSnapshots($this, $source . $path);
    if ($topology === 'reassigned mount') {
        foundationFormUpdateDomains($site, ['path' => '/retired']);
    }
    $resolved = null;
    try {
        [$resolved] = SiteResolver::resolve($target . $path, SiteLoader::getSites());
    } catch (Throwable) {
        // Unknown hosts redirect or fail on normal pages; neither grants form authority.
    }
    expect($source === $target && $resolved?->getKey() === $site->getKey())->toBe($accepted);
    $response = foundationFormEntryRequest($this, $entryPoint, $target);
    $html = $response->json('components.0.effects.html');
    Assert::assertIsString($html);
    if ($accepted) {
        expect($html)->not->toContain(__('capell-form-builder::message.form_unavailable'));
        if ($entryPoint === 'submit') {
            $submission = Submission::query()->sole();
            expect($submission->site_id)->toBe($site->getKey())->and($submission->meta->url)->toBe($target . $path);
        }
    } else {
        expect($html)->toContain('role="status"')->not->toContain('<form');
        expect(Submission::query()->count())->toBe(0);
    }
})->with(['root', 'alias', 'www', 'staging', 'port', 'wildcard', 'wildcard replay', 'shared mount', 'reassigned mount', 'default off', 'default on'])
    ->with(['load', 'parent update', 'child update', 'submit']);

function foundationFormIssueSnapshots(PackagesTestCase $test, string $url): void
{
    foundationFormResetRequestState();
    $page = $test->get($url);
    Assert::assertSame(200, $page->status(), 'Unexpected page redirect: ' . $page->headers->get('Location', 'none'));
    $html = $page->getContent();
    Assert::assertIsString($html);
    $test->formRequestParent = foundationFormRequestSnapshot($html, 'public-form');
    $origin = preg_replace('~^(https?://[^/]+).*$~', '$1', $url);
    Assert::assertIsString($origin);
    $loaded = foundationFormRequestUpdate($test, $test->formRequestParent, $origin, 'loadForm');
    $loadedHtml = $loaded->json('components.0.effects.html');
    Assert::assertIsString($loadedHtml);
    expect($loadedHtml)->toContain('<form');
    $test->formRequestChild = foundationFormRequestSnapshot($loadedHtml, 'public-form-fields');
    $test->formRequestLoadedParent = $loaded->json('components.0.snapshot');
    Assert::assertIsString($test->formRequestLoadedParent);
}

/** @return TestResponse<Response> */
function foundationFormEntryRequest(PackagesTestCase $test, string $entryPoint, string $origin): TestResponse
{
    $snapshot = match ($entryPoint) {
        'load' => $test->formRequestParent,
        'parent update' => $test->formRequestLoadedParent,
        default => $test->formRequestChild,
    };
    $method = match ($entryPoint) {
        'load' => 'loadForm', 'submit' => 'submit', default => null,
    };

    return foundationFormRequestUpdate($test, $snapshot, $origin, $method, $entryPoint === 'submit' ? foundationFormRequestInput() : []);
}

it('falls back through HTTP when a parent site reference cannot be decrypted', function (): void {
    $snapshotData = json_decode($this->formRequestParent, true, flags: JSON_THROW_ON_ERROR);
    Assert::assertIsArray($snapshotData);
    $data = $snapshotData['data'] ?? null;
    Assert::assertIsArray($data);
    $data['siteReference'] = 'invalid-encrypted-reference';
    $snapshotData['data'] = $data;
    unset($snapshotData['checksum']);
    $snapshotData['checksum'] = Checksum::generate($snapshotData);
    $snapshot = json_encode($snapshotData, JSON_THROW_ON_ERROR);
    $response = foundationFormRequestUpdate($this, $snapshot, 'http://localhost', 'loadForm');
    expect($response->json('components.0.effects.html'))->toContain('role="status"')->not->toContain('<form');
    expect(Submission::query()->count())->toBe(0);
});

it('falls back through HTTP when a child form reference cannot be decrypted', function (): void {
    $response = foundationFormRequestUpdate($this, $this->formRequestChild, 'http://localhost', 'submit', [
        ...foundationFormRequestInput(), 'formReference' => 'invalid-encrypted-reference',
    ]);
    expect($response->json('components.0.effects.html'))->toContain(__('capell-form-builder::message.form_unavailable'))->not->toContain('<form');
    expect(Submission::query()->count())->toBe(0);
});

it('refuses legacy snapshots without an issuing origin at every HTTP entry point', function (string $entryPoint): void {
    foreach (['formRequestParent', 'formRequestLoadedParent', 'formRequestChild'] as $property) {
        $snapshot = json_decode($this->{$property}, true, flags: JSON_THROW_ON_ERROR);
        Assert::assertIsArray($snapshot);
        Assert::assertIsArray($snapshot['memo']);
        unset($snapshot['memo']['origin'], $snapshot['checksum']);
        $snapshot['checksum'] = Checksum::generate($snapshot);
        $this->{$property} = json_encode($snapshot, JSON_THROW_ON_ERROR);
    }
    $response = foundationFormEntryRequest($this, $entryPoint, 'http://localhost');
    expect($response->json('components.0.effects.html'))->toContain('role="status"')->not->toContain('<form');
    expect(Submission::query()->count())->toBe(0);
})->with(['load', 'parent update', 'child update', 'submit']);

it('rejects unsigned changes to the originating path or origin before any form work', function (string $field): void {
    config(['app.debug' => false]);
    $snapshot = json_decode($this->formRequestChild, true, flags: JSON_THROW_ON_ERROR);
    Assert::assertIsArray($snapshot);
    Assert::assertIsArray($snapshot['memo']);
    $snapshot['memo'][$field] = match ($field) {
        'path' => 'tenant-b/contact', 'basePath' => '/tenant-b', default => 'http://tenant-b.example.test',
    };
    foundationFormResetRequestState();
    $this->postJson('http://localhost' . Livewire::getUpdateUri(), [
        'components' => [[
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'updates' => foundationFormRequestInput(),
            'calls' => [['path' => '', 'method' => 'submit', 'params' => []]],
        ]],
    ], ['X-Livewire' => 'true'])->assertStatus(419);
    expect(Submission::query()->count())->toBe(0);
})->with(['path', 'origin', 'basePath']);

it('retains form authority through an actual HTML cache hit for another visitor', function (string $delivery, string $mount): void {
    foundationFormUpdateDomains($this->formRequestSite, ['path' => $mount === '' ? '/' : $mount]);
    $disk = Storage::fake('page_cache');
    app()->instance(PageCache::class, new PageCache(new Filesystem)->setContainer(app())->setCachePath($disk->path('')));
    config([
        'capell-html-cache.enabled' => true, 'capell-html-cache.write_enabled' => true,
        'capell-html-cache.hit_recording.enabled' => false,
        'capell-html-cache.origin_stale_while_revalidate.enabled' => false,
    ]);
    $handle = data_get(json_decode($this->formRequestParent, true, flags: JSON_THROW_ON_ERROR), 'data.formHandle');
    Assert::assertIsString($handle);
    $registry = new RenderHookRegistry;
    app()->instance(RenderHookRegistry::class, $registry);
    app()->instance(RenderHookFragmentRegistry::class, new RenderHookFragmentRegistry);
    $extension = new class($handle) implements RenderHookExtensionInterface
    {
        public function __construct(private readonly string $handle) {}

        #[Override]
        public function render(RenderHookContext $context): string
        {
            [$site] = SiteResolver::resolve(request()->fullUrl(), SiteLoader::getSites());
            app()->instance(FrontendContextReader::class, new FrontendState()->withSite($site));
            Frontend::clearResolvedInstance(FrontendContextReader::class);

            return Livewire::mount('public-form', ['handle' => $this->handle]);
        }
    };
    $registry->contribute(RenderHookContributionData::extension(
        location: RenderHookLocation::BodyEnd,
        extension: $extension,
        owner: 'test/public-form',
        key: 'form',
        cacheSafe: false,
        fragment: true,
    ));
    $path = $mount . '/cached-contact';
    Route::get($path, static fn (): Illuminate\Http\Response => response(
        $delivery === 'fragment'
            ? $registry->renderAll(RenderHookLocation::BodyEnd)
            : $extension->render(new RenderHookContext(RenderHookLocation::BodyEnd->value, null)),
        headers: ['Content-Type' => 'text/html'],
    ))->middleware(HtmlCacheMiddleware::class);

    foundationFormResetRequestState();
    $this->get('http://localhost' . $path)->assertOk();
    foundationFormResetRequestState();
    $cached = $this->withSession(['visitor' => 'second'])->get('http://localhost' . $path)
        ->assertOk()->assertHeader('X-Frontend-Cache', 'HIT');
    $html = $cached->getContent();
    Assert::assertIsString($html);
    $parent = foundationFormRequestSnapshot($html, 'public-form');
    $loaded = foundationFormRequestUpdate($this, $parent, 'http://localhost', 'loadForm');
    $loadedHtml = $loaded->json('components.0.effects.html');
    Assert::assertIsString($loadedHtml);
    expect($loadedHtml)->toContain('<form');
    $child = foundationFormRequestSnapshot($loadedHtml, 'public-form-fields');
    foundationFormRequestUpdate($this, $child, 'http://localhost', 'submit', foundationFormRequestInput());
    $submission = Submission::query()->sole();
    expect($submission->site_id)->toBe($this->formRequestSite->getKey())
        ->and($submission->meta->url)->toBe('http://localhost' . $path);

    foundationFormUpdateDomains($this->formRequestSite, ['domain' => null]);
    $replay = foundationFormRequestUpdate($this, $parent, 'http://elsewhere.example.test', 'loadForm');
    expect($replay->json('components.0.effects.html'))->toContain('role="status"')->not->toContain('<form');
    expect(Submission::query()->count())->toBe(1);
})->with(['page', 'fragment'])->with(['root' => '', 'mounted' => '/tenant-a']);

it('keeps bundled components bound to their own verified origin', function (bool $foreignFirst): void {
    $local = $this->formRequestChild;
    $domain = $this->formRequestSite->siteDomains()->firstOrFail()->replicate();
    $domain->fill(['domain' => 'alias.example.test', 'scheme' => 'http'])->save();
    foundationFormIssueSnapshots($this, 'http://alias.example.test/theme-default-contact');
    $snapshots = $foreignFirst ? [$this->formRequestChild, $local] : [$local, $this->formRequestChild];
    foundationFormResetRequestState();
    $response = $this->postJson('http://localhost' . Livewire::getUpdateUri(), [
        'components' => array_map(static fn (string $snapshot): array => [
            'snapshot' => $snapshot, 'updates' => new stdClass, 'calls' => [],
        ], $snapshots),
    ], ['X-Livewire' => 'true'])->assertOk();
    expect($response->json('components.' . ($foreignFirst ? 0 : 1) . '.effects.html'))->toContain('role="status"')->not->toContain('<form');
    expect($response->json('components.' . ($foreignFirst ? 1 : 0) . '.effects.html'))->toContain('<form');
    expect(Submission::query()->count())->toBe(0);
})->with([false, true]);

it('uses trusted proxy origin semantics for HTTP forms without trusting a referer', function (bool $trusted, string $entryPoint): void {
    $previousProxies = Request::getTrustedProxies();
    $previousHeaders = Request::getTrustedHeaderSet();
    if ($previousHeaders < 0 || $previousHeaders > 63) {
        throw new LogicException('Unexpected trusted proxy header mask.');
    }
    TrustProxies::at($trusted ? ['127.0.0.1'] : []);
    TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
    try {
        $domain = $this->formRequestSite->siteDomains()->firstOrFail()->replicate();
        $domain->fill(['domain' => 'proxy.example.test', 'scheme' => 'https', 'port' => 8443])->save();
        $this->withHeaders([
            'X-Forwarded-Host' => 'proxy.example.test', 'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => '8443', 'Referer' => 'https://foreign.example.test/tenant-b',
        ]);
        foundationFormIssueSnapshots($this, 'http://localhost/theme-default-contact');
        expect(data_get(json_decode($this->formRequestParent, true, flags: JSON_THROW_ON_ERROR), 'memo.origin'))
            ->toBe($trusted ? 'https://proxy.example.test:8443' : 'http://localhost');
        $response = foundationFormEntryRequest($this, $entryPoint, 'http://localhost');
        expect($response->json('components.0.effects.html'))->not->toContain(__('capell-form-builder::message.form_unavailable'));
        if ($entryPoint === 'submit') {
            expect(Submission::query()->sole()->meta->url)->toBe(($trusted ? 'https://proxy.example.test:8443' : 'http://localhost') . '/theme-default-contact');
        }
    } finally {
        TrustProxies::flushState();
        Request::setTrustedProxies($previousProxies, $previousHeaders);
    }
})->with([false, true])->with(['load', 'parent update', 'child update', 'submit']);

it('retains a trusted proxy mount prefix at every HTTP form entry point', function (string $entryPoint): void {
    $previousProxies = Request::getTrustedProxies();
    $previousHeaders = Request::getTrustedHeaderSet();
    if ($previousHeaders < 0 || $previousHeaders > 63) {
        throw new LogicException('Unexpected trusted proxy header mask.');
    }
    TrustProxies::at(['127.0.0.1']);
    TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_PREFIX);
    try {
        foundationFormUpdateDomains($this->formRequestSite, ['path' => '/tenant-a']);
        $this->withHeaders(['X-Forwarded-Prefix' => '/tenant-a']);
        foundationFormIssueSnapshots($this, 'http://localhost/theme-default-contact');
        $response = foundationFormEntryRequest($this, $entryPoint, 'http://localhost');
        expect($response->json('components.0.effects.html'))->not->toContain(__('capell-form-builder::message.form_unavailable'));
        if ($entryPoint === 'submit') {
            $submission = Submission::query()->sole();
            expect($submission->site_id)->toBe($this->formRequestSite->getKey())
                ->and($submission->meta->url)->toBe('http://localhost/tenant-a/theme-default-contact');
        }
    } finally {
        TrustProxies::flushState();
        Request::setTrustedProxies($previousProxies, $previousHeaders);
    }
})->with(['load', 'parent update', 'child update', 'submit']);

function foundationFormRequestSnapshot(string $html, string $name): string
{
    if ($html === '') {
        throw new RuntimeException('Expected public form HTML.');
    }

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $elements = new DOMXPath($document)->query('//*[@*[name()="wire:snapshot"]]');
    Assert::assertNotFalse($elements);
    foreach ($elements as $element) {
        Assert::assertInstanceOf(DOMElement::class, $element);
        $snapshot = $element->getAttribute('wire:snapshot');
        if (data_get(json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR), 'memo.name') === $name) {
            return $snapshot;
        }
    }

    throw new RuntimeException('Expected public form snapshot.');
}

/**
 * @param  array<string, mixed>  $updates
 * @return TestResponse<Response>
 */
function foundationFormRequestUpdate(PackagesTestCase $test, string $snapshot, string $origin, ?string $method = null, array $updates = []): TestResponse
{
    $updateUri = Livewire::getUpdateUri();
    $basePath = request()->getBaseUrl();
    if ($basePath !== '' && str_starts_with($updateUri, $basePath . '/')) {
        // The HTTP kernel receives the backend path after the proxy strips its prefix.
        $updateUri = substr($updateUri, strlen($basePath));
    }
    foundationFormResetRequestState();

    return $test->postJson($origin . $updateUri, [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => $updates === [] ? new stdClass : $updates,
            'calls' => $method === null ? [] : [['path' => '', 'method' => $method, 'params' => []]],
        ]],
    ], ['X-Livewire' => 'true', 'Host' => (string) parse_url($origin, PHP_URL_HOST) . (parse_url($origin, PHP_URL_PORT) ? ':' . parse_url($origin, PHP_URL_PORT) : '')])->assertOk();
}

function foundationFormResetRequestState(): void
{
    // These are separate HTTP requests sharing one test application. Discard
    // the previous kernel's context and Livewire's removed-child bookkeeping.
    Livewire::flushState();
    \Livewire\store()->unset('removedChildren');
    $state = new FrontendState;
    app()->instance(FrontendState::class, $state);
    app()->instance(FrontendContextReader::class, $state);
    app()->forgetInstance(FrontendKernelInterface::class);
    Frontend::clearResolvedInstance(FrontendContextReader::class);
}

/** @return array<string, string> */
function foundationFormRequestInput(): array
{
    return [
        'data.enquiry_type' => 'project', 'data.name' => 'Enquiry test',
        'data.email' => 'enquiry@example.test', 'data.message' => 'Request site boundary test.',
    ];
}

/** @param array<string, mixed> $attributes */
function foundationFormUpdateDomains(Site $site, array $attributes): void
{
    // Model events invalidate the frontend domain cache, as in an admin edit.
    foreach ($site->siteDomains()->get() as $domain) {
        $domain->update($attributes);
    }
}
