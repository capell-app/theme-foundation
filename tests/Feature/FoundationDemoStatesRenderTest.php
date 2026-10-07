<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Actions\ResolveThemeFormEmbedDataAction;
use Capell\FoundationTheme\Support\CapellOptionalExtensionAvailability;
use Capell\Smart404\Actions\ResolveSmart404SuggestionsAction;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Http\Request;
use Livewire\LivewireManager;

use function Pest\Livewire\livewire;

use PHPUnit\Framework\Assert;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

function foundationDemoStateDom(string $html): DOMXPath
{
    if ($html === '') {
        throw new RuntimeException('Expected rendered Foundation demo HTML.');
    }

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

afterEach(function (): void {
    CapellCore::clearPackages();
});

it('renders the empty demo without populated results for public visitors', function (bool $signedIn): void {
    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Empty State', 'empty');
    $response = $this->get($pageUrl->full_url)->assertOk();
    $html = (string) $response->getContent();
    $dom = foundationDemoStateDom($html);

    expect($dom->query('//main//h1[normalize-space(.) != ""]'))->toHaveCount(1)
        ->and($dom->query('//section[contains(@class, "theme-search")]//li'))->toHaveCount(0)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "0 results")]'))->toHaveCount(1)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "No matching results")]'))->toHaveCount(1)
        ->and($dom->query('//*[@data-editor or @data-widget-id or @*[name()="wire:snapshot"]]'))->toHaveCount(0)
        ->and($html)->not->toContain('capell-theme-foundation::');
})->with([false, true]);

it('renders suggestions without search counts or unnecessary pagination', function (string $surface): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Populated State', $surface);
    $response = $this->get($pageUrl->full_url)->assertOk();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//section[contains(@class, "theme-search")]//li'))->toHaveCount(2)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "2 results")]'))->toHaveCount(0)
        ->and($dom->query('//section[contains(@class, "theme-pagination")]'))->toHaveCount(0)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "No matching results")]'))->toHaveCount(0)
        ->and($dom->query('//section[contains(@class, "theme-content-listing")]//a/img/following-sibling::span[contains(concat(" ", @class, " "), " block ") and contains(concat(" ", @class, " "), " p-5 ")]'))->toHaveCount(3)
        ->and($dom->query('//section[contains(@class, "theme-content-listing")]//a/img/following-sibling::span/span[contains(concat(" ", @class, " "), " mt-2 ") and contains(concat(" ", @class, " "), " block ")]'))->toHaveCount(6);
})->with(['homepage', 'directory']);

it('renders inset spaced recovery suggestions on the not found demo', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Not Found State', 'not-found');
    $response = $this->get($pageUrl->full_url)->assertNotFound();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//main//section[@id="capell-smart-404"]//li'))->toHaveCount(5)
        ->and($dom->query('//main/div/div[contains(concat(" ", @class, " "), " capell-error-recovery ") and contains(@class, "[&_ul]:ps-5!") and contains(@class, "[&_ul]:space-y-3!")]//section[@id="capell-smart-404"]'))->toHaveCount(1);
});

it('resolves the real registered form builder public component', function (): void {
    $livewire = resolve(LivewireManager::class);
    expect($livewire->exists('public-form'))->toBeTrue();

    $action = new ResolveThemeFormEmbedDataAction(new CapellOptionalExtensionAvailability($livewire));
    CapellCore::forcePackageInstalled('capell-app/form-builder');
    expect($action->handle('contact-enquiry')->available)->toBeTrue();
});

it('aligns the system error heading with the recovery suggestions', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'System Error Alignment', 'not-found');
    $page = $pageUrl->pageable;
    Assert::assertInstanceOf(Page::class, $page);
    $layout = $page->layout;
    Assert::assertInstanceOf(Layout::class, $layout);
    $layout->update(['containers' => null]);
    $response = $this->get($pageUrl->full_url)->assertNotFound();
    $html = $response->getContent();
    Assert::assertIsString($html);
    $dom = foundationDemoStateDom($html);

    expect($dom->query('//main//*[contains(concat(" ", @class, " "), " max-w-7xl ")]//h1'))->toHaveCount(1);
});

it('keeps the layout native error heading and recovery content in one page container', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Recovery Container', 'not-found');
    $response = $this->get($pageUrl->full_url)->assertNotFound();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//main//*[contains(concat(" ", @class, " "), " max-w-7xl ")][.//h1 and .//section[@id="capell-smart-404"]]'))->toHaveCount(1);
});

it('renders another page using the error blueprint inside the shared container', function (bool $layoutHook): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Other Error Blueprint', 'homepage');
    $page = $pageUrl->pageable;
    Assert::assertInstanceOf(Page::class, $page);
    $page->update(['blueprint_id' => Blueprint::query()->where('key', 'error')->sole()->getKey()]);
    if (! $layoutHook) {
        $page->layout?->update(['containers' => null]);
    }

    $response = $this->get($pageUrl->full_url)->assertNotFound();
    $html = $response->getContent();
    Assert::assertIsString($html);
    $dom = foundationDemoStateDom($html);
    expect($dom->query('//main/div[contains(concat(" ", @class, " "), " capell-error-page ")]'))->toHaveCount(1)
        ->and($dom->query('//main/div[contains(concat(" ", @class, " "), " capell-error-page ")]//h1'))->toHaveCount(1)
        ->and($dom->query('//main/div[contains(concat(" ", @class, " "), " capell-error-page ")]/div[contains(concat(" ", @class, " "), " capell-error-recovery ")]'))->toHaveCount(1)
        ->and($dom->query('//main/div[contains(concat(" ", @class, " "), " capell-error-page ")][.//h1 and .//section[@id="capell-smart-404"]]'))->toHaveCount(1)
        ->and($html)->not->toContain('data-capell-editor', 'signed-editor');
})->with([true, false]);

it('uses concise navigation labels in real discovered error suggestions', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Error Suggestion Labels', 'contact');
    $page = $pageUrl->pageable;
    Assert::assertInstanceOf(Page::class, $page);
    $translation = $page->translations->firstOrFail();
    $translation->update(['meta' => [...$translation->meta, 'label' => 'Contact']]);
    app()->instance('request', Request::create($pageUrl->full_url . '-missing'));

    $site = $page->site;
    Assert::assertInstanceOf(Site::class, $site);
    $language = $site->language;
    Assert::assertInstanceOf(Language::class, $language);
    $suggestions = ResolveSmart404SuggestionsAction::run(
        '/missing',
        $site,
        $language,
    );
    expect($suggestions->firstWhere('url', '/theme-default-contact')?->title)->toBe('Contact');
});

it('loads the seeded enquiry form for each Foundation form surface', function (string $surface): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Form State', $surface);
    $response = $this->get($pageUrl->full_url)->assertOk();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//*[@data-theme-form-fallback]'))->toHaveCount(0)
        ->and($dom->query('//*[@*[name()="wire:init"]="loadForm"]'))->toHaveCount(1);

    $component = livewire('public-form', ['handle' => 'foundation-enquiry'])
        ->call('loadForm');
    $formDom = foundationDemoStateDom($component->html());

    expect($formDom->query('//form'))->toHaveCount(1)
        ->and($formDom->query('//form//input[@type="email"]'))->toHaveCount(1)
        ->and($formDom->query('//form//textarea'))->toHaveCount(1)
        ->and($formDom->query('//form//button[@type="submit"]'))->toHaveCount(1)
        ->and($formDom->query('//*[@class="capell-form-element__fallback"]'))->toHaveCount(0);
})->with(['contact', 'detail', 'cta']);

it('waits for the article on every Foundation detail capture viewport', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Detail Capture', 'detail');
    $html = (string) $this->get($pageUrl->full_url)->assertOk()->getContent();
    $dom = foundationDemoStateDom($html);
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/docs/screenshots.json'), true, flags: JSON_THROW_ON_ERROR);
    Assert::assertIsArray($manifest);
    $entries = $manifest['entries'];
    Assert::assertIsArray($entries);

    foreach ($entries as $entry) {
        Assert::assertIsArray($entry);
        $id = $entry['id'];
        Assert::assertIsString($id);

        if (! str_starts_with($id, 'foundation-detail')) {
            continue;
        }

        expect($entry['waitFor'])->toBe('#main article.capell-page-content')
            ->and($dom->query('//main[@id="main"]//article[contains(concat(" ", @class, " "), " capell-page-content ")]'))->toHaveCount(1)
            ->and($html)->toContain('A practical brief for a shared workshop');
    }
});
