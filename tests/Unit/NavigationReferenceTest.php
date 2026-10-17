<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\View\Components\Widget\Navigation;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Navigation\Models\Navigation as NavigationModel;

it('resolves numeric string keys before colliding legacy IDs in navigation widgets', function (bool $collision): void {
    CapellCore::forcePackageInstalled('capell-app/navigation');
    $site = Site::factory()->createOne();
    $language = Language::factory()->createOne();
    $other = NavigationModel::factory()->site($site)->language($language)->createOne(['key' => 'other-menu']);
    $menu = NavigationModel::factory()->site($site)->language($language)->createOne([
        'key' => $collision ? (string) $other->id : '2026',
    ]);
    $state = new FrontendState(site: $site, language: $language);
    app()->instance(FrontendState::class, $state);
    app()->instance(FrontendContextReader::class, $state);
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    $widget = new Widget(['meta' => ['navigation' => $menu->key]]);

    $component = new Navigation([], 'main', 0, new stdClass, $widget);

    expect($component->menu?->getKey())->toBe($menu->getKey());

    foreach (['navigation', 'navigation_id'] as $field) {
        foreach ([$menu->id, (string) $menu->id] as $legacyId) {
            $legacyWidget = new Widget(['meta' => [$field => $legacyId]]);
            $legacyComponent = new Navigation([], 'main', 0, new stdClass, $legacyWidget);

            expect($legacyComponent->menu?->getKey())->toBe($menu->getKey());
        }
    }

    foreach ([['navigation', $other->id], ['navigation_id', $other->id], ['navigation_id', (string) $other->id]] as [$field, $legacyId]) {
        $legacyWidget = new Widget(['meta' => [$field => $legacyId]]);
        $legacyComponent = new Navigation([], 'main', 0, new stdClass, $legacyWidget);

        expect($legacyComponent->menu?->getKey())->toBe($other->getKey());
    }
})->with(['numeric key' => false, 'key collides with an ID' => true]);
