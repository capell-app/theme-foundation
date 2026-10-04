<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Actions\ResolveThemeFormEmbedDataAction;
use Capell\FoundationTheme\Support\CapellOptionalExtensionAvailability;
use Capell\Tests\Fixtures\Models\User;
use Livewire\LivewireManager;

use function Pest\Livewire\livewire;

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

it('renders populated search counts matching the listed results and padded note captions', function (string $surface): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Populated State', $surface);
    $response = $this->get($pageUrl->full_url)->assertOk();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//section[contains(@class, "theme-search")]//li'))->toHaveCount(2)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "2 results")]'))->toHaveCount(1)
        ->and($dom->query('//section[contains(@class, "theme-search")]//p[contains(., "No matching results")]'))->toHaveCount(0)
        ->and($dom->query('//section[contains(@class, "theme-content-listing")]//a/img/following-sibling::span[contains(concat(" ", @class, " "), " block ") and contains(concat(" ", @class, " "), " p-5 ")]'))->toHaveCount(3)
        ->and($dom->query('//section[contains(@class, "theme-content-listing")]//a/img/following-sibling::span/span[contains(concat(" ", @class, " "), " mt-2 ") and contains(concat(" ", @class, " "), " block ")]'))->toHaveCount(6);
})->with(['homepage', 'directory']);

it('renders inset spaced recovery suggestions on the not found demo', function (): void {
    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Not Found State', 'not-found');
    $response = $this->get($pageUrl->full_url)->assertNotFound();
    $dom = foundationDemoStateDom((string) $response->getContent());

    expect($dom->query('//main//section[@id="capell-smart-404"]//li'))->toHaveCount(5)
        ->and($dom->query('//main/div[contains(concat(" ", @class, " "), " capell-error-recovery ") and contains(@class, "[&_ul]:ps-5!") and contains(@class, "[&_ul]:space-y-3!")]//section[@id="capell-smart-404"]'))->toHaveCount(1);
});

it('resolves the real registered form builder public component', function (): void {
    $livewire = resolve(LivewireManager::class);
    expect($livewire->exists('public-form'))->toBeTrue();

    $action = new ResolveThemeFormEmbedDataAction(new CapellOptionalExtensionAvailability($livewire));
    CapellCore::forcePackageInstalled('capell-app/form-builder');
    expect($action->handle('contact-enquiry')->available)->toBeTrue();
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
