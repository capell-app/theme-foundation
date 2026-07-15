<?php

declare(strict_types=1);

it('resolves asset widget aliases through their data-hydrating class components', function (): void {
    $provider = file_get_contents(__DIR__ . '/../../src/Providers/FoundationThemeServiceProvider.php');

    expect($provider)
        ->toBeString()
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset');")
        ->toContain("Blade::component(AssetAccordionComponent::class, 'capell::widget.asset.accordion');")
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset.banners');")
        ->toContain("Blade::component(AssetCarouselComponent::class, 'capell::widget.asset.carousel');")
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset.features');")
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset.media');")
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset.testimonials');")
        ->toContain("Blade::component(AssetComponent::class, 'capell::widget.asset.widgets');");
});

it('derives asset totals after Blade has extracted sibling props', function (): void {
    $templates = [
        'features',
        'widgets',
        'testimonials',
        'index',
        'carousel',
    ];

    foreach ($templates as $template) {
        $contents = file_get_contents(__DIR__ . "/../../resources/views/components/widget/asset/{$template}.blade.php");

        expect($contents)
            ->toBeString()
            ->toContain("'total' => null")
            ->toContain('$total ??= $assets->count();')
            ->not->toContain("'total' => \$assets->count()");
    }
});
