<?php

declare(strict_types=1);

use Capell\FoundationTheme\Data\NewsletterFormData;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

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

it('renders visibly unavailable controls without a submitting fallback form', function (): void {
    $html = view('capell-theme-foundation::forms.newsletter', [
        'form' => new NewsletterFormData(
            action: '#newsletter',
            method: 'get',
            source: 'public_newsletter',
            wired: false,
        ),
        'attributes' => new ComponentAttributeBag(['class' => 'newsletter-shell']),
        'slot' => new HtmlString('<input type="email" name="email"><button type="submit">Join</button>'),
    ])->render();

    expect($html)
        ->toContain('data-newsletter-unavailable')
        ->toContain('role="note"')
        ->toContain('<fieldset')
        ->toContain('disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('Newsletter signup is temporarily unavailable.')
        ->not->toContain('<form')
        ->not->toContain('method="get"');
});
