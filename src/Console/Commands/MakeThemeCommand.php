<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Actions\GenerateThemeScaffoldAction;
use Capell\FoundationTheme\Data\ThemeScaffoldRequestData;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * `capell:make-theme` — scaffolds a new, platform-shaped, layout-native
 * theme package. Thin orchestration only: collects input (prompting for
 * anything not passed as an option), delegates generation to
 * {@see GenerateThemeScaffoldAction}, and prints a summary of what was
 * written. No template-rendering logic lives in this command.
 */
final class MakeThemeCommand extends Command
{
    protected $signature = 'capell:make-theme
        {slug? : Kebab-case theme slug, e.g. "business"}
        {--name= : Display name shown in the marketplace, e.g. "Business"}
        {--tier= : Commercial tier: free or premium}
        {--family= : Catalogue family/lane, e.g. "service-business"}
        {--path= : Base packages directory to scaffold into (defaults to the monorepo packages/ directory)}';

    protected $description = 'Scaffold a new Capell theme package (capell.json, service provider, demo content, and tests).';

    public function handle(): int
    {
        try {
            $request = $this->buildRequest();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $writtenFiles = GenerateThemeScaffoldAction::run($request);

        $this->info(sprintf(
            'Scaffolded theme "%s" (%s) at %s',
            $request->displayName,
            $request->themeKey(),
            $request->packageDirectory(),
        ));

        foreach ($writtenFiles as $writtenFile) {
            $this->line(' - ' . $writtenFile);
        }

        return self::SUCCESS;
    }

    private function buildRequest(): ThemeScaffoldRequestData
    {
        $themeSlug = $this->resolveThemeSlug();
        $displayName = $this->resolveDisplayName($themeSlug);
        $tier = $this->resolveTier();
        $family = $this->resolveFamily();
        $basePackagesPath = $this->resolveBasePackagesPath();

        return new ThemeScaffoldRequestData(
            themeSlug: $themeSlug,
            displayName: $displayName,
            tier: $tier,
            family: $family,
            basePackagesPath: $basePackagesPath,
        );
    }

    private function resolveThemeSlug(): string
    {
        $argument = $this->argument('slug');

        if (is_string($argument) && trim($argument) !== '') {
            return $argument;
        }

        $answer = $this->ask('Theme slug (kebab-case, e.g. "business")');

        return is_string($answer) ? $answer : '';
    }

    private function resolveDisplayName(string $themeSlug): string
    {
        $option = $this->option('name');

        if (is_string($option) && trim($option) !== '') {
            return $option;
        }

        $suggestedDisplayName = ucwords(str_replace('-', ' ', $themeSlug));
        $answer = $this->ask('Display name', $suggestedDisplayName);

        return is_string($answer) ? $answer : $suggestedDisplayName;
    }

    private function resolveTier(): string
    {
        $option = $this->option('tier');

        if (is_string($option) && trim($option) !== '') {
            return $option;
        }

        $answer = $this->choice('Tier', ThemeScaffoldRequestData::ALLOWED_TIERS, 1);

        return is_string($answer) ? $answer : 'premium';
    }

    private function resolveFamily(): string
    {
        $option = $this->option('family');

        if (is_string($option) && trim($option) !== '') {
            return $option;
        }

        $answer = $this->ask('Catalogue family (e.g. "service-business")');

        return is_string($answer) ? $answer : '';
    }

    private function resolveBasePackagesPath(): string
    {
        $option = $this->option('path');

        if (is_string($option) && trim($option) !== '') {
            return $option;
        }

        return $this->defaultPackagesRoot();
    }

    /**
     * Resolves the real monorepo `packages/` directory, mirroring
     * `ThemeCatalogueReportCommand::packagesRoot()`'s resolution order: first
     * derive it relative to this package's own install location, then fall
     * back to the application's `base_path('packages')`.
     */
    private function defaultPackagesRoot(): string
    {
        $candidate = dirname(__DIR__, 4);

        if (is_dir($candidate) && glob($candidate . '/theme-*') !== []) {
            return $candidate;
        }

        if (function_exists('base_path')) {
            $fallback = base_path('packages');

            if (is_dir($fallback)) {
                return $fallback;
            }
        }

        return $candidate;
    }
}
