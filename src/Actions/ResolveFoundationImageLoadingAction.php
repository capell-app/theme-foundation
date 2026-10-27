<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class ResolveFoundationImageLoadingAction
{
    use AsFake;
    use AsObject;

    public function handle(): string
    {
        try {
            return resolve(FoundationThemeSettings::class)->enable_lazy_loading ? 'lazy' : 'eager';
        } catch (Throwable) {
            return 'lazy';
        }
    }
}
