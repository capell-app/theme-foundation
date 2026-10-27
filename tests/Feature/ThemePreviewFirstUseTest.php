<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Manifest\CapellManifestData;
use Capell\Core\Support\PackageRegistry\CapellPackageRegistry;
use Capell\Marketplace\Filament\Pages\ThemeExtensionPage;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('previews current content privately then applies and restores the selected site theme', function (): void {
    Permission::findOrCreate('View:ThemeExtensionPage', 'web');
    Permission::findOrCreate(ExtensionsPage::MANAGE_PERMISSION, 'web');
    $this->actingAsAdmin();
    $this->authenticatedUser()->givePermissionTo('View:ThemeExtensionPage', ExtensionsPage::MANAGE_PERMISSION);

    $registry = resolve(CapellPackageRegistry::class);
    foreach (['theme-agency', 'theme-awards'] as $slug) {
        $json = file_get_contents(dirname(__DIR__, 3) . '/' . $slug . '/capell.json');
        throw_unless(is_string($json));
        $manifest = CapellManifestData::fromArray(foundationThemeJsonObjectDocument($json));
        CapellCore::registerManifestPackage($manifest);
        $registry->fill([...$registry->all(), $manifest->name => $manifest]);
    }

    $previous = Theme::factory()->create(['key' => 'agency', 'status' => true]);
    $site = Site::factory()->theme($previous)->withTranslations()->create();
    $other = Site::factory()->theme($previous)->create();
    $content = Page::factory()->site($site)->home()->create();
    $before = $content->refresh()->getAttributes();
    $page = resolve(ThemeExtensionPage::class);
    $page->mount('awards');
    $page->scope = 'site';
    $page->siteId = $site->id;

    $response = $page->previewTheme();
    expect($response?->getTargetUrl())->toContain('/admin/theme-preview/', 'signature=')
        ->and($site->refresh()->theme_id)->toBe($previous->id)
        ->and($content->refresh()->getAttributes())->toBe($before);

    $page->applyTheme();
    expect($site->refresh()->theme?->key)->toBe('awards')
        ->and($other->refresh()->theme_id)->toBe($previous->id);

    $page->mount('agency');
    $page->applyTheme();
    expect($site->refresh()->theme_id)->toBe($previous->id)
        ->and($content->refresh()->getAttributes())->toBe($before);
});
