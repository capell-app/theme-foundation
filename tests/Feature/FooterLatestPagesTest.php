<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\BuildFooterLatestPageLinksAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;

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

    test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        [
            'pages' => $pages,
            'linkedPages' => BuildFooterLatestPageLinksAction::run($pages),
        ],
    )
        ->assertSee('Latest Pages')
        ->assertElementExists('a[href="https://example.test/resources"]')
        ->assertSee('Resources');
});

it('does not render latest pages when the provided page collection is empty', function (): void {
    test()->blade(
        '<x-capell::footer.latest-pages heading-class="footer-heading" :pages="$pages" :linked-pages="$linkedPages" />',
        [
            'pages' => new Collection,
            'linkedPages' => collect(),
        ],
    )
        ->assertDontSee('Latest Pages');
});
