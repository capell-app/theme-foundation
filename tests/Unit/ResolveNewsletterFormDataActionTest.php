<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveNewsletterFormDataAction;
use Capell\FoundationTheme\Data\NewsletterFormData;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

it('uses a safe inert fallback when the newsletter route is unavailable', function (): void {
    $router = Mockery::mock(Router::class);
    $urlGenerator = Mockery::mock(UrlGenerator::class);

    $router->shouldReceive('has')
        ->once()
        ->with('capell-newsletter.subscribe')
        ->andReturnFalse();
    $urlGenerator->shouldNotReceive('route');

    $data = (new ResolveNewsletterFormDataAction($router, $urlGenerator))->handle(
        fallbackAction: 'javascript:alert(1)',
        source: 'invalid source',
    );

    expect($data->action)->toBe('#newsletter')
        ->and($data->method)->toBe('get')
        ->and($data->source)->toBe('public_newsletter')
        ->and($data->wired)->toBeFalse();
});

it('renders the unwired fallback without a submitting form', function (): void {
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
        ->toContain('newsletter-shell')
        ->toContain('<fieldset')
        ->toContain('disabled')
        ->not->toContain('<form')
        ->not->toContain('method="get"');
});

it('posts to the package-neutral subscribe route when it is available', function (): void {
    $router = Mockery::mock(Router::class);
    $urlGenerator = Mockery::mock(UrlGenerator::class);

    $router->shouldReceive('has')
        ->once()
        ->with('capell-newsletter.subscribe')
        ->andReturnTrue();
    $urlGenerator->shouldReceive('route')
        ->once()
        ->with('capell-newsletter.subscribe', [], false)
        ->andReturn('/newsletter/subscribe');

    $data = (new ResolveNewsletterFormDataAction($router, $urlGenerator))->handle(
        source: 'public_newsletter',
    );

    expect($data->action)->toBe('/newsletter/subscribe')
        ->and($data->method)->toBe('post')
        ->and($data->source)->toBe('public_newsletter')
        ->and($data->wired)->toBeTrue();
});
