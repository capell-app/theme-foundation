<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use FilesystemIterator;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Proves that a theme's optional-package claim reaches a real public or
 * provider integration point. Manifest metadata alone is not evidence.
 *
 * @method static bool run(string $themeDirectory, string $packageName)
 */
final class HasThemeIntegrationEvidenceAction
{
    use AsFake;
    use AsObject;

    public function handle(string $themeDirectory, string $packageName): bool
    {
        $source = $this->integrationSource($themeDirectory);

        return match ($packageName) {
            'capell-app/form-builder' => str_contains($source, 'FormBuilderAvailability')
                || str_contains($source, 'BuildThemeDemoFormSectionAction'),
            'capell-app/newsletter' => str_contains($source, '<x-capell::newsletter-form'),
            'capell-app/blog' => str_contains($source, 'BlogAvailability'),
            'capell-app/bookings' => str_contains($source, 'BookingsAvailability'),
            'capell-app/knowledge-base' => str_contains($source, 'KnowledgeBaseIntegration'),
            'capell-app/widget-live-poll' => str_contains($source, 'LivePollIntegration'),
            default => false,
        };
    }

    private function integrationSource(string $themeDirectory): string
    {
        $source = '';

        foreach (['src', 'resources/views'] as $relativeDirectory) {
            $directory = $themeDirectory . '/' . $relativeDirectory;

            if (! is_dir($directory)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if (! $file instanceof SplFileInfo) {
                    continue;
                }

                if (! $file->isFile() || ! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if (is_string($contents)) {
                    $source .= "\n" . $contents;
                }
            }
        }

        return $source;
    }
}
