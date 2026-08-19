<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveFoundationThemeTokensAction;
use Capell\FoundationTheme\Support\Editor\StandardThemeEditorSchema;

function motionControlsStylesheet(string $theme): string
{
    $repoRoot = dirname(__DIR__, 4);
    $themeFile = basename($theme);
    $path = $repoRoot . "/packages/{$theme}/resources/css/{$themeFile}.css";

    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException("Expected stylesheet at [{$path}]");
    }

    return $contents;
}

it('adds a theme-level motion-off override for Broadsheet ticker motion', function (): void {
    $stylesheet = motionControlsStylesheet('theme-broadsheet');

    expect($stylesheet)->toMatch(
        '/@container\s+bds-tokens\s+style\(--theme-motion-intensity:\s*none\)\s*\{[^}]*\.bds-ticker--running\s*\.bds-ticker-track[^}]*animation:\s*none;[^}]*animation-iteration-count:\s*1;[^}]*animation-duration:\s*0\.001ms;/s',
    );
});

it('keeps Broadsheet default animated motion contract unchanged', function (): void {
    $stylesheet = motionControlsStylesheet('theme-broadsheet');

    expect($stylesheet)->toMatch(
        '/\.bds-ticker--running\s*\.bds-ticker-track\s*\{[^}]*animation:\s*bds-ticker-loop var\(--bds-ticker-duration,\s*24s\) linear infinite;/s',
    );
});

it('keeps foundation motion defaults intact when no setting is supplied', function (): void {
    $tokens = ResolveFoundationThemeTokensAction::run(resolveSettings: false);

    expect($tokens->motionIntensity)->toBe('subtle')
        ->and($tokens->motionDuration)->toBe('500ms')
        ->and($tokens->motionEase)->toBe('cubic-bezier(0.22, 1, 0.36, 1)')
        ->and($tokens->motionStagger)->toBe('60ms')
        ->and($tokens->motionDistance)->toBe('20px');
});

it('adds optional motion and media controls without changing the base shared schema', function (): void {
    $base = StandardThemeEditorSchema::definition();
    $extended = StandardThemeEditorSchema::withMotionAndMediaControls();

    expect($extended['groups'])
        ->toHaveKey('motion')
        ->toHaveKey('media')
        ->and($extended['groups']['motion'])->toBe(['motionIntensity'])
        ->and($extended['groups']['media'])->toContain('mediaTreatment');

    foreach ($base['groups'] as $groupName => $groupTokens) {
        foreach ($groupTokens as $tokenKey) {
            expect($extended['groups'][$groupName])->toContain($tokenKey);
        }
    }

    foreach (array_keys($base['tokens']) as $tokenKey) {
        expect($extended['tokens'])->toHaveKey($tokenKey);
        expect($extended['tokens'][$tokenKey])->toEqual($base['tokens'][$tokenKey]);
    }

    expect($extended['tokens'])
        ->toHaveKey('motionIntensity')
        ->toHaveKey('mediaTreatment');
});
