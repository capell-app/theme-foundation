<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\AuthMenuWidget;
use Capell\Frontend\Actions\BuildPublicPageRenderDataAction;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\BuildPublicWidgetInteractionLocatorsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RebuildPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\Tests\Fixtures\Models\User;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    (new FoundationThemeServiceProvider(app()))->packageBooted();

    Route::get('/cap-0234-login', static fn (): string => 'Login')->name('login');
    Route::get('/cap-0234-account', static fn (): string => 'Account')->name('dashboard');
    Route::getRoutes()->refreshNameLookups();
});

it('keeps auth-state markup out of anonymous and authenticated cacheable public header responses', function (): void {
    $fixture = authMenuFixture();
    authMenuRegisterPublicPageRoute($fixture);
    $user = User::factory()->create([
        'name' => 'Morgan Customer',
        'email' => 'morgan@example.test',
    ]);

    $responses = [
        $this->get('/cap-0234-auth-menu-page'),
        $this->actingAs($user)->get('/cap-0234-auth-menu-page'),
    ];

    foreach ($responses as $response) {
        $response->assertOk();

        expect((string) $response->headers->get('Cache-Control'))
            ->toContain('public', 'max-age=300');

        $html = (string) $response->getContent();
        $document = authMenuDocument($html);
        $placeholders = authMenuQuery(
            $document,
            '//*[@data-auth-menu-placeholder and @data-deferred-fragment and @data-deferred-fragment-refresh="pageshow"]',
        );
        $placeholder = authMenuFirstElement($placeholders, 'Expected the public header to contain one auth-menu placeholder.');
        $links = authMenuQuery($document, '//*[@data-auth-menu-placeholder]//a[@href]');

        expect($placeholders)->toHaveCount(1)
            ->and($links)->toHaveCount(1)
            ->and(authMenuFirstElement($links, 'Expected a no-JavaScript login link.')->getAttribute('href'))->toBe(route('login'))
            ->and(authMenuQuery($document, '//details'))->toHaveCount(0)
            ->and($placeholder->getAttribute('data-auth-menu-login-url'))->toBe(route('login'))
            ->and($html)->not->toContain(
                'Morgan Customer',
                '>MC<',
                'Account menu',
                '__capell',
                'instance_id',
                'state_version',
                'field_path',
                'permission',
                'editor_url',
                'selector',
                'capell-app.auth-menu',
                'signed',
            );
    }
});

it('serves a guest login link through the lazy widget endpoint with private no-store caching', function (): void {
    $fixture = authMenuFixture();

    $response = $this->get($fixture['endpointPath']);

    $response->assertOk()
        ->assertHeaderContains('Cache-Control', 'private')
        ->assertHeaderContains('Cache-Control', 'no-store');

    $html = (string) $response->getContent();
    $document = authMenuDocument($html);
    $links = authMenuQuery($document, '//a[@href]');

    expect($links)->toHaveCount(1)
        ->and(authMenuFirstElement($links, 'Expected a guest login link.')->getAttribute('href'))->toBe(route('login'))
        ->and(authMenuQuery($document, '//details'))->toHaveCount(0)
        ->and($html)->not->toContain(
            'Morgan Customer',
            'Account menu',
            '__capell',
            'instance_id',
            'state_version',
            'field_path',
            'permission',
            'editor_url',
            'selector',
            'signed',
        );
});

it('serves the authenticated account menu only through the private lazy widget endpoint', function (): void {
    $fixture = authMenuFixture();
    $user = User::factory()->create([
        'name' => 'Morgan Customer',
        'email' => 'morgan@example.test',
    ]);

    $response = $this->actingAs($user)->get($fixture['endpointPath']);

    $response->assertOk()
        ->assertHeaderContains('Cache-Control', 'private')
        ->assertHeaderContains('Cache-Control', 'no-store');

    $html = (string) $response->getContent();
    $document = authMenuDocument($html);
    $accountMenu = authMenuQuery($document, '//summary[@aria-label="Account menu"]');
    $links = authMenuQuery($document, '//a[@href]');

    expect(authMenuQuery($document, '//details'))->toHaveCount(1)
        ->and($accountMenu)->toHaveCount(1)
        ->and(authMenuFirstElement($accountMenu, 'Expected an account menu summary.')->textContent)->toContain('MC', 'Morgan Customer')
        ->and($links)->toHaveCount(1)
        ->and(authMenuFirstElement($links, 'Expected an account link.')->getAttribute('href'))->toBe(route('dashboard'))
        ->and($html)->not->toContain(
            'Log in',
            '__capell',
            'instance_id',
            'state_version',
            'field_path',
            'permission',
            'editor_url',
            'selector',
            'signed',
        );
});

/**
 * @return array{context: FrontendRenderContextData, endpointPath: string, language: Language, layout: Layout, page: Page, site: Site, theme: Theme}
 */
function authMenuFixture(): array
{
    $language = Language::factory()->createOne(['code' => 'en', 'status' => true]);
    $theme = Theme::factory()->default()->createOne();
    $site = Site::factory()
        ->theme($theme)
        ->language($language)
        ->withTranslations($language)
        ->createOne(['status' => true]);
    $layout = Layout::factory()
        ->site($site)
        ->createOne([
            'containers' => [
                'header' => [
                    'meta' => [
                        'area' => 'header',
                        'alignment' => 'end',
                        'colspan' => 12,
                        'html_class' => 'ml-auto flex w-auto items-center',
                    ],
                    'widgets' => [AuthMenuWidget::block()],
                ],
            ],
        ]);
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->withTranslations($language, ['content' => [], 'title' => 'Auth menu'], slug: '/auth-menu')
        ->createOne();
    $page->setRelation('translation', $page->translations()->firstOrFail());

    SiteDomain::query()->updateOrCreate([
        'site_id' => $site->id,
        'domain' => 'localhost',
        'path' => null,
    ], [
        'scheme' => 'http',
        'language_id' => $language->id,
        'status' => true,
        'default' => true,
    ]);

    expect(resolve(WidgetExtensionRegistry::class)->definition(AuthMenuWidget::KEY))->not->toBeNull();

    $context = new FrontendRenderContextData($page, $site, $language, $layout, $theme);
    RebuildPublicWidgetSnapshotsAction::run($context);

    $locator = resolve(BuildPublicWidgetInteractionLocatorsAction::class)
        ->build($context)[AuthMenuWidget::INSTANCE_ID] ?? null;
    $endpointPath = is_string($locator) ? parse_url($locator, PHP_URL_PATH) : null;

    throw_unless(
        is_string($endpointPath) && str_starts_with($endpointPath, '/_capell/layout-widgets/'),
        RuntimeException::class,
        'Expected the auth menu to use the session-aware lazy layout widget endpoint.',
    );

    return [
        'context' => $context,
        'endpointPath' => $endpointPath,
        'language' => $language,
        'layout' => $layout,
        'page' => $page,
        'site' => $site,
        'theme' => $theme,
    ];
}

/**
 * @param  array{context: FrontendRenderContextData, endpointPath: string, language: Language, layout: Layout, page: Page, site: Site, theme: Theme}  $fixture
 */
function authMenuRegisterPublicPageRoute(array $fixture): void
{
    Route::get('/cap-0234-auth-menu-page', function () use ($fixture): Response {
        $renderData = BuildPublicPageRenderDataAction::run($fixture['context']);

        app()->instance(
            FrontendContextReader::class,
            (new FrontendState)
                ->withSite($fixture['site'])
                ->withLanguage($fixture['language'])
                ->withPage($fixture['page'])
                ->withLayout($fixture['layout'])
                ->withTheme($fixture['theme'])
                ->setFrontendData('publicPageRenderData', $renderData),
        );

        $header = view('capell-layout-builder::components.layout.area', [
            'area' => 'header',
            'layout' => $fixture['layout'],
        ])->render();

        return response(
            '<!doctype html><html><head><title>Auth menu</title></head><body>' . $header . '</body></html>',
            headers: ['Cache-Control' => 'public, max-age=300'],
        );
    });
}

function authMenuDocument(string $html): DOMXPath
{
    if ($html === '') {
        throw new RuntimeException('Expected an HTML response.');
    }

    $document = new DOMDocument;
    $previousUseInternalErrors = libxml_use_internal_errors(true);

    try {
        $document->loadHTML($html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);
    }

    return new DOMXPath($document);
}

function authMenuQuery(DOMXPath $document, string $expression): DOMNodeList
{
    $nodes = $document->query($expression);

    if ($nodes === false) {
        throw new RuntimeException("Unable to query auth-menu response DOM [{$expression}].");
    }

    return $nodes;
}

function authMenuFirstElement(DOMNodeList $nodes, string $message): DOMElement
{
    $node = $nodes->item(0);

    if (! $node instanceof DOMElement) {
        throw new RuntimeException($message);
    }

    return $node;
}
