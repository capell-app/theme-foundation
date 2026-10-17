<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Theme;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Support\HtmlString;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../Support/DomQueries.php';

it('renders translated carousel controls in the public shell', function (): void {
    $theme = new Theme(['key' => 'default']);
    resolve(FrontendState::class)->withTheme($theme);
    $page = Page::factory()->withTranslations()->create();
    resolve(FrontendState::class)->withPage($page)->withSite($page->site)->withLayout($page->layout);
    resolve(FrontendState::class)->withLanguage(Language::factory()->english()->create());
    $html = view('capell-theme-foundation::app', [
        'layout' => new Layout(['key' => 'default']),
        'theme' => $theme,
        'slot' => new HtmlString(''),
    ])->render();
    if ($html === '') {
        throw new RuntimeException('Expected a rendered public shell.');
    }
    foreach (['go-to-slide' => 'carousel_go_to_slide', 'pause' => 'pause_carousel', 'play' => 'play_carousel'] as $attribute => $key) {
        $document = new DOMDocument;
        @$document->loadHTML($html, LIBXML_NONET);
        expect($document->documentElement?->getAttribute('data-carousel-label-' . $attribute))
            ->toBe(__('capell-theme-foundation::generic.' . $key));
    }
});

it('honours translated control labels and reduced motion in source and published carousels', function (): void {
    $process = new Process(['node', '--experimental-vm-modules', __DIR__ . '/../Support/carousel-outcomes.mjs']);
    $process->setTimeout(30);
    $process->run();
    expect($process->getExitCode())->toBe(0, $process->getOutput() . $process->getErrorOutput());
});
