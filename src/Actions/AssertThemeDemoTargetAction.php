<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(ThemeDemoInstallData $data)
 */
final class AssertThemeDemoTargetAction
{
    use AsFake;
    use AsObject;

    public function handle(ThemeDemoInstallData $data): void
    {
        if ($data->siteNames === [] && Site::query()->exists()) {
            throw new InvalidArgumentException(__('capell-theme-foundation::demo.target_required'));
        }
    }
}
