<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use DateTimeImmutable;

interface CompiledThemeReceiptSigningAuthority
{
    public function issuer(): string;

    public function keyId(): string;

    public function releaseIdentity(): string;

    public function notBefore(): DateTimeImmutable;

    public function notAfter(): ?DateTimeImmutable;

    public function sign(string $message): string;

    public function verify(string $message, string $signature): bool;
}
