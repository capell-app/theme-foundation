<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\FoundationTheme\Actions\BuildFooterLatestPageLinksAction;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Assert;

beforeEach(function (): void {
    Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
});

it('renders latest pages from the provided page collection', function (): void {
    $page = new class
    {
        public object $pageUrl;

        public string $name = 'Fallback Page Name';

        public function __construct()
        {
            $this->pageUrl = (object) ['full_url' => 'https://example.test/resources'];
        }

        public function getTranslation(string $key): ?string
        {
            return $key === 'title' ? 'Resources' : null;
        }
    };

    $pages = new Collection([$page]);

    $view = test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        [
            'pages' => $pages,
            'linkedPages' => BuildFooterLatestPageLinksAction::run($pages),
        ],
    );
    Assert::assertInstanceOf(TestView::class, $view);

    $view->assertSee('Latest Pages')
        ->assertSee('Resources');
    $view->assertElementExists('a[href="https://example.test/resources"]');
});

it('does not render latest pages when the provided page collection is empty', function (): void {
    $view = test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        [
            'pages' => new Collection,
            'linkedPages' => collect(),
        ],
    );
    Assert::assertInstanceOf(TestView::class, $view);

    $view->assertDontSee('Latest Pages');
});

it('uses the same short navigation labels as the header for footer page links', function (): void {
    $language = new Language(['code' => 'en']);
    $language->id = 1;
    $site = new Site(['name' => 'Field Office']);
    $site->setRelation('language', $language);
    $translation = new Translation([
        'language_id' => 1,
        'title' => 'Welcome to our field notes | Field Office',
        'meta' => ['label' => 'Notes | Field Office'],
    ]);
    $page = new Page(['name' => 'Welcome']);
    $page->setRelation('site', $site);
    $page->setRelation('translation', $translation);
    $page->setRelation('translations', new EloquentCollection([$translation]));
    $pageUrl = new PageUrl(['url' => '/notes']);
    $pageUrl->setRelation('siteDomain', new SiteDomain(['domain' => 'example.test', 'scheme' => 'https']));
    $page->setRelation('pageUrl', $pageUrl);
    $pages = collect([$page]);

    $view = test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        ['pages' => $pages, 'linkedPages' => BuildFooterLatestPageLinksAction::run($pages)],
    );
    Assert::assertInstanceOf(TestView::class, $view);

    $view->assertSee('Notes')->assertDontSee('Notes | Field Office')->assertDontSee('Welcome');
});

it('uses the current French language for footer labels on an English-default site', function (bool $hasEnglishTranslation, ?string $label): void {
    $english = new Language(['code' => 'en']);
    $english->id = 1;
    $french = new Language(['code' => 'fr']);
    $french->id = 2;
    $site = new Site(['name' => 'Field Office']);
    $site->setRelation('language', $english);
    $frenchTranslation = new Translation([
        'language_id' => $french->id,
        'title' => 'Conseils | Field Office',
        'meta' => ['label' => $label],
    ]);
    $translations = new EloquentCollection([$frenchTranslation]);

    if ($hasEnglishTranslation) {
        $translations->push(new Translation([
            'language_id' => $english->id,
            'title' => 'Field notes | Field Office',
            'meta' => ['label' => 'Notes | Field Office'],
        ]));
    }

    $page = new Page(['name' => 'Internal page name']);
    $page->setRelation('site', $site);
    $page->setRelation('translation', $frenchTranslation);
    $page->setRelation('translations', $translations);
    $pageUrl = new PageUrl(['url' => '/fr/conseils']);
    $pageUrl->setRelation('siteDomain', new SiteDomain(['domain' => 'example.test', 'scheme' => 'https']));
    $page->setRelation('pageUrl', $pageUrl);
    Frontend::swap(new FrontendContext(
        site: $site,
        language: $french,
        page: $page,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    ));
    $pages = collect([$page]);
    $linkedPages = BuildFooterLatestPageLinksAction::run($pages);

    expect($linkedPages->first()?->label)->toBe('Conseils')
        ->toBe(NavigationCreator::getPageNavigationLabel($page, $french));

    $view = test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        ['pages' => $pages, 'linkedPages' => $linkedPages],
    );
    Assert::assertInstanceOf(TestView::class, $view);

    $view->assertElementExists('a[href="https://example.test/fr/conseils"]');
    $view->assertSee('Conseils')
        ->assertDontSee('Notes')
        ->assertDontSee('Internal page name')
        ->assertDontSee('Field Office');
})->with([
    'French navigation label with an English translation' => [true, 'Conseils | Field Office'],
    'French title without an English translation' => [false, null],
]);
