<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures\DesignSpec;

use Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority;
use DateTimeImmutable;

final readonly class Ed25519CompiledThemeReceiptSigningAuthority implements CompiledThemeReceiptSigningAuthority
{
    private string $secretKey;

    public function __construct()
    {
        $keyPair = sodium_crypto_sign_seed_keypair(str_repeat("\x19", SODIUM_CRYPTO_SIGN_SEEDBYTES));
        $this->secretKey = sodium_crypto_sign_secretkey($keyPair);
    }

    public function issuer(): string
    {
        return 'theme-foundation-tests';
    }

    public function keyId(): string
    {
        return 'theme-foundation-test-key';
    }

    public function releaseIdentity(): string
    {
        return 'capell-app/theme-foundation@1.0.0';
    }

    public function notBefore(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-01T00:00:00Z');
    }

    public function notAfter(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-01T00:00:00Z');
    }

    public function sign(string $message): string
    {
        return base64_encode(sodium_crypto_sign_detached($message, $this->secretKey));
    }

    public function verify(string $message, string $signature): bool
    {
        $decoded = base64_decode($signature, true);

        return is_string($decoded)
            && $decoded !== ''
            && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES
            && sodium_crypto_sign_verify_detached(
                $decoded,
                $message,
                sodium_crypto_sign_publickey_from_secretkey($this->secretKey),
            );
    }
}
