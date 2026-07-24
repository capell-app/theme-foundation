<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecMigrationReaderRegistry;
use InvalidArgumentException;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static DesignSpecData run(array<string, mixed>|string $specification) */
final class ReadDesignSpecAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly ?DesignSpecMigrationReaderRegistry $registry = null,
    ) {}

    /** @param array<string, mixed>|string $specification */
    public function handle(array|string $specification): DesignSpecData
    {
        $payload = $this->payload($specification);
        $schemaVersion = $payload['schemaVersion'] ?? null;

        if (! is_int($schemaVersion)) {
            throw new InvalidArgumentException('design_spec.schema_version.invalid');
        }

        return ($this->registry ?? DesignSpecMigrationReaderRegistry::released())
            ->readerFor($schemaVersion)
            ->read($payload);
    }

    /**
     * @param  array<string, mixed>|string  $specification
     * @return array<string, mixed>
     */
    private function payload(array|string $specification): array
    {
        if (is_array($specification)) {
            return $specification;
        }

        if (strlen($specification) > DesignSpecConstraints::MAX_DOCUMENT_BYTES) {
            throw new InvalidArgumentException('design_spec.document.too_large');
        }

        try {
            $payload = json_decode(
                $specification,
                true,
                DesignSpecConstraints::JSON_DECODE_DEPTH,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new InvalidArgumentException('design_spec.json.invalid');
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw new InvalidArgumentException('design_spec.root.invalid');
        }

        $object = [];
        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('design_spec.root.invalid');
            }

            $object[$key] = $value;
        }

        return $object;
    }
}
