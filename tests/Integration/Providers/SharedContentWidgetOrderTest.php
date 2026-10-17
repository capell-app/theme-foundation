<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Data\Extensions\ExtensionContributionReceiptData;
use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\ExtensionContributionType;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Extensions\ExtensionContributionReceiptRegistry;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\Filament\AuthMenuWidget;
use Capell\FoundationTheme\Support\AuthMenuWidget as AuthMenuWidgetDefinition;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Foundation\Application;

it('retains shared renderables and orders content widgets identically on fresh and late installation and two refreshes', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $manifest = InstalledRuntimeParity::manifest('theme-foundation');
        $root = dirname(__DIR__, 3);
        $renderables = array_map(static fn (FoundationSectionWidgetComponentEnum $component): array => [
            'renderable:layout-widget:' . $component->value,
            RenderableDefinitionData::class,
        ], FoundationSectionWidgetComponentEnum::cases());
        $expected = [...$renderables, [AuthMenuWidgetDefinition::KEY, AuthMenuWidget::class]];
        $keys = array_column($expected, 0);
        $receipts = static function (Application $app) use ($keys): array {
            $app->make(RenderableRegistry::class);

            return array_values(array_map(
                static fn (ExtensionContributionReceiptData $receipt): array => [$receipt->key, $receipt->implementation],
                array_filter(
                    $app->make(ExtensionContributionReceiptRegistry::class)->all(),
                    static fn (ExtensionContributionReceiptData $receipt): bool => $receipt->type === ExtensionContributionType::ContentWidget
                        && in_array($receipt->key, $keys, true),
                ),
            ));
        };

        $late = InstalledRuntimeTestCase::boot($manifest, false, sourceRoot: $root);
        expect(CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName))->toBeTrue()
            ->and(CapellCore::isPackageInstalled($manifest['name']))->toBeFalse();
        $registry = $late->make(RenderableRegistry::class);
        expect(FoundationSectionWidgetComponentEnum::cases())->toHaveCount(11);
        foreach (FoundationSectionWidgetComponentEnum::cases() as $component) {
            expect($registry->get('layout-widget', $component->value)->blade)->toBe('capell-theme-foundation::widget.foundation-section');
        }

        expect($receipts($late))->toBe($renderables);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($late);
        $package = CapellCore::getPackage($manifest['name']);
        InstallPackageAction::run($package);
        $installedReceipts = $receipts($late);
        expect($installedReceipts)->toBe($expected);

        for ($refresh = 0; $refresh < 2; $refresh++) {
            RefreshInstalledPackageRuntimeAction::run($package, replayBootedCallbacks: false);
            expect($receipts($late))->toBe($installedReceipts);
        }

        $fresh = InstalledRuntimeTestCase::boot($manifest, true, sourceRoot: $root);
        expect($receipts($fresh))->toBe($expected)
            ->and($receipts($fresh))->toBe($installedReceipts);
        $package = CapellCore::getPackage($manifest['name']);
        for ($refresh = 0; $refresh < 2; $refresh++) {
            RefreshInstalledPackageRuntimeAction::run($package, replayBootedCallbacks: false);
            expect($receipts($fresh))->toBe($installedReceipts);
        }
    });
});
