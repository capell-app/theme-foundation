<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Lorisleiva\Actions\Concerns\AsObject;

final class MarkPrimaryHeadingRenderedAction
{
    use AsObject;

    private const string FRONTEND_CONTEXT_SERVICE = 'capell.frontend.context';

    public function handle(): void
    {
        if (! app()->bound(self::FRONTEND_CONTEXT_SERVICE)) {
            return;
        }

        $frontend = resolve(self::FRONTEND_CONTEXT_SERVICE);

        if (is_object($frontend) && method_exists($frontend, 'setFrontendData')) {
            $frontend->setFrontendData('has_primary_heading', true);
        }
    }
}
