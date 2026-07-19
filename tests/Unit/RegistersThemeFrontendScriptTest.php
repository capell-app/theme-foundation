<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Tests\Fixtures\RegistersThemeFrontendScriptTestProvider;
use Capell\Frontend\Actions\ResolveFrontendResourcePlanAction;
use Capell\Frontend\Contracts\FrontendResourceContributor;
use Capell\Frontend\Contracts\FrontendResourcePlanRenderer;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

function themeFrontendScriptContext(string $themeKey): FrontendResourceContextData
{
    $theme = new Theme;
    $theme->forceFill(['key' => $themeKey]);

    return new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    );
}

it('publishes a theme build and contributes its script only for the active theme', function (): void {
    (new RegistersThemeFrontendScriptTestProvider(app()))->boot();

    $contributors = collect(app()->tagged(FrontendResourceContributor::TAG))
        ->filter(static fn (mixed $contributor): bool => $contributor instanceof FrontendResourceContributor);

    $inactiveContributions = $contributors
        ->flatMap(static fn (FrontendResourceContributor $contributor): array => $contributor->resources(themeFrontendScriptContext('another-theme')))
        ->filter(static fn (mixed $contribution): bool => $contribution->resource->package === RegistersThemeFrontendScriptTestProvider::PACKAGE_NAME)
        ->values()
        ->all();
    $activeContributions = $contributors
        ->flatMap(static fn (FrontendResourceContributor $contributor): array => $contributor->resources(themeFrontendScriptContext(RegistersThemeFrontendScriptTestProvider::THEME_KEY)))
        ->filter(static fn (mixed $contribution): bool => $contribution->resource->package === RegistersThemeFrontendScriptTestProvider::PACKAGE_NAME)
        ->values();

    $publishPaths = ServiceProvider::pathsToPublish(
        RegistersThemeFrontendScriptTestProvider::class,
        'capell-theme-assets',
    );
    $activeContribution = $activeContributions->first();

    if (! $activeContribution instanceof FrontendResourceContributionData) {
        throw new RuntimeException('Expected one typed frontend resource contribution.');
    }

    $source = $activeContribution->resource->source;

    if (! $source instanceof ViteResourceSourceData) {
        throw new RuntimeException('Expected the theme frontend script to use a Vite resource source.');
    }

    expect($inactiveContributions)->toBe([])
        ->and($activeContributions)->toHaveCount(1)
        ->and($source->entry)->toBe(RegistersThemeFrontendScriptTestProvider::ENTRY)
        ->and($publishPaths)->toBe([
            dirname(__DIR__) . '/publishes/build' => public_path(RegistersThemeFrontendScriptTestProvider::BUILD_DIRECTORY),
        ]);
});

it('resolves the hashed manifest entry as a body-end module script', function (): void {
    $buildDirectory = RegistersThemeFrontendScriptTestProvider::BUILD_DIRECTORY;
    $publicBuildPath = public_path($buildDirectory);
    $hashedFile = 'assets/testing-theme.8f31d4.js';

    File::ensureDirectoryExists($publicBuildPath);
    File::put($publicBuildPath . '/manifest.json', json_encode([
        RegistersThemeFrontendScriptTestProvider::ENTRY => [
            'file' => $hashedFile,
            'isEntry' => true,
            'src' => RegistersThemeFrontendScriptTestProvider::ENTRY,
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        (new RegistersThemeFrontendScriptTestProvider(app()))->boot();

        $context = themeFrontendScriptContext(RegistersThemeFrontendScriptTestProvider::THEME_KEY);
        $contributions = collect(app()->tagged(FrontendResourceContributor::TAG))
            ->filter(static fn (mixed $contributor): bool => $contributor instanceof FrontendResourceContributor)
            ->flatMap(static fn (FrontendResourceContributor $contributor): array => $contributor->resources($context))
            ->filter(static fn (mixed $contribution): bool => $contribution->resource->package === RegistersThemeFrontendScriptTestProvider::PACKAGE_NAME)
            ->values()
            ->all();
        $plan = ResolveFrontendResourcePlanAction::run($contributions);
        $rendered = resolve(FrontendResourcePlanRenderer::class)->render($plan, $context);

        expect($plan->headResources)->toBe([])
            ->and($plan->bodyEndResources)->toHaveCount(1)
            ->and($plan->bodyEndResources[0]->url)->toEndWith('/' . $buildDirectory . '/' . $hashedFile)
            ->and($rendered->bodyEndHtml)->toContain('<script type="module"')
            ->and($rendered->bodyEndHtml)->toContain('/' . $buildDirectory . '/' . $hashedFile)
            ->and($rendered->bodyEndHtml)->not->toContain(RegistersThemeFrontendScriptTestProvider::THEME_KEY . '=');
    } finally {
        File::deleteDirectory($publicBuildPath);
    }
});
