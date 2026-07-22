<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\DesignSpec;

final class DesignSpecConstraints
{
    public const int SCHEMA_VERSION = 1;

    public const string TEMPLATE = 'foundation';

    public const int MAX_DOCUMENT_BYTES = 65_536;

    public const int MAX_NESTING_DEPTH = 8;

    public const int JSON_DECODE_DEPTH = 32;

    public const int MAX_SITES = 8;

    public const int MAX_LOCALES = 16;

    public const int MAX_ASSET_FILES = 32;

    public const int MAX_ASSET_BYTES = 5_242_880;

    public const int MAX_TOTAL_ASSET_BYTES = 26_214_400;

    public const int MAX_IDENTIFIER_LENGTH = 64;

    public const int MAX_COMPONENT_SELECTIONS = 4;

    public const int MAX_PALETTE_COLORS = 9;

    public const string TEXT_PATTERN = '^(?!\\s)(?!.*\\s$)[^\\x00-\\x1F\\x7F<>`{}]+$';

    public const float MIN_TEXT_CONTRAST = 4.5;

    public const float MIN_LARGE_TEXT_CONTRAST = 3.0;

    public const float MIN_UI_CONTRAST = 3.0;

    /** @var list<string> */
    public const array SUPPORTED_LOCALES = [
        'ar', 'ar-SA', 'cy', 'cy-GB', 'de', 'de-DE', 'en', 'en-GB', 'en-US',
        'es', 'es-ES', 'fr', 'fr-FR', 'it', 'it-IT', 'nl', 'nl-NL', 'pt', 'pt-PT',
    ];

    /** @var list<string> */
    public const array FONT_FAMILIES = ['system', 'sans', 'serif', 'mono'];

    /** @var list<string> */
    public const array FONT_STYLES = ['normal', 'italic'];

    /** @var list<int> */
    public const array FONT_WEIGHTS = [400, 500, 600, 700, 800];

    /** @var array<string, list<string>> */
    public const array LAYOUT_OPTIONS = [
        'container' => ['compact', 'standard', 'wide'],
        'density' => ['compact', 'comfortable', 'spacious'],
        'spacing' => ['tight', 'normal', 'relaxed'],
        'radius' => ['none', 'small', 'medium', 'large'],
        'darkMode' => ['light', 'dark', 'system'],
    ];

    /** @var array<string, list<string>> */
    public const array COMPONENT_VARIANTS = [
        'button' => ['solid', 'outline', 'soft'],
        'card' => ['flat', 'bordered', 'elevated'],
        'navigation' => ['inline', 'drawer', 'hybrid'],
        'hero' => ['split', 'centered', 'editorial'],
    ];

    /** @var array<string, array{minimum: int, maximum: int}> */
    public const array TEXT_FIELDS = [
        'display.name' => ['minimum' => 1, 'maximum' => 80],
        'display.description' => ['minimum' => 1, 'maximum' => 240],
        'site.name' => ['minimum' => 1, 'maximum' => 120],
        'locale.label' => ['minimum' => 1, 'maximum' => 80],
        'brand.name' => ['minimum' => 1, 'maximum' => 120],
    ];

    /**
     * Server-owned asset catalogue. DesignSpec may select only these logical IDs;
     * paths and URLs never cross the model boundary.
     *
     * @var array<string, array{kind: 'font'|'image', bytes: int}>
     */
    public const array ASSET_CATALOGUE = [
        'font-mono-latin' => ['kind' => 'font', 'bytes' => 98_304],
        'font-sans-arabic' => ['kind' => 'font', 'bytes' => 131_072],
        'font-serif-latin' => ['kind' => 'font', 'bytes' => 114_688],
        'logo-foundation' => ['kind' => 'image', 'bytes' => 16_384],
    ];

    /** @var list<string> */
    public const array FORBIDDEN_KEYS = [
        'binaries', 'commands', 'composer', 'dependencies', 'dependency', 'deploy',
        'executable', 'hooks', 'launch', 'npm', 'plugins', 'publish', 'scripts',
        'source', 'workflow', 'workflows',
    ];

    private function __construct() {}
}
