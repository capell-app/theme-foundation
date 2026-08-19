<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

final class AuthMenuWidget
{
    public const string KEY = 'capell-app.auth-menu';

    public const string INSTANCE_ID = 'foundation-header-auth-menu';

    /**
     * @return array{type: string, data: array{__capell: array{instance_id: string, state_version: int}}}
     */
    public static function block(): array
    {
        return [
            'type' => self::KEY,
            'data' => [
                '__capell' => [
                    'instance_id' => self::INSTANCE_ID,
                    'state_version' => 1,
                ],
            ],
        ];
    }
}
