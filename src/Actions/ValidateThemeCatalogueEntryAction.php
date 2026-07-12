<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\FoundationTheme\Data\ThemeValidationResultData;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * Wave 1.4 — checks three-way agreement for a single theme package: its
 * `capell.json` manifest, its `docs/themes.json` catalogue entry, and its
 * registered `ThemeDefinitionData` (obtained by calling the theme's own
 * service provider `definition(): ThemeDefinitionData` static method — the
 * exact class named in the manifest's `providers.runtime[0]`). Also checks
 * `docs/screenshots.json` manifest completeness and that the catalogue's
 * classification fields are populated.
 *
 * This extracts the cross-check logic that previously lived only inline in
 * `ThemeCatalogueTest` and `ThemePackageManifestTest`, so `capell:validate-themes`
 * and those two Pest suites share one source of truth.
 *
 * Deliberately requires nothing but Composer autoloading — `definition()` is
 * a static method with no framework or database dependency, so this action
 * (and therefore `capell:validate-themes`) runs from a plain
 * `vendor/autoload.php`-only script, exactly like `scripts/audit-manifest-v3.php`,
 * with no Testbench/Laravel boot required.
 *
 * @method static ThemeValidationResultData run(string $packageDirectory, string $packagesRoot)
 */
final class ValidateThemeCatalogueEntryAction
{
    use AsObject;

    /**
     * The `ProvidesThemeDemoContent` contract's 7 canonical surface names
     * (see `ThemeDemoPageDefinition::$surface`). Wave 0.7's interior-page
     * audit found 17 of 19 themes capture screenshots under ad hoc names
     * (`landing`, `listing`, `search`, ...) instead of these, and none of
     * those 17 have `empty`, `not-found`, or `cta` captured at all — a raw
     * entry count can't catch that, so manifest completeness must gate on
     * every surface name being present.
     *
     * @var list<string>
     */
    private const array REQUIRED_DEMO_SURFACES = [
        'homepage',
        'directory',
        'detail',
        'contact',
        'empty',
        'not-found',
        'cta',
    ];

    /**
     * @var list<string>
     */
    private const array REQUIRED_CLASSIFICATION_FIELDS = [
        'audience',
        'lane',
        'overlapRisk',
        'priorityPhase',
        'visualDifferentiators',
        'notes',
    ];

    public function handle(string $packageDirectory, string $packagesRoot): ThemeValidationResultData
    {
        $manifestPath = $packagesRoot . '/' . $packageDirectory . '/capell.json';
        $manifest = $this->readJsonObject($manifestPath);

        $themeKey = is_string($manifest['themeKey'] ?? null) ? $manifest['themeKey'] : $packageDirectory;

        $violations = [
            ...$this->manifestViolations($manifest, $themeKey),
            ...$this->integrationViolations($packagesRoot . '/' . $packageDirectory, $manifest, $themeKey),
            ...$this->catalogueViolations($packagesRoot, $manifest, $themeKey),
            ...$this->definitionViolations($manifest, $themeKey),
            ...$this->screenshotManifestViolations($packagesRoot, $packageDirectory, $themeKey, $manifest),
        ];

        return new ThemeValidationResultData(
            themeKey: $themeKey,
            violations: $violations,
        );
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function manifestViolations(array $manifest, string $themeKey): array
    {
        $violations = [];

        if (! is_string($manifest['themeKey'] ?? null) || $manifest['themeKey'] === '') {
            $violations[] = "{$themeKey}: capell.json is missing a themeKey.";
        }

        if (! array_key_exists('extends', $manifest)) {
            $violations[] = "{$themeKey}: capell.json is missing an extends key.";
        }

        if (! is_array($manifest['product'] ?? null) || ! is_string($manifest['product']['tier'] ?? null)) {
            $violations[] = "{$themeKey}: capell.json is missing product.tier.";
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function integrationViolations(string $themeDirectory, array $manifest, string $themeKey): array
    {
        $supports = $manifest['dependencies']['supports'] ?? [];

        if (! is_array($supports)) {
            return ["{$themeKey}: dependencies.supports must be an array."];
        }

        $violations = [];

        foreach ($supports as $packageName) {
            if (! is_string($packageName) || $packageName === '') {
                $violations[] = "{$themeKey}: dependencies.supports entries must be non-empty package names.";

                continue;
            }

            if (! HasThemeIntegrationEvidenceAction::run($themeDirectory, $packageName)) {
                $violations[] = "{$themeKey}: dependencies.supports claims {$packageName} without a corresponding integration marker.";
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function catalogueViolations(string $packagesRoot, array $manifest, string $themeKey): array
    {
        $cataloguePath = dirname($packagesRoot) . '/docs/themes.json';
        $catalogue = $this->readJsonObject($cataloguePath);

        $entries = is_array($catalogue['themes'] ?? null) ? $catalogue['themes'] : [];

        $catalogueEntry = null;

        foreach ($entries as $entry) {
            if (is_array($entry) && ($entry['themeKey'] ?? null) === $themeKey) {
                $catalogueEntry = $entry;

                break;
            }
        }

        if ($catalogueEntry === null) {
            return ["{$themeKey}: no docs/themes.json entry found for this theme."];
        }

        $violations = [];

        if (($catalogueEntry['package'] ?? null) !== ($manifest['name'] ?? null)) {
            $violations[] = "{$themeKey}: docs/themes.json package ({$this->describe($catalogueEntry['package'] ?? null)}) does not match capell.json name ({$this->describe($manifest['name'] ?? null)}).";
        }

        $manifestExtends = $manifest['extends'] ?? null;
        $catalogueExtends = $catalogueEntry['extends'] ?? null;

        if ($manifestExtends !== $catalogueExtends) {
            $violations[] = "{$themeKey}: docs/themes.json extends ({$this->describe($catalogueExtends)}) does not match capell.json extends ({$this->describe($manifestExtends)}).";
        }

        $manifestTier = is_array($manifest['product'] ?? null) ? ($manifest['product']['tier'] ?? null) : null;
        $catalogueTier = $catalogueEntry['tier'] ?? null;

        // docs/themes.json `tier` is a catalogue/governance classification
        // (`foundation|free|premium|experimental|candidate-for-merge`, see
        // ThemeCatalogueTest's $allowedTiers) while capell.json `product.tier`
        // is a commercial billing tier (`free|premium`) — two different
        // taxonomies that happen to share the word "tier". They only need to
        // agree for the `free`/`premium` catalogue tiers; `foundation` (the
        // single root theme every other theme extends) legitimately pairs
        // with either commercial tier and is not a disagreement.
        if ($manifestTier !== null && $catalogueTier !== null && $catalogueTier !== 'foundation' && $manifestTier !== $catalogueTier) {
            $violations[] = "{$themeKey}: docs/themes.json tier ({$this->describe($catalogueTier)}) does not match capell.json product.tier ({$this->describe($manifestTier)}).";
        }

        foreach (self::REQUIRED_CLASSIFICATION_FIELDS as $field) {
            if (! $this->hasNonEmptyValue($catalogueEntry, $field)) {
                $violations[] = "{$themeKey}: docs/themes.json is missing a populated \"{$field}\" classification field.";
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function definitionViolations(array $manifest, string $themeKey): array
    {
        $providerClass = $this->providerClass($manifest);

        if ($providerClass === null) {
            return ["{$themeKey}: capell.json providers.runtime[0] is missing or not a string."];
        }

        if (! class_exists($providerClass)) {
            return ["{$themeKey}: service provider class {$providerClass} does not exist or is not autoloadable."];
        }

        if (! method_exists($providerClass, 'definition')) {
            return ["{$themeKey}: {$providerClass} does not declare a static definition() method."];
        }

        $definition = $providerClass::definition();

        if (! $definition instanceof ThemeDefinitionData) {
            return ["{$themeKey}: {$providerClass}::definition() did not return a ThemeDefinitionData instance."];
        }

        $violations = [];

        if ($definition->key !== $themeKey) {
            $violations[] = "{$themeKey}: ThemeDefinitionData key ({$definition->key}) does not match themeKey.";
        }

        if ($definition->package !== ($manifest['name'] ?? null)) {
            $violations[] = "{$themeKey}: ThemeDefinitionData package ({$definition->package}) does not match capell.json name ({$this->describe($manifest['name'] ?? null)}).";
        }

        $manifestExtends = $manifest['extends'] ?? null;

        if ($definition->extends !== $manifestExtends) {
            $violations[] = "{$themeKey}: ThemeDefinitionData extends ({$this->describe($definition->extends)}) does not match capell.json extends ({$this->describe($manifestExtends)}).";
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function providerClass(array $manifest): ?string
    {
        $providers = $manifest['providers'] ?? null;

        if (! is_array($providers)) {
            return null;
        }

        $runtimeProviders = $providers['runtime'] ?? null;

        if (! is_array($runtimeProviders) || ! is_string($runtimeProviders[0] ?? null)) {
            return null;
        }

        return $runtimeProviders[0];
    }

    /**
     * @return list<string>
     */
    private function screenshotManifestViolations(
        string $packagesRoot,
        string $packageDirectory,
        string $themeKey,
        array $manifest,
    ): array {
        $screenshotsPath = $packagesRoot . '/' . $packageDirectory . '/docs/screenshots.json';

        if (! is_file($screenshotsPath)) {
            return ["{$themeKey}: docs/screenshots.json is missing."];
        }

        $screenshots = $this->readJsonObject($screenshotsPath);
        $entries = is_array($screenshots['entries'] ?? null) ? $screenshots['entries'] : [];

        $violations = $this->promotedScreenshotViolations($packageDirectory, $themeKey, $manifest, $entries);
        $missingSurfaces = array_values(array_diff(
            self::REQUIRED_DEMO_SURFACES,
            $this->capturedDemoSurfaces($entries),
        ));

        if ($missingSurfaces !== []) {
            $violations[] = sprintf(
                '%s: docs/screenshots.json is missing captures for the DemoContent surface(s): %s.',
                $themeKey,
                implode(', ', $missingSurfaces),
            );
        }

        return $violations;
    }

    /**
     * Marketplace-promoted runner captures are release evidence, not optional
     * examples. Every promoted PNG must map to a reproducible manifest entry
     * and that entry must fail the screenshot run when capture breaks.
     *
     * @param  array<string, mixed>  $manifest
     * @param  array<mixed>  $entries
     * @return list<string>
     */
    private function promotedScreenshotViolations(
        string $packageDirectory,
        string $themeKey,
        array $manifest,
        array $entries,
    ): array {
        $promotedScreenshots = $manifest['marketplace']['screenshots'] ?? [];

        if (! is_array($promotedScreenshots)) {
            return [];
        }

        $violations = [];

        foreach ($promotedScreenshots as $promotedScreenshot) {
            if (! is_array($promotedScreenshot) || ! is_string($promotedScreenshot['path'] ?? null)) {
                continue;
            }

            $path = $promotedScreenshot['path'];

            if (! str_starts_with($path, 'docs/screenshots/')) {
                continue;
            }

            $screenshotPath = 'packages/' . $packageDirectory . '/' . $path;
            $matchingEntry = null;

            foreach ($entries as $entry) {
                if (is_array($entry) && ($entry['screenshotPath'] ?? null) === $screenshotPath) {
                    $matchingEntry = $entry;

                    break;
                }
            }

            if ($matchingEntry === null) {
                $violations[] = "{$themeKey}: promoted screenshot {$path} has no reproducible docs/screenshots.json entry.";

                continue;
            }

            if (($matchingEntry['required'] ?? null) !== true) {
                $violations[] = "{$themeKey}: promoted screenshot {$path} must be required in docs/screenshots.json.";
            }
        }

        return $violations;
    }

    /**
     * Matches each entry's `id` against the contract's surface names, e.g.
     * `liquid-glass-not-found-mobile` covers `not-found` once its
     * `-tablet`/`-mobile` viewport suffix is stripped. Ad hoc names such as
     * `photography-contact-form` deliberately do not match `contact` — the
     * whole point of this check is to gate on the contract's exact names.
     *
     * @param  array<mixed>  $entries
     * @return list<string>
     */
    private function capturedDemoSurfaces(array $entries): array
    {
        $covered = [];

        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['id'] ?? null)) {
                continue;
            }

            $baseId = (string) preg_replace('/-(?:desktop|tablet|mobile)$/', '', $entry['id']);

            foreach (self::REQUIRED_DEMO_SURFACES as $surface) {
                if (str_ends_with($baseId, '-' . $surface)) {
                    $covered[$surface] = $surface;
                }
            }
        }

        return array_values($covered);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonObject(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Expected JSON file at ' . $path . '.');
        }

        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Expected a JSON object at ' . $path . '.');
        }

        $object = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $object[$key] = $value;
            }
        }

        return $object;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function hasNonEmptyValue(array $entry, string $field): bool
    {
        if (! array_key_exists($field, $entry)) {
            return false;
        }

        $value = $entry[$field];

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null;
    }

    private function describe(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) json_encode($value);
    }
}
