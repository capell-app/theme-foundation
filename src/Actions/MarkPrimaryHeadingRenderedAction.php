<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Frontend\Contracts\FrontendContextReader;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class MarkPrimaryHeadingRenderedAction
{
    use AsFake;
    use AsObject;

    public function handle(): void
    {
        if (! app()->bound(FrontendContextReader::class)) {
            return;
        }

        resolve(FrontendContextReader::class)->setFrontendData('has_primary_heading', true);
    }
}
