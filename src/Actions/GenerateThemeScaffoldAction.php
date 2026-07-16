<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\ThemeScaffoldRequestData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * Generates a full `platform`-shaped theme package scaffold from the stub
 * templates under `packages/theme-foundation/stubs/make-theme/`, driven by a
 * {@see ThemeScaffoldRequestData} request. Rendering is plain string
 * placeholder replacement (`strtr()`), not Blade compilation — stub files use
 * `{{ placeholder }}` tokens that are resolved once per file and written
 * verbatim to the destination package directory.
 *
 * @method static list<string> run(ThemeScaffoldRequestData $request)
 */
final class GenerateThemeScaffoldAction
{
    use AsFake;
    use AsObject;

    /**
     * Maps each stub template path (relative to the `stubs/make-theme/`
     * directory) to the destination path (relative to the generated
     * package's own root directory).
     *
     * @var array<string, string>
     */
    private const array STUB_TO_DESTINATION = [
        'capell.json.stub' => 'capell.json',
        'composer.json.stub' => 'composer.json',
        'src/ThemeServiceProvider.php.stub' => 'src/{{ studlyName }}ThemeServiceProvider.php',
        'src/Support/Demo/DemoContent.php.stub' => 'src/Support/Demo/{{ studlyName }}DemoContent.php',
        'src/Actions/InstallThemeDemoAction.php.stub' => 'src/Actions/Install{{ studlyName }}ThemeDemoAction.php',
        'src/Console/Commands/DemoCommand.php.stub' => 'src/Console/Commands/{{ studlyName }}DemoCommand.php',
        'docs/screenshots.json.stub' => 'docs/screenshots.json',
        'tests/Pest.php.stub' => 'tests/Pest.php',
        'tests/Unit/PublicOutputSafetyTest.php.stub' => 'tests/Unit/PublicOutputSafetyTest.php',
        'tests/Unit/ManifestTest.php.stub' => 'tests/Unit/ManifestTest.php',
        'tests/Unit/DefinitionTest.php.stub' => 'tests/Unit/DefinitionTest.php',
    ];

    /**
     * @return list<string> Absolute paths of every file written, in the same
     *                      order as {@see STUB_TO_DESTINATION}.
     */
    public function handle(ThemeScaffoldRequestData $request): array
    {
        $stubsDirectory = $this->stubsDirectory();
        $placeholders = $this->placeholders($request);
        $packageDirectory = $request->packageDirectory();

        $writtenFiles = [];

        foreach (self::STUB_TO_DESTINATION as $stubRelativePath => $destinationRelativePath) {
            $stubPath = $stubsDirectory . '/' . $stubRelativePath;

            if (! is_file($stubPath)) {
                throw new RuntimeException('Missing make-theme stub template at ' . $stubPath . '.');
            }

            $stubContents = file_get_contents($stubPath);

            if ($stubContents === false) {
                throw new RuntimeException('Could not read make-theme stub template at ' . $stubPath . '.');
            }

            $renderedContents = strtr($stubContents, $placeholders);
            $renderedDestinationRelativePath = strtr($destinationRelativePath, $placeholders);
            $destinationPath = $packageDirectory . '/' . $renderedDestinationRelativePath;

            $this->ensureDirectoryExists(dirname($destinationPath));

            if (file_put_contents($destinationPath, $renderedContents) === false) {
                throw new RuntimeException('Could not write generated theme file to ' . $destinationPath . '.');
            }

            $writtenFiles[] = $destinationPath;
        }

        return $writtenFiles;
    }

    /**
     * @return array<string, string>
     */
    private function placeholders(ThemeScaffoldRequestData $request): array
    {
        return [
            '{{ themeSlug }}' => $request->themeSlug,
            '{{ displayName }}' => $request->displayName,
            '{{ tier }}' => $request->tier,
            '{{ family }}' => $request->family,
            '{{ studlyName }}' => $request->studlyName(),
            '{{ rootNamespace }}' => $request->rootNamespace(),
            '{{ rootNamespaceJson }}' => str_replace('\\', '\\\\', $request->rootNamespace()),
            '{{ composerPackageName }}' => $request->composerPackageName(),
            '{{ packageDirectoryName }}' => $request->packageDirectoryName(),
            '{{ themeManifestFunctionName }}' => lcfirst($request->studlyName()) . 'ManifestJson',
        ];
    }

    private function stubsDirectory(): string
    {
        return dirname(__DIR__, 2) . '/stubs/make-theme';
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, recursive: true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create directory ' . $directory . '.');
        }
    }
}
