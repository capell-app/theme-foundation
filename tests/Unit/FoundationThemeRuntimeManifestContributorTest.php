<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Support\AuthMenuWidget;
use Capell\FoundationTheme\Support\FoundationThemeRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

it('loads the Foundation runtime when the header contains the auth-menu placeholder', function (): void {
    $layout = new Layout([
        'containers' => [
            'header' => [
                'meta' => ['area' => 'header'],
                'widgets' => [AuthMenuWidget::block()],
            ],
        ],
    ]);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withLayout($layout),
        $manifest,
    );

    expect($manifest->modules['theme-foundation-runtime'] ?? false)->toBeTrue();
});

it('does not load the Foundation runtime without the auth-menu placeholder', function (): void {
    $layout = new Layout(['containers' => ['header' => ['meta' => ['area' => 'header'], 'widgets' => []]]]);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withLayout($layout),
        $manifest,
    );

    expect($manifest->modules)->not->toHaveKey('theme-foundation-runtime');
});

it('hydrates translated site media without assuming every configured media model supports translations', function (): void {
    $site = Site::factory()->create();
    $language = Language::query()->whereKey($site->language_id)->firstOrFail();
    $media = Media::factory()->model($site)->create(['order_column' => 1]);
    $translation = $media->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Translated site media',
    ]);
    $plainMediaRecord = Media::factory()->model($site)->create(['order_column' => 0]);
    $untranslatedMedia = SpatieMedia::query()->whereKey($plainMediaRecord->getKey())->firstOrFail();
    $media->unsetRelation('translations');

    foreach (['image', 'logo', 'logoInverted', 'siteDomain', 'translation'] as $relation) {
        $site->setRelation($relation, null);
    }

    // A broad eager load must encounter the model without translations first.
    $site->setRelation('media', new MediaCollection([$untranslatedMedia, $media]));

    expect($site->getMedia('*')->modelKeys())->toBe([$untranslatedMedia->getKey(), $media->getKey()])
        ->and($media->relationLoaded('translations'))->toBeFalse()
        ->and($untranslatedMedia->relationLoaded('translations'))->toBeFalse();

    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withSite($site),
        FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    );

    expect($media->relationLoaded('translations'))->toBeTrue()
        ->and($untranslatedMedia->relationLoaded('translations'))->toBeFalse();

    $loadedTranslation = $media->translations->sole();
    expect($loadedTranslation->is($translation))->toBeTrue()
        ->and($loadedTranslation->relationLoaded('language'))->toBeTrue()
        ->and($loadedTranslation->language->is($language))->toBeTrue();

    expect(collect($queries)->contains(static fn (string $sql): bool => preg_match('/\bfrom\s+["`]?translations\b/i', $sql) === 1))->toBeTrue()
        ->and(collect($queries)->contains(static fn (string $sql): bool => preg_match('/\bfrom\s+["`]?languages\b/i', $sql) === 1))->toBeTrue();
});
