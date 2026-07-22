<?php

declare(strict_types=1);

it('renders kitchen sink reference widgets with semantic accessibility hooks', function (): void {
    $view = file_get_contents(__DIR__ . '/../../resources/views/components/widget/kitchen-sink/reference.blade.php');

    expect($view)->toContain('role="tablist"')
        ->toContain('role="tab"')
        ->toContain('role="tabpanel"')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-roledescription="carousel"')
        ->toContain('<x-capell::form-embed')
        ->toContain("__('capell-theme-foundation::generic.demo_tab_set')")
        ->not->toContain('action="#"')
        ->not->toContain('signed')
        ->not->toContain('field_path')
        ->not->toContain('wire:snapshot');
});
