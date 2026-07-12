<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Illuminate\Console\Command;
use Stringable;

final class DemoCommand extends Command
{
    protected $signature = 'capell:theme-foundation-demo
        {--site=* : Site name(s) to seed. Defaults to the existing default site or Demo.}
        {--language=* : Language code(s) to seed. Defaults to the existing default language or en.}
        {--base-url= : Base URL used for seeded demo links. Defaults to app.url.}
        {--force : Rebuild existing Foundation demo page layouts}';

    protected $description = 'Install Foundation theme demo pages.';

    public function handle(): int
    {
        $result = InstallFoundationThemeDemoAction::run(new ThemeDemoInstallData(
            siteNames: $this->stringOptions('site'),
            languageCodes: $this->stringOptions('language'),
            baseUrl: $this->baseUrl(),
            force: (bool) $this->option('force'),
        ));

        $this->info('Foundation theme demo pages installed.');

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function stringOptions(string $name): array
    {
        $options = $this->option($name);

        if (! is_array($options)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn (mixed $value): string => is_scalar($value) || $value instanceof Stringable
                    ? trim((string) $value)
                    : '',
                $options,
            ),
            static fn (string $value): bool => $value !== '',
        ));
    }

    private function baseUrl(): string
    {
        $baseUrl = $this->option('base-url');

        if (is_string($baseUrl) && trim($baseUrl) !== '') {
            return trim($baseUrl);
        }

        $configuredUrl = config('app.url', 'http://localhost');

        return is_string($configuredUrl) && trim($configuredUrl) !== ''
            ? trim($configuredUrl)
            : 'http://localhost';
    }
}
