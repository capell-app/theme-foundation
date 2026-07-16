<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Media;
use Capell\FoundationTheme\Data\PageContentRenderData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildPageContentRenderDataAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<int, string>  $pageContents
     */
    public function handle(?Pageable $page, array $pageContents, bool $showPageTitle): PageContentRenderData
    {
        $translation = $page instanceof Model ? $this->loadedRelation($page, 'translation') : null;
        $blueprint = $page instanceof Model ? $this->loadedRelation($page, 'blueprint') : null;
        $image = $page instanceof Model ? $this->loadedRelation($page, 'image') : null;
        $imageTranslations = $image instanceof Media && $image->relationLoaded('translations')
            ? $image->getRelation('translations')
            : null;
        $imageTranslation = $imageTranslations instanceof Collection
            ? $imageTranslations->firstWhere('language_id', data_get($translation, 'language_id'))
            : null;
        $imageAlt = data_get($imageTranslation, 'meta.alt');
        $content = data_get($translation, 'content');
        $title = data_get($translation, 'title');
        $displayTitle = data_get($translation, 'meta.hero_title');
        $contentStructure = data_get($blueprint, 'content_structure');
        $contentStructure = $contentStructure instanceof ContentStructure
            ? $contentStructure
            : ContentStructure::Html;

        return new PageContentRenderData(
            image: $image instanceof Media ? $image : null,
            imageAlt: is_string($imageAlt) && $imageAlt !== '' ? $imageAlt : null,
            content: is_string($content) ? $content : null,
            contentStructure: $contentStructure,
            hasContent: in_array('content', $pageContents, true) && is_string($content) && $content !== '',
            hasTitle: in_array('title', $pageContents, true) && $showPageTitle,
            title: is_string($displayTitle) && $displayTitle !== ''
                ? $displayTitle
                : (is_string($title) ? $title : null),
        );
    }

    private function loadedRelation(Model $model, string $relation): mixed
    {
        if (! $model->relationLoaded($relation)) {
            return null;
        }

        return $model->getRelation($relation);
    }
}
