<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Media;
use Capell\FoundationTheme\Data\PageContentRenderData;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildPageContentRenderDataAction
{
    use AsObject;

    /**
     * @param  array<int, string>  $pageContents
     */
    public function handle(?Pageable $page, array $pageContents, bool $showPageTitle): PageContentRenderData
    {
        $translation = $page instanceof Model ? $this->loadedRelation($page, 'translation') : null;
        $type = $page instanceof Model ? $this->loadedRelation($page, 'type') : null;
        $image = $page instanceof Model ? $this->loadedRelation($page, 'image') : null;
        $content = data_get($translation, 'content');
        $title = data_get($translation, 'title');
        $displayTitle = data_get($translation, 'meta.hero_title');
        $contentStructure = data_get($type, 'content_structure');
        $contentStructure = $contentStructure instanceof ContentStructure
            ? $contentStructure
            : ContentStructure::Html;

        return new PageContentRenderData(
            image: $image instanceof Media ? $image : null,
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
