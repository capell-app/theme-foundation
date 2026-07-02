<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\FoundationTheme\Actions\InstallFoundationThemeLayoutDefaultsAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Relations\Relation;

it('registers layout builder morph models during a same-process fresh install', function (): void {
    $originalMorphMap = Relation::morphMap();
    $morphMapWithoutLayoutBuilder = array_filter(
        $originalMorphMap,
        static fn (string $model): bool => ! in_array($model, [Widget::class, WidgetAsset::class], true),
    );

    Relation::morphMap($morphMapWithoutLayoutBuilder, merge: false);

    try {
        $result = InstallFoundationThemeLayoutDefaultsAction::run();

        expect($result['created'])->toBeGreaterThanOrEqual(2)
            ->and(Relation::getMorphedModel('widget'))->toBe(Widget::class)
            ->and(Relation::getMorphedModel('widget_asset'))->toBe(WidgetAsset::class)
            ->and(Layout::query()->where('key', LayoutEnum::Home->value)->firstOrFail()->widgets)->toBe(['page-content']);
    } finally {
        Relation::morphMap($originalMorphMap, merge: false);
    }
});
