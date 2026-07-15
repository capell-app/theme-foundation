<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;

final class FoundationLayoutContainerPresentationData extends LayoutContainerThemePresentationData
{
    public function __construct(public readonly string $surfaceTone = 'default') {}

    public function classes(): array
    {
        return match ($this->surfaceTone) {
            'muted' => ['capell-container-surface-muted'],
            'contrast' => ['capell-container-surface-contrast'],
            default => [],
        };
    }
}
