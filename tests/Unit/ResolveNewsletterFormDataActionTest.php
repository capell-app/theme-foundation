<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveNewsletterFormDataAction;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;

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
