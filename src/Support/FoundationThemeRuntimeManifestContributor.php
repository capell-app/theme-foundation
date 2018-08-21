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
use Illuminate\Database\Eloquent\Model;

final class FoundationThemeRuntimeManifestContributor implements FrontendRuntimeManifestContributor
{
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
            $relations = [
                'image.translations.language',
                'media.translations.language',
            ];

            if (method_exists($page, 'socialImage')) {
                $relations[] = 'socialImage.translations.language';
            }

            $page->loadMissing($relations);
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

        foreach ($value as $child) {
            if ($this->containsAuthMenu($child)) {
                return true;
            }
        }

        return false;
    }
}
