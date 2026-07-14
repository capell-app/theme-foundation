<?php

declare(strict_types=1);

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
