<?php

declare(strict_types=1);

it('ships translated labels for every shared carousel control', function (): void {
    $themePath = dirname(__DIR__, 2);
    $translations = (string) file_get_contents($themePath . '/resources/lang/en/generic.php');
    $appLayout = (string) file_get_contents($themePath . '/resources/views/app.blade.php');
    $carouselRuntime = (string) file_get_contents($themePath . '/resources/js/widgets/widget/carousel.js');

    expect($translations)->toContain("'carousel_go_to_slide' => 'Go to slide :number'")
        ->and($translations)->toContain("'pause_carousel' => 'Pause carousel'")
        ->and($translations)->toContain("'play_carousel' => 'Play carousel'")
        ->and($appLayout)->toContain('data-carousel-label-go-to-slide')
        ->and($appLayout)->toContain('data-carousel-label-pause')
        ->and($appLayout)->toContain('data-carousel-label-play')
        ->and($carouselRuntime)->toContain('A11y, Keyboard')
        ->and($carouselRuntime)->toContain('keyboard: {')
        ->and($carouselRuntime)->toContain('data-carousel-autoplay-toggle')
        ->and($carouselRuntime)->not->toContain('aria-label="Go to slide ${index + 1}"');
});

it('does not start shared carousel autoplay for reduced-motion visitors', function (): void {
    $carouselRuntime = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/js/widgets/widget/carousel.js');

    expect($carouselRuntime)->toContain("window.matchMedia?.('(prefers-reduced-motion: reduce)')")
        ->and($carouselRuntime)->toContain('autoplayEnabled: autoplayRequested && !prefersReducedMotion()');
});

it('publishes the current carousel runtime through the Foundation asset manifest', function (): void {
    $themePath = dirname(__DIR__, 2);
    $manifest = json_decode(
        (string) file_get_contents($themePath . '/publishes/build/manifest.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($manifest)) {
        throw new RuntimeException('Foundation asset manifest must decode to an array.');
    }

    $carouselEntry = $manifest['resources/js/widgets/widget/carousel.js'] ?? null;

    if (! is_array($carouselEntry) || ! is_string($carouselEntry['file'] ?? null)) {
        throw new RuntimeException('Foundation carousel asset manifest entry is invalid.');
    }

    $carouselBuild = $carouselEntry['file'];
    $publishedRuntime = (string) file_get_contents($themePath . '/publishes/build/' . $carouselBuild);

    expect($publishedRuntime)->toContain('data-carousel-autoplay-toggle')
        ->and($publishedRuntime)->toContain('prefers-reduced-motion: reduce');
});
