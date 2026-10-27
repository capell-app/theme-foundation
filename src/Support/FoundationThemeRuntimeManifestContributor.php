<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Override;

final class FoundationThemeRuntimeManifestContributor implements FrontendRuntimeManifestContributor
{
    #[Override]
    public function contribute(FrontendContextReader $context, FrontendRuntimeManifestData $manifest): void
    {
        $site = $context->site();
        $page = $context->page();

        if ($site instanceof Site) {
            $site->loadMissing([
                'image.translations.language',
                'logo.translations.language',
                'logoInverted.translations.language',
                'media',
                'siteDomain',
                'translation',
            ]);

            $site->getMedia('*')
                ->filter(static fn ($media): bool => $media instanceof Media)
                ->loadMissing('translations.language');
        }

        if ($page instanceof Pageable && $page instanceof Model) {
            $relations = array_values(array_filter(
                ['image', 'socialImage', 'media'],
                static fn (string $relation): bool => $page->relationLoaded($relation) || $page->isRelation($relation),
            ));
            $page->loadMissing($relations);

            $pageMedia = [];
            foreach ($relations as $relation) {
                $related = $page->getRelation($relation);
                if ($related instanceof Media) {
                    $pageMedia[] = $related;
                } elseif ($related instanceof Collection) {
                    foreach ($related as $media) {
                        if ($media instanceof Media) {
                            $pageMedia[] = $media;
                        }
                    }
                }
            }

            new EloquentCollection($pageMedia)->loadMissing('translations.language');
        }

        if ($this->usesAuthMenu($context->layout())) {
            $manifest->modules['theme-foundation-runtime'] = true;
        }
    }

    private function usesAuthMenu(?Layout $layout): bool
    {
        return $this->containsAuthMenu($layout?->containers);
    }

    private function containsAuthMenu(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        if (($value['type'] ?? null) === AuthMenuWidget::KEY
            && data_get($value, 'data.__capell.instance_id') === AuthMenuWidget::INSTANCE_ID) {
            return true;
        }

        return array_any($value, fn ($child): bool => $this->containsAuthMenu($child));
    }
}
