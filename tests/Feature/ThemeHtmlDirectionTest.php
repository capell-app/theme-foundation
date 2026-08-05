<?php

declare(strict_types=1);

use Capell\Core\Models\Language;

test('the theme shell derives text direction from the language model', function (): void {
    $shell = file_get_contents(dirname(__DIR__, 2) . '/resources/views/app.blade.php');

    if (! is_string($shell)) {
        throw new RuntimeException('Unable to read the Foundation theme application shell.');
    }

    expect($shell)->toContain('$language?->direction()')
        ->toContain('Language::directionForCode((string) $languageCode)')
        ->toContain('dir="{{ $textDirection }}"')
        ->not->toContain("'ckb'");
});

test('the shell direction expression resolves a right-to-left language', function (): void {
    $language = new Language(['code' => 'ar', 'meta' => null]);

    expect($language->direction())->toBe('rtl');
});

test('the shell direction expression resolves a left-to-right language', function (): void {
    $language = new Language(['code' => 'fr', 'meta' => null]);

    expect($language->direction())->toBe('ltr');
});

test('the shell direction expression falls back when no language is resolved', function (): void {
    // Mirrors the shell expression for an unresolved language: the null-safe
    // call yields null, so the fallback decides the direction.
    expect(Language::directionForCode(app()->getLocale()))->toBe('ltr');
});
