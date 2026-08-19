<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament;

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\FoundationTheme\Support\AuthMenuWidget as AuthMenuWidgetDefinition;
use Filament\Forms\Components\Builder\Block;

final class AuthMenuWidget implements FilamentWidget
{
    public static function getWidgetName(): string
    {
        return AuthMenuWidgetDefinition::KEY;
    }

    public static function make(): Block
    {
        return Block::make(self::getWidgetName())
            ->label(__('capell-theme-foundation::generic.auth_menu'))
            ->schema([]);
    }
}
