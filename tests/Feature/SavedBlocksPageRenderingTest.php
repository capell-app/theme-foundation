<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

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
    expect($html)->not->toContain('__capell', 'saved-block-identity', 'state_version', 'content-blocks.', 'data-field=', 'data-page=');
    assertCapellPublicOutputIsSafe($response, 'Saved blocks page');
})->with([
    'empty layout anonymous' => [false, false],
    'empty layout non-admin' => [false, true],
    'page-content layout anonymous' => [true, false],
    'page-content layout non-admin' => [true, true],
]);
