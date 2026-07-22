<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\CanonicalDesignSpecData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecAssetData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use JsonException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/** @method static CanonicalDesignSpecData run(array<string, mixed>|string|DesignSpecData $specification) */
final class CanonicalizeDesignSpecAction
{
    use AsFake;
    use AsObject;

    /** @param array<string, mixed>|string|DesignSpecData $specification */
    public function handle(array|string|DesignSpecData $specification): CanonicalDesignSpecData
    {
        $validated = $specification instanceof DesignSpecData
            ? ReadDesignSpecAction::run($this->schemaArray($specification))
            : ReadDesignSpecAction::run($specification);
        $canonical = $this->schemaArray($validated);

        try {
            $bytes = json_encode(
                $this->sortObjects($canonical),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException) {
            throw new RuntimeException('design_spec.canonicalization.failed');
        }

        return new CanonicalDesignSpecData(
            schemaVersion: $validated->schemaVersion,
            bytes: $bytes,
            sha256: hash('sha256', $bytes),
            specification: $validated,
        );
    }

    private function sortObjects(mixed $value): mixed
    {
        if (is_object($value)) {
            return $this->sortObjects(get_object_vars($value));
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->sortObjects(...), $value);
        }

        ksort($value, SORT_STRING);

        return array_map($this->sortObjects(...), $value);
    }

    /** @return array<string, mixed> */
    private function schemaArray(DesignSpecData $specification): array
    {
        $schema = get_object_vars($specification);
        $schema['assets'] = array_map(
            static fn (DesignSpecAssetData $asset): array => ['id' => $asset->id],
            $specification->assets,
        );

        return $schema;
    }
}
