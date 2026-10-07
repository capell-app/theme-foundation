<?php

declare(strict_types=1);

use Capell\Core\Models\PageUrl;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Contracts\Console\Kernel;
use Livewire\Livewire;
use PHPUnit\Framework\Assert;
use Workbench\App\Console\Commands\PrepareFoundationScreenshotEvidenceCommand;
use Workbench\App\Providers\ScreenshotWorkbenchServiceProvider;

it('loads the prepared Foundation enquiry through the real HTTP Livewire endpoint with fresh frontend state', function (string $surface): void {
    $previousEnvironment = [];
    foreach (['CAPELL_SCREENSHOT_WORKBENCH' => 'true', 'CAPELL_SCREENSHOT_FIXTURE' => 'record-state', 'CAPELL_SCREENSHOT_APP_PATH' => base_path()] as $name => $value) {
        $previousEnvironment[$name] = getenv($name);
        putenv($name . '=' . $value);
    }

    try {
        new ScreenshotWorkbenchServiceProvider(app())->register();
        resolve(Kernel::class)->registerCommand(new PrepareFoundationScreenshotEvidenceCommand);
        $this->artisan('capell:theme-foundation-demo', ['--base-url' => 'http://localhost', '--force' => true])->assertSuccessful();
        $this->artisan('capell:prepare-foundation-screenshot-evidence')->assertSuccessful();

        $pageUrl = PageUrl::query()->where('url', '/theme-default-' . $surface)->sole();
        $response = $this->get($pageUrl->full_url)->assertOk();
        $response->assertDontSee('data-theme-form-fallback', false)->assertDontSee('fixture');
        $document = new DOMDocument;
        $html = $response->getContent();
        Assert::assertIsString($html);
        if ($html === '') {
            throw new RuntimeException('Expected rendered Foundation page HTML.');
        }
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $snapshots = $xpath->query('//*[@*[name()="wire:snapshot"]]');
        Assert::assertNotFalse($snapshots);
        $snapshot = null;
        foreach ($snapshots as $element) {
            Assert::assertInstanceOf(DOMElement::class, $element);
            $candidate = $element->getAttribute('wire:snapshot');
            $decoded = json_decode($candidate, true, flags: JSON_THROW_ON_ERROR);
            if (is_array($decoded) && data_get($decoded, 'memo.name') === 'public-form') {
                $snapshot = $candidate;
            }
        }
        Assert::assertIsString($snapshot);
        app()->instance(FrontendContextReader::class, new FrontendState);
        Frontend::clearResolvedInstance(FrontendContextReader::class);

        $update = $this->postJson(Livewire::getUpdateUri(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => new stdClass,
                'calls' => [['path' => '', 'method' => 'loadForm', 'params' => []]],
            ]],
        ], ['X-Livewire' => 'true'])->assertOk();
        $html = $update->json('components.0.effects.html');
        Assert::assertIsString($html);
        if ($html === '') {
            throw new RuntimeException('Expected rendered Foundation enquiry HTML.');
        }
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//form[.//input[@name="name"] and .//input[@name="email"] and .//textarea[@name="message"]]'))->toHaveCount(1)
            ->and($xpath->query('//*[contains(@class, "capell-form-element__fallback")]'))->toHaveCount(0)
            ->and($html)->not->toContain('data-capell-editor', 'signed-editor', 'fixture');
    } finally {
        foreach ($previousEnvironment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
    }
})->with(['contact', 'detail', 'cta']);
