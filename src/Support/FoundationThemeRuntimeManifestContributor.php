<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Contracts\Pageable;
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
                'media.translations.language',
                'siteDomain',
                'translation',
            ]);
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
    }
}
