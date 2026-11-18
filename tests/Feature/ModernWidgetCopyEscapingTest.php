<?php

declare(strict_types=1);

use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/../Support/WidgetViewFixture.php';

it('renders modern widget copy as escaped text, decoding entities once', function (string $view): void {
    $widget = foundationWidgetViewFixture();
    $widget->setRelation('translation', new Translation([
        'title' => 'Plain title',
        'content' => '<p>Fish &amp; chips &lt;script&gt;alert(1)&lt;/script&gt;</p><script>alert(2)</script>',
    ]));

    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        /** @var view-string $viewName */
        $viewName = implode('', ['capell-theme-foundation::components.widget.modern.', $view]);
        $html = view($viewName, [
            'widget' => $widget,
            'assets' => new Collection,
            'assetRenderDataItems' => [],
            'container' => [],
            'containerKey' => 'main',
            'containerWidth' => null,
            'loop' => (object) ['index' => 0, 'first' => true, 'last' => true],
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)->toContain('Fish &amp; chips &lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>alert(1)', '&amp;amp;', '&amp;lt;');
})->with(['hero-banner', 'card-grid', 'feature-list', 'image-gallery', 'cta-section']);
