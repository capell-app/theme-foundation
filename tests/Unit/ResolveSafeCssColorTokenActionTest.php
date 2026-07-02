<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveSafeCssColorTokenAction;

it('normalizes safe css color tokens', function (): void {
    expect(ResolveSafeCssColorTokenAction::run('#1f2937', '#000000'))->toBe('rgb(31, 41, 55)')
        ->and(ResolveSafeCssColorTokenAction::run('32,31,40', '#000000'))->toBe('rgb(32, 31, 40)')
        ->and(ResolveSafeCssColorTokenAction::run('transparent', '#000000'))->toBe('transparent')
        ->and(ResolveSafeCssColorTokenAction::run('oklch(62% 0.1 240)', '#000000'))->toBe('oklch(62% 0.1 240)');
});

it('falls back when css token values contain unsafe delimiters', function (): void {
    expect(ResolveSafeCssColorTokenAction::run('red; background: url(https://example.test/x)', '#1f2937'))->toBe('rgb(31, 41, 55)')
        ->and(ResolveSafeCssColorTokenAction::run('rgb(0 0 0)<script>', 'transparent'))->toBe('transparent')
        ->and(ResolveSafeCssColorTokenAction::run(null, '#ffffff'))->toBe('rgb(255, 255, 255)');
});
