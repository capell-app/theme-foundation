<?php

declare(strict_types=1);

it('requires the vite plugin version that supports plugin fonts', function (): void {
    $dependencies = require __DIR__ . '/../../config/capell-theme-foundation.php';

    expect($dependencies['npm_dependencies'])
        ->toHaveKey('@tailwindcss/vite', '^4.0.13')
        ->toHaveKey('fontaine', '^0.8.0')
        ->toHaveKey('laravel-vite-plugin', '^3.1.3')
        ->toHaveKey('npm-run-all', '^4.1.5')
        ->toHaveKey('swiper', '^12.1.2')
        ->toHaveKey('tailwindcss', '^4.0.14')
        ->toHaveKey('vite', '^8.0')
        ->not->toHaveKey('@tailwindcss/form-builder');
});
