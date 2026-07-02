<?php

declare(strict_types=1);

it('renders kitchen sink reference widgets with semantic accessibility hooks', function (): void {
    $view = file_get_contents(__DIR__ . '/../../resources/views/components/widget/kitchen-sink/reference.blade.php');

    expect($view)->toContain('role="tablist"')
        ->toContain('role="tab"')
        ->toContain('role="tabpanel"')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-roledescription="carousel"')
        ->not->toContain('signed')
        ->not->toContain('field_path')
        ->not->toContain('wire:snapshot');
});
