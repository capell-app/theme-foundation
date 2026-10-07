<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\Tests\Support\PackageInstallationTestCase;
use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler;

it('registers installed theme widget Blade aliases after metadata has already booted', function (): void {
    PackageInstallationTestCase::assertInProcessInstallation('theme-foundation', function (Application $app, Closure $refresh): void {
        expect($app->make(BladeCompiler::class)->getClassComponentAliases())->not->toHaveKey('capell-app.auth-menu');
        $refresh();
        expect($app->make(BladeCompiler::class)->getClassComponentAliases()['capell-app.auth-menu'] ?? null)->toBe(WidgetExtensionDefinitionAdapter::GATED_COMPONENT);
        $aliases = $app->make(BladeCompiler::class)->getClassComponentAliases();
        $refresh();
        expect($app->make(BladeCompiler::class)->getClassComponentAliases())->toBe($aliases);
    });
});
