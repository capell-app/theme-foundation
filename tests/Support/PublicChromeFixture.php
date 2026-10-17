<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;
use RuntimeException;

require_once __DIR__ . '/NoOptionalExtensions.php';

/**
 * @param  array<string, mixed>  $themeMeta
 * @param  array<string, mixed>  $frontendData
 */
function preparePublicChrome(string $title = 'Public site', array $themeMeta = [], array $frontendData = [], bool $footerWidgets = false): Site
{
    $site = new Site(['name' => $title, 'meta' => []]);
    $site->setRelation('translation', new Translation(['title' => $title]));
    $site->setRelation('siteDomain', new SiteDomain(['domain' => 'example.test', 'path' => '/prepared-home']));
    $site->setRelation('blueprint', null);
    $site->setRelation('logo', null);
    $site->setRelation('logoInverted', null);
    $site->setRelation('media', new Collection);
    $layout = new Layout(['containers' => $footerWidgets ? ['footer' => ['widgets' => [['key' => 'footer-content']]]] : []]);
    // An absent optional package contributes neither navigation hooks nor installed-state capabilities.
    CapellCore::forcePackageInstalled('capell-app/navigation', false);
    app()->instance(RenderHookRegistry::class, new RenderHookRegistry);
    app()->instance(OptionalExtensionAvailability::class, new NoOptionalExtensions);
    $page = new Page;
    $page->setRelation('blueprint', null);
    $theme = new Theme(['key' => 'default', 'meta' => $themeMeta]);
    $theme->setRelation('blueprint', null);
    app()->instance(FrontendContextReader::class, new FrontendContext(
        $site,
        (new Language)->forceFill(['id' => 1, 'code' => 'en', 'name' => 'English', 'flag' => 'gb']),
        new Page,
        $layout,
        new Theme(['key' => 'default', 'meta' => $themeMeta]),
        $frontendData,
        null,
    ));
    Frontend::clearResolvedInstance(FrontendContextReader::class);

    return $site;
}

/** @param array<string, mixed> $data */
function renderPublicChromeView(string $name, array $data = []): string
{
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        return view()->file(dirname(__DIR__, 2) . '/resources/views/components/' . str_replace('.', '/', $name) . '.blade.php', [
            ...$data, 'attributes' => new ComponentAttributeBag,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
}

/** @return list<DOMElement> */
function chromeElements(string $html, string $expression): array
{
    $document = new DOMDocument;
    @$document->loadHTML($html === '' ? '<html></html>' : $html, LIBXML_NONET);
    $nodes = (new DOMXPath($document))->query($expression);
    if ($nodes === false) {
        throw new RuntimeException('Expected a valid output query.');
    }
    $elements = [];
    foreach ($nodes as $node) {
        if (! $node instanceof DOMElement) {
            throw new RuntimeException('Expected a rendered element.');
        }
        $elements[] = $node;
    }

    return $elements;
}
