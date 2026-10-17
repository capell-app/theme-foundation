<?php

declare(strict_types=1);

use Capell\Core\Actions\EnablePackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\ProjectBuild\ProjectBuildArtifactHandlerRegistry;
use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Events\Dispatcher;

it('matches fresh installed boot after in-process installation and repeated refresh', function (): void {
    InstalledRuntimeParity::assertPackage('theme-foundation');
});

it('activates a preloaded foundation when enabling it without legacy callback replay', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false);
        $handlers = $app->make(ProjectBuildArtifactHandlerRegistry::class);
        $before = $handlers->types();
        $package = CapellCore::getPackage('capell-app/theme-foundation');
        EnablePackageAction::run($package);
        $listeners = $app->make(Dispatcher::class)->getRawListeners()[PackageUninstalled::class] ?? [];
        throw_unless(is_array($listeners), LogicException::class, 'Expected registered listener occurrences.');
        expect(array_filter($listeners, static fn (mixed $listener): bool => $listener === [RunTailwindAssetsOnPackageChange::class, 'handleUninstalled']))->toHaveCount(1);
        expect($handlers->types())->toContain(CompileFoundationThemeArtifactAction::ARTIFACT_TYPE)
            ->and($before)->toContain(CompileFoundationThemeArtifactAction::ARTIFACT_TYPE);

        RefreshInstalledPackageRuntimeAction::run($package, replayBootedCallbacks: false);
        RefreshInstalledPackageRuntimeAction::run($package, replayBootedCallbacks: false);
        $listeners = $app->make(Dispatcher::class)->getRawListeners()[PackageUninstalled::class] ?? [];
        throw_unless(is_array($listeners), LogicException::class, 'Expected registered listener occurrences.');
        expect(array_filter($listeners, static fn (mixed $listener): bool => $listener === [RunTailwindAssetsOnPackageChange::class, 'handleUninstalled']))->toHaveCount(1);
    });
});
