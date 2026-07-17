<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveFoundationSectionAnchorAction
{
    use AsFake, AsObject;

    public function handle(
        string $sectionType,
        ?string $configuredAnchor,
        string $containerKey,
        string $widgetKey,
        int $widgetIndex,
        int $occurrence,
    ): string {
        $configuredAnchor = Str::slug((string) $configuredAnchor);

        if ($configuredAnchor !== '') {
            if ($occurrence <= 1) {
                return $configuredAnchor;
            }

            return implode('-', array_filter([
                $configuredAnchor,
                Str::slug($widgetKey),
                (string) $occurrence,
            ]));
        }

        $parts = array_filter([
            Str::slug($sectionType),
            Str::slug($containerKey),
            Str::slug($widgetKey),
            (string) max(0, $widgetIndex),
            (string) max(1, $occurrence),
        ]);

        return implode('-', $parts) ?: 'section';
    }
}
