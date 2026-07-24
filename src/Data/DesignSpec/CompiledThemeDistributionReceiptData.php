<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use JsonException;
use Spatie\LaravelData\Data;

final class CompiledThemeDistributionReceiptData extends Data
{
    private const string DOMAIN = "capell.website-generator.artifact-receipt.v1\0";

    /** @param array<string, string> $bindings */
    public function __construct(
        public readonly int $schemaVersion,
        public readonly string $issuer,
        public readonly string $keyId,
        public readonly string $artifactType,
        public readonly string $subject,
        public readonly array $bindings,
        public readonly DateTimeImmutable $issuedAt,
        public readonly DateTimeImmutable $expiresAt,
        public readonly string $signature,
    ) {
        $keys = array_keys($bindings);
        $sortedKeys = $keys;
        sort($sortedKeys, SORT_STRING);
        $decodedSignature = base64_decode($signature, true);

        if ($schemaVersion !== 1
            || trim($issuer) === ''
            || trim($keyId) === ''
            || trim($artifactType) === ''
            || trim($subject) === ''
            || $bindings === []
            || $keys !== $sortedKeys
            || $expiresAt <= $issuedAt
            || $decodedSignature === false
            || strlen($decodedSignature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new InvalidArgumentException('design_spec.distribution.receipt_invalid');
        }

        foreach ($bindings as $name => $digest) {
            if (preg_match('/\A[a-z][a-z0-9_]*\z/', $name) !== 1
                || preg_match('/\A[a-f0-9]{64}\z/', $digest) !== 1) {
                throw new InvalidArgumentException('design_spec.distribution.receipt_binding_invalid');
            }
        }
    }

    /** @param array<string, string> $bindings */
    public static function signingMessageFor(
        string $issuer,
        string $keyId,
        string $artifactType,
        string $subject,
        array $bindings,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $expiresAt,
    ): string {
        try {
            $bytes = json_encode([
                'artifact_type' => $artifactType,
                'bindings' => $bindings,
                'expires_at' => $expiresAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                'issued_at' => $issuedAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                'issuer' => $issuer,
                'key_id' => $keyId,
                'schema_version' => 1,
                'subject' => $subject,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw new InvalidArgumentException('design_spec.distribution.receipt_encoding_failed');
        }

        return self::DOMAIN . $bytes;
    }

    /** @return array<string, mixed> */
    public function signedPayload(): array
    {
        return [
            'artifact_type' => $this->artifactType,
            'bindings' => $this->bindings,
            'expires_at' => $this->expiresAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'issued_at' => $this->issuedAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'issuer' => $this->issuer,
            'key_id' => $this->keyId,
            'schema_version' => $this->schemaVersion,
            'subject' => $this->subject,
        ];
    }

    public function signingMessage(): string
    {
        return self::DOMAIN . $this->canonicalPayloadBytes();
    }

    public function canonicalPayloadBytes(): string
    {
        try {
            return json_encode($this->signedPayload(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw new InvalidArgumentException('design_spec.distribution.receipt_encoding_failed');
        }
    }
}
