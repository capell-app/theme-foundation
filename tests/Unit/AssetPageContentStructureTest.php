<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Facades\DB;
use Livewire\Blaze\Blaze;

it('renders asset widget page content using the page override before its loaded blueprint', function (string $view, ?string $override, ContentStructure $blueprintStructure, ContentStructure $expected, bool $hasWidgetTranslation): void {
    $page = Page::factory()->make(['content_structure_override' => $override]);
    $blueprint = Blueprint::factory()->make(['meta' => ['content_structure' => $blueprintStructure->value]]);
    $page->setRelation('blueprint', $blueprint);
    $content = $expected === ContentStructure::Blocks
        ? json_encode([['type' => 'title', 'data' => ['title' => 'Override content rendered']]], JSON_THROW_ON_ERROR)
        : '<p>Override content rendered</p>';
    $translation = new Translation;
    $translation->setRawAttributes(['translatable_id' => 1, 'title' => 'Override page title', 'content' => $content]);
    $translation->setRelation('translatable', $page);

    $page->setRelation('translation', $translation);

    $widget = new Widget(['key' => 'asset-content', 'meta' => []]);
    $widgetTranslation = new Translation;
    $widgetTranslation->setRawAttributes(['title' => '', 'content' => null]);
    $widgetTranslation->setRelation('translatable', $widget);

    $widget->setRelation('translation', $hasWidgetTranslation ? $widgetTranslation : null);
    $widget->setRelation('blueprint', Blueprint::factory()->make(['meta' => ['content_structure' => 'html']]));
    $widget->setRelation('media', collect());

    $theme = Theme::factory()->make(['meta' => ['secondary_containers' => []]]);
    $theme->setRelation('blueprint', null);

    $context = new FrontendContext(
        site: Site::factory()->make(),
        language: Language::factory()->make(),
        page: $page,
        layout: null,
        theme: $theme,
        params: [],
        slug: null,
    );
    app()->instance(FrontendContextReader::class, $context);
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    config()->set('capell-layout-builder.widget.skip_render_empty', false);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    DB::enableQueryLog();

    try {
        $viewName = match ($view) {
            'carousel' => 'capell-theme-foundation::components.widget.asset.carousel',
            'pages' => 'capell-theme-foundation::components.widget.asset.pages',
            default => throw new LogicException('Unknown asset widget view.'),
        };
        $html = view($viewName, [
            'widget' => $widget,
            'widgetData' => ['meta' => ['show_page_content' => true]],
            'page' => $page,
            'showPageContent' => true,
            'showPageTitle' => ! $hasWidgetTranslation,
            'container' => [],
            'containerKey' => 'main',
            'containerWidth' => null,
            'index' => 0,
            'loop' => (object) ['index' => 0],
            'assets' => collect(),
            'assetRenderDataItems' => collect(),
        ])->render();
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)->toContain('Override content rendered')
        ->not->toContain('&lt;p&gt;', '__capell', 'content_structure_override')
        ->and($queries)->toBe([]);
    if (! $hasWidgetTranslation) {
        expect($html)->toContain('Override page title');
    }
})->with(['carousel', 'pages'])->with([
    'blocks overrides HTML blueprint' => ['blocks', ContentStructure::Html, ContentStructure::Blocks],
    'HTML overrides blocks blueprint' => ['html', ContentStructure::Blocks, ContentStructure::Html],
    'blueprint used without override' => [null, ContentStructure::Blocks, ContentStructure::Blocks],
    'invalid override falls back' => ['invalid', ContentStructure::Html, ContentStructure::Html],
])->with([
    'empty widget translation' => [true],
    'missing widget translation' => [false],
]);
