<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class AuthMenuRenderData extends Data
{
    public function __construct(
        public string $instanceId,
        public string $loginUrl,
        public bool $isLazyResponse = false,
        public bool $isAuthenticated = false,
        public ?string $displayName = null,
        public ?string $avatarInitials = null,
        public ?string $accountUrl = null,
    ) {}
}
