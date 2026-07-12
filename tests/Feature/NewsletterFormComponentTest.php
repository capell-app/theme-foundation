<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('renders a cache-safe post form when the newsletter route exists', function (): void {
    if (! Route::has('capell-newsletter.subscribe')) {
        Route::post('/newsletter/subscribe', static fn (): string => 'subscribed')
            ->name('capell-newsletter.subscribe');
    }

    $html = Blade::render(<<<'BLADE'
        <x-capell::newsletter-form class="test-newsletter-form">
            <input type="email" name="email" />
        </x-capell::newsletter-form>
        BLADE);

    expect($html)->toContain('method="post"')
        ->toContain('action="/newsletter/subscribe"')
        ->toContain('class="test-newsletter-form"')
        ->toContain('name="source"')
        ->toContain('value="public_newsletter"')
        ->not->toContain('name="_token"');
});
