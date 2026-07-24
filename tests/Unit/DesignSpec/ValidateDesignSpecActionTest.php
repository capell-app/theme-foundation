<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\DesignSpec\ValidateDesignSpecAction;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecLocaleTypographyData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;

function designSpecFixture(string $name): string
{
    $contents = file_get_contents(dirname(__DIR__, 2) . "/Fixtures/design-spec/{$name}.json");

    return $contents === false ? throw new RuntimeException('Unable to load DesignSpec fixture.') : $contents;
}

/**
 * @return array{schemaVersion: int, template: string, display: array<string, mixed>, sites: list<array<string, mixed>>, locales: list<array<string, mixed>>, brand: array<string, mixed>, palette: array{light: array<string, mixed>, dark: array<string, mixed>}, typography: array{locales: list<array{locale: mixed, heading: array<string, mixed>, body: array<string, mixed>, ...}>}, layout: array<string, mixed>, components: array<string, mixed>, accessibility: array<string, mixed>, assets: list<array<string, mixed>>, ...}
 */
function designSpecFixturePayload(string $name): array
{
    $payload = foundationThemeJsonObjectDocument(designSpecFixture($name));
    $schemaVersion = $payload['schemaVersion'] ?? null;
    $template = $payload['template'] ?? null;
    if (! is_int($schemaVersion) || ! is_string($template)) {
        throw new RuntimeException('DesignSpec fixture root is invalid.');
    }

    $typography = foundationThemeJsonObject($payload['typography'] ?? null);
    $typographyLocales = [];
    foreach (foundationThemeJsonList($typography['locales'] ?? null) as $locale) {
        $localeObject = foundationThemeJsonObject($locale);
        $typographyLocales[] = [
            'locale' => $localeObject['locale'] ?? null,
            'heading' => foundationThemeJsonObject($localeObject['heading'] ?? null),
            'body' => foundationThemeJsonObject($localeObject['body'] ?? null),
        ];
    }

    $sites = [];
    foreach (foundationThemeJsonList($payload['sites'] ?? null) as $site) {
        $sites[] = foundationThemeJsonObject($site);
    }

    $locales = [];
    foreach (foundationThemeJsonList($payload['locales'] ?? null) as $locale) {
        $locales[] = foundationThemeJsonObject($locale);
    }

    $assets = [];
    foreach (foundationThemeJsonList($payload['assets'] ?? null) as $asset) {
        $assets[] = foundationThemeJsonObject($asset);
    }

    return [
        'schemaVersion' => $schemaVersion,
        'template' => $template,
        'display' => foundationThemeJsonObject($payload['display'] ?? null),
        'sites' => $sites,
        'locales' => $locales,
        'brand' => foundationThemeJsonObject($payload['brand'] ?? null),
        'palette' => [
            'light' => foundationThemeJsonObject(foundationThemeJsonObject($payload['palette'] ?? null)['light'] ?? null),
            'dark' => foundationThemeJsonObject(foundationThemeJsonObject($payload['palette'] ?? null)['dark'] ?? null),
        ],
        'typography' => ['locales' => $typographyLocales],
        'layout' => foundationThemeJsonObject($payload['layout'] ?? null),
        'components' => foundationThemeJsonObject($payload['components'] ?? null),
        'accessibility' => foundationThemeJsonObject($payload['accessibility'] ?? null),
        'assets' => $assets,
    ];
}

/**
 * @return array{schemaVersion: int, template: string, display: array<string, mixed>, sites: list<array<string, mixed>>, locales: list<array<string, mixed>>, brand: array<string, mixed>, palette: array{light: array<string, mixed>, dark: array<string, mixed>}, typography: array{locales: list<array{locale: mixed, heading: array<string, mixed>, body: array<string, mixed>, ...}>}, layout: array<string, mixed>, components: array<string, mixed>, accessibility: array<string, mixed>, assets: list<array<string, mixed>>, ...}
 */
function canonicalDesignSpec(): array
{
    return designSpecFixturePayload('v1-canonical');
}

/**
 * @param  array{schemaVersion: int, template: string, display: array<string, mixed>, sites: list<array<string, mixed>>, locales: list<array<string, mixed>>, brand: array<string, mixed>, palette: array{light: array<string, mixed>, dark: array<string, mixed>}, typography: array{locales: list<array{locale: mixed, heading: array<string, mixed>, body: array<string, mixed>, ...}>}, layout: array<string, mixed>, components: array<string, mixed>, accessibility: array<string, mixed>, assets: list<array<string, mixed>>, ...}  $payload
 */
function applyDesignSpecThreatCase(array &$payload, string $case): void
{
    match ($case) {
        'absolute-path' => $payload['assets'][0]['id'] = '/tmp/logo.svg',
        'nul-path' => $payload['assets'][0]['id'] = "logo\0foundation",
        'backslash-path' => $payload['assets'][0]['id'] = 'assets\\logo.svg',
        'symlink-path' => $payload['assets'][0]['symlink'] = '/etc/passwd',
        'duplicate-normalized-paths' => $payload['assets'] = [['id' => 'logo-foundation'], ['id' => 'assets/../logo-foundation']],
        'http-url' => $payload['brand']['logoAssetId'] = 'http://attacker.test/logo.svg',
        'data-url' => $payload['brand']['logoAssetId'] = 'data:image/svg+xml,unsafe',
        'javascript-url' => $payload['brand']['logoAssetId'] = 'javascript:alert(1)',
        'inline-svg' => $payload['brand']['logoAssetId'] = '<svg onload=alert(1)>',
        'remote-css' => $payload['assets'][0]['id'] = 'https://attacker.test/theme.css',
        'executable' => $payload['assets'][0]['id'] = 'payload.php',
        'composer' => $payload['composer'] = ['require' => ['attacker/package']],
        'npm' => $payload['npm'] = ['attacker-package'],
        'plugins' => $payload['plugins'] = ['attacker/plugin'],
        'hooks' => $payload['hooks'] = ['post-install'],
        'workflows' => $payload['workflows'] = ['deploy.yml'],
        'binaries' => $payload['binaries'] = ['payload'],
        'unsupported-locale' => $payload['locales'][0]['code'] = 'xx-ZZ',
        'excessive-items' => $payload['sites'] = array_fill(0, DesignSpecConstraints::MAX_SITES + 1, $payload['sites'][0]),
        'excessive-string' => $payload['display']['description'] = str_repeat('x', DesignSpecConstraints::MAX_DOCUMENT_BYTES),
        'excessive-file-bytes' => $payload['assets'][0]['bytes'] = DesignSpecConstraints::MAX_ASSET_BYTES + 1,
        'excessive-total-bytes' => $payload['assetsTotalBytes'] = DesignSpecConstraints::MAX_TOTAL_ASSET_BYTES + 1,
        'large-text-contrast' => $payload['palette']['light']['largeText'] = '#A0A0A0',
        'cyclic-aliases' => $payload['aliases'] = ['a' => 'b', 'b' => 'a'],
        'prompt-metadata' => $payload['prompt'] = 'private prompt',
        'project-metadata' => $payload['project'] = ['id' => 123],
        'version-metadata' => $payload['version'] = ['id' => 456],
        'billing-metadata' => $payload['billing'] = ['credits' => 100],
        'preview-metadata' => $payload['preview'] = ['url' => 'https://private.test'],
        'editor-metadata' => $payload['editor'] = ['selector' => '#private'],
        default => throw new LogicException('Unknown DesignSpec threat case.'),
    };
}

it('hydrates the complete canonical contract without defaults or normalization', function (): void {
    $specification = ValidateDesignSpecAction::run(designSpecFixture('v1-canonical'));

    expect($specification)->toBeInstanceOf(DesignSpecData::class)
        ->and($specification->schemaVersion)->toBe(1)
        ->and($specification->template)->toBe('foundation')
        ->and($specification->display->name)->toBe('Capell Studio')
        ->and($specification->sites[0]->fallbackLocaleCodes)->toBe(['en-GB'])
        ->and($specification->typography->locales[0]->locale)->toBe('en-GB')
        ->and($specification->typography->locales[0]->heading->style)->toBe('normal')
        ->and($specification->brand->logoAssetId)->toBe('logo-foundation')
        ->and($specification->palette->light->largeText)->toBe('#4B5563')
        ->and($specification->accessibility->reducedMotion)->toBe('respect')
        ->and($specification->assets[0]->id)->toBe('logo-foundation');
});

it('preserves site-scoped fallback priority and locale-aware typography', function (): void {
    $specification = ValidateDesignSpecAction::run(designSpecFixture('v1-two-sites-two-locales'));

    expect($specification->sites)->toHaveCount(2)
        ->and($specification->sites[0]->fallbackLocaleCodes)->toBe(['en-GB', 'fr-FR'])
        ->and($specification->sites[1]->fallbackLocaleCodes)->toBe(['fr-FR', 'en-GB'])
        ->and(array_map(static fn (DesignSpecLocaleTypographyData $entry): string => $entry->locale, $specification->typography->locales))
        ->toBe(['en-GB', 'fr-FR']);
});

it('rejects legacy, non-foundation, missing display, and launch-bearing roots', function (string $case): void {
    $payload = canonicalDesignSpec();
    if ($case === 'version') {
        $payload['schemaVersion'] = 0;
    } elseif ($case === 'template') {
        $payload['template'] = 'bespoke';
    } elseif ($case === 'display') {
        unset($payload['display']);
    } else {
        $payload['launch'] = true;
    }

    ValidateDesignSpecAction::run($payload);
})->with(['version', 'template', 'display', 'launch'])->throws(InvalidArgumentException::class);

it('rejects incomplete or invalid accessibility policy', function (string $field, mixed $value): void {
    $payload = canonicalDesignSpec();
    $payload['accessibility'][$field] = $value;

    ValidateDesignSpecAction::run($payload);
})->with([
    ['reducedMotion', 'ignore'],
    ['focusIndicator', 'none'],
    ['landmarks', 'optional'],
    ['headingHierarchy', 'freeform'],
])->throws(InvalidArgumentException::class, 'value.unsupported: $.accessibility');

it('enforces text, UI, focus, and border contrast thresholds', function (string $field, string $value): void {
    $payload = canonicalDesignSpec();
    $payload['palette']['light'][$field] = $value;

    ValidateDesignSpecAction::run($payload);
})->with([
    'text contrast' => ['text', '#FFFFFF'],
    'muted text contrast' => ['mutedText', '#B0B0B0'],
    'muted text surface contrast' => ['mutedText', '#737373'],
    'primary UI contrast' => ['primary', '#EEEEEE'],
    'focus contrast' => ['focus', '#EEEEEE'],
    'border contrast' => ['border', '#EEEEEE'],
])->throws(InvalidArgumentException::class, 'contrast.');

it('requires every site fallback chain to be complete, unique, and default-first', function (string $case): void {
    $payload = designSpecFixturePayload('v1-two-sites-two-locales');
    $payload['sites'][0]['fallbackLocaleCodes'] = match ($case) {
        'incomplete' => ['en-GB'],
        'duplicate' => ['en-GB', 'en-GB'],
        'wrong-first' => ['fr-FR', 'en-GB'],
        default => throw new LogicException('Unknown DesignSpec fallback case.'),
    };

    ValidateDesignSpecAction::run($payload);
})->with(['incomplete', 'duplicate', 'wrong-first'])->throws(InvalidArgumentException::class);

it('requires exactly one typography contract for every declared locale', function (string $case): void {
    $payload = designSpecFixturePayload('v1-two-sites-two-locales');
    match ($case) {
        'missing' => array_pop($payload['typography']['locales']),
        'duplicate' => $payload['typography']['locales'][1]['locale'] = 'en-GB',
        'undeclared' => $payload['typography']['locales'][1]['locale'] = 'de-DE',
        default => throw new LogicException('Unknown DesignSpec typography case.'),
    };

    ValidateDesignSpecAction::run($payload);
})->with(['missing', 'duplicate', 'undeclared'])->throws(InvalidArgumentException::class);

it('accepts only declared server catalogue IDs and rejects normalized identifier collisions', function (string $id): void {
    $payload = canonicalDesignSpec();
    $payload['assets'][0]['id'] = $id;

    ValidateDesignSpecAction::run($payload);
})->with([
    'path' => ['assets/logo.svg'],
    'dot segment' => ['logo..foundation'],
    'case normalization' => ['Logo-Foundation'],
    'separator normalization' => ['logo--foundation'],
    'unknown canonical ID' => ['logo-other'],
])->throws(InvalidArgumentException::class);

it('rejects duplicate assets and wrong-kind logical references', function (string $case): void {
    $payload = canonicalDesignSpec();
    if ($case === 'duplicate') {
        $payload['assets'][] = ['id' => 'logo-foundation'];
    } else {
        $payload['brand']['logoAssetId'] = 'font-serif-latin';
    }

    ValidateDesignSpecAction::run($payload);
})->with(['duplicate', 'wrong-kind'])->throws(InvalidArgumentException::class);

it('rejects an oversized catalogue asset even when the aggregate byte limit would pass', function (): void {
    $action = new ValidateDesignSpecAction;
    $catalogue = DesignSpecConstraints::ASSET_CATALOGUE;
    $catalogue['logo-foundation']['bytes'] = DesignSpecConstraints::MAX_ASSET_BYTES + 1;

    expect($catalogue['logo-foundation']['bytes'])->toBeLessThanOrEqual(DesignSpecConstraints::MAX_TOTAL_ASSET_BYTES);

    $catalogueProperty = new ReflectionProperty($action, 'assetCatalogue');
    $catalogueProperty->setValue($action, $catalogue);

    $action->handle(canonicalDesignSpec());
})->throws(InvalidArgumentException::class, 'asset.too_large: $.assets[0].id');

it('rejects executable, dependency, asset path or byte claims, unknown components, and excessive files', function (string $case): void {
    $payload = canonicalDesignSpec();
    match ($case) {
        'script' => $payload['scripts'] = ['build'],
        'dependency' => $payload['brand']['dependencies'] = ['package'],
        'asset-path' => $payload['assets'][0]['path'] = 'assets/logo.svg',
        'asset-bytes' => $payload['assets'][0]['bytes'] = PHP_INT_MAX,
        'component' => $payload['components']['carousel'] = 'animated',
        'files' => $payload['assets'] = array_fill(0, DesignSpecConstraints::MAX_ASSET_FILES + 1, ['id' => 'logo-foundation']),
        default => throw new LogicException('Unknown DesignSpec executable case.'),
    };

    ValidateDesignSpecAction::run($payload);
})->with(['script', 'dependency', 'asset-path', 'asset-bytes', 'component', 'files'])->throws(InvalidArgumentException::class);

it('rejects unsupported font choices and non-font catalogue references', function (string $case): void {
    $payload = canonicalDesignSpec();
    if ($case === 'family') {
        $payload['typography']['locales'][0]['heading']['family'] = 'remote-font';
    } else {
        $payload['typography']['locales'][0]['heading']['fontAssetId'] = 'logo-foundation';
    }

    ValidateDesignSpecAction::run($payload);
})->with(['family', 'wrong-kind'])->throws(InvalidArgumentException::class);

it('requires an allowlisted typography style', function (string $case): void {
    $payload = canonicalDesignSpec();
    if ($case === 'missing') {
        unset($payload['typography']['locales'][0]['heading']['style']);
    } else {
        $payload['typography']['locales'][0]['heading']['style'] = 'oblique(unsafe)';
    }

    ValidateDesignSpecAction::run($payload);
})->with(['missing', 'unsupported'])->throws(InvalidArgumentException::class);

it('rejects exact traversal, arbitrary font URL, and known component variant threats', function (string $case): void {
    $payload = canonicalDesignSpec();
    match ($case) {
        'dot-dot-traversal' => $payload['assets'][0]['id'] = '../logo-foundation',
        'arbitrary-font-url' => $payload['typography']['locales'][0]['heading']['fontAssetId'] = 'https://attacker.test/font.woff2',
        'known-component-invalid-variant' => $payload['components']['button'] = 'scripted',
        default => throw new LogicException('Unknown DesignSpec traversal case.'),
    };

    ValidateDesignSpecAction::run($payload);
})->with([
    '../ traversal' => 'dot-dot-traversal',
    'arbitrary URL in fontAssetId' => 'arbitrary-font-url',
    'invalid variant for known component' => 'known-component-invalid-variant',
])->throws(InvalidArgumentException::class);

it('enforces the closed large-text palette role at three-to-one contrast', function (string $case): void {
    $payload = canonicalDesignSpec();
    if ($case === 'missing') {
        unset($payload['palette']['light']['largeText']);
    } else {
        $payload['palette']['light']['largeText'] = '#A0A0A0';
    }

    ValidateDesignSpecAction::run($payload);
})->with(['missing', 'below three-to-one'])->throws(InvalidArgumentException::class);

it('ratchets every named DesignSpec threat-model case', function (string $case): void {
    $payload = canonicalDesignSpec();
    applyDesignSpecThreatCase($payload, $case);

    ValidateDesignSpecAction::run($payload);
})->with([
    'absolute paths' => 'absolute-path',
    'NUL paths' => 'nul-path',
    'backslash paths' => 'backslash-path',
    'symlink paths' => 'symlink-path',
    'duplicate normalized paths' => 'duplicate-normalized-paths',
    'HTTP URLs' => 'http-url',
    'data URLs' => 'data-url',
    'javascript URLs' => 'javascript-url',
    'inline SVG' => 'inline-svg',
    'remote CSS' => 'remote-css',
    'executables' => 'executable',
    'Composer dependencies' => 'composer',
    'npm dependencies' => 'npm',
    'plugins' => 'plugins',
    'hooks' => 'hooks',
    'workflows' => 'workflows',
    'binaries' => 'binaries',
    'unsupported locales' => 'unsupported-locale',
    'excessive items' => 'excessive-items',
    'excessive strings' => 'excessive-string',
    'excessive file bytes' => 'excessive-file-bytes',
    'excessive total bytes' => 'excessive-total-bytes',
    'large-text contrast' => 'large-text-contrast',
    'cyclic aliases' => 'cyclic-aliases',
    'prompt metadata' => 'prompt-metadata',
    'project metadata' => 'project-metadata',
    'version metadata' => 'version-metadata',
    'billing metadata' => 'billing-metadata',
    'preview metadata' => 'preview-metadata',
    'editor metadata' => 'editor-metadata',
])->throws(InvalidArgumentException::class);

it('applies the same nesting limit to array and JSON documents', function (string $input): void {
    $payload = canonicalDesignSpec();
    $nested = ['value' => true];
    for ($depth = 0; $depth < DesignSpecConstraints::MAX_NESTING_DEPTH; $depth++) {
        $nested = ['nested' => $nested];
    }
    $payload['nested'] = $nested;

    ValidateDesignSpecAction::run($input === 'json'
        ? json_encode($payload, JSON_THROW_ON_ERROR)
        : $payload);
})->with(['array', 'json'])->throws(InvalidArgumentException::class, 'document.too_deep: $');

it('rejects rather than normalizes whitespace and lowercase colours', function (string $path, string $value): void {
    $payload = canonicalDesignSpec();
    if ($path === 'display.name') {
        $payload['display']['name'] = $value;
    } else {
        $payload['palette']['light']['primary'] = $value;
    }

    ValidateDesignSpecAction::run($payload);
})->with([
    ['display.name', ' Capell Studio'],
    ['palette.light.primary', '#1d4ed8'],
])->throws(InvalidArgumentException::class);

it('returns deterministic path-coded errors without reflecting attacker values', function (): void {
    $payload = canonicalDesignSpec();
    $payload['display']['name'] = '<attacker-secret>';

    try {
        ValidateDesignSpecAction::run($payload);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toBe('text.invalid: $.display.name')
            ->not->toContain('attacker-secret');

        return;
    }

    $this->fail('Expected invalid DesignSpec display name to be rejected.');
});

it('does not reflect attacker-controlled unknown field names', function (): void {
    $payload = canonicalDesignSpec();
    $payload['brand']['attacker-secret-field'] = true;

    try {
        ValidateDesignSpecAction::run($payload);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toBe('field.unknown: $.brand')
            ->not->toContain('attacker-secret-field');

        return;
    }

    $this->fail('Expected unknown DesignSpec field to be rejected.');
});

it('rejects oversized documents before parsing', function (): void {
    ValidateDesignSpecAction::run(str_repeat(' ', DesignSpecConstraints::MAX_DOCUMENT_BYTES + 1));
})->throws(InvalidArgumentException::class, 'document.too_large: $');
