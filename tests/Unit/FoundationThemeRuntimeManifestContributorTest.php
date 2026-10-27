<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Support\AuthMenuWidget;
use Capell\FoundationTheme\Support\FoundationThemeRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
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

it('supports custom pageables without optional image relationships while hydrating preloaded translated media', function (bool $withMedia): void {
    /** @var Model&Pageable<Model> $page */
    $page = Mockery::mock(Model::class, Pageable::class)->makePartial();
    $page->setTable('pages');

    $media = null;
    $plainMedia = null;
    $translation = null;

    if ($withMedia) {
        $site = Site::factory()->create();
        $language = Language::query()->whereKey($site->language_id)->firstOrFail();
        $media = Media::factory()->model($site)->create();
        $translation = $media->translations()->create(['language_id' => $language->getKey(), 'title' => 'Custom pageable image']);
        $plainRecord = Media::factory()->model($site)->create();
        $plainMedia = SpatieMedia::query()->whereKey($plainRecord->getKey())->firstOrFail();
        $media->unsetRelation('translations');
        $page->setRelation('image', null);
        $page->setRelation('media', new MediaCollection([$plainMedia, $media]));
    }

    $layout = new Layout(['containers' => ['header' => ['widgets' => [AuthMenuWidget::block()]]]]);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);
    (new FoundationThemeRuntimeManifestContributor)->contribute((new FrontendState)->withPage($page)->withLayout($layout), $manifest);

    expect($page->relationLoaded('image'))->toBe($withMedia)
        ->and($page->relationLoaded('socialImage'))->toBeFalse()
        ->and($page->relationLoaded('media'))->toBe($withMedia)
        ->and($manifest->modules['theme-foundation-runtime'] ?? false)->toBeTrue();

    if ($withMedia) {
        expect($media)->toBeInstanceOf(Media::class);
        expect($plainMedia::class)->toBe(SpatieMedia::class);
        assert($media instanceof Media);
        expect($media->relationLoaded('translations'))->toBeTrue()
            ->and($plainMedia->relationLoaded('translations'))->toBeFalse();
        $translations = $media->getRelation('translations');
        assert($translations instanceof EloquentCollection);
        $loaded = $translations->sole();
        expect($loaded->is($translation))->toBeTrue()
            ->and($loaded->relationLoaded('language'))->toBeTrue();
    }
})->with(['no media relations' => false, 'preloaded mixed media' => true]);

it('still hydrates supported Core page image and social-image translations', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->site($site)->create();
    $expected = [];
    foreach (['image' => MediaCollectionEnum::Image, 'socialImage' => MediaCollectionEnum::SocialImage] as $relation => $collection) {
        $media = Media::factory()->model($page)->create(['collection_name' => $collection->value]);
        $media->translations()->create(['language_id' => $site->language_id, 'title' => 'Translated page image']);
        $expected[$relation] = $media;
    }

    expect($page->relationLoaded('image'))->toBeFalse();
    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withPage($page),
        FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    );
    foreach ($expected as $relation => $media) {
        expect($page->relationLoaded($relation))->toBeTrue();
        $loadedMedia = $page->getRelation($relation);
        expect($loadedMedia)->toBeInstanceOf(Media::class);
        assert($loadedMedia instanceof Media);
        expect($loadedMedia->is($media))->toBeTrue()
            ->and($loadedMedia->relationLoaded('translations'))->toBeTrue();
        $translations = $loadedMedia->getRelation('translations');
        assert($translations instanceof EloquentCollection);
        expect($translations->sole()->relationLoaded('language'))->toBeTrue();
    }
});
