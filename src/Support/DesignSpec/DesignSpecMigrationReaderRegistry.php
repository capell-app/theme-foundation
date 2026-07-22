<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\DesignSpec;

use Capell\FoundationTheme\Contracts\DesignSpecMigrationReader;
use InvalidArgumentException;

final readonly class DesignSpecMigrationReaderRegistry
{
    /** @var array<int, DesignSpecMigrationReader> */
    private array $readers;

    /** @param list<DesignSpecMigrationReader> $readers */
    public function __construct(array $readers)
    {
        $registered = [];

        foreach ($readers as $reader) {
            $version = $reader->schemaVersion();

            if ($version < 1 || array_key_exists($version, $registered)) {
                throw new InvalidArgumentException('design_spec.reader_registry.invalid');
            }

            $registered[$version] = $reader;
        }

        ksort($registered, SORT_NUMERIC);
        $this->readers = $registered;
    }

    public static function released(): self
    {
        return new self([new DesignSpecV1MigrationReader]);
    }

    public function readerFor(int $schemaVersion): DesignSpecMigrationReader
    {
        return $this->readers[$schemaVersion]
            ?? throw new InvalidArgumentException('design_spec.schema_version.unsupported');
    }

    /** @return list<int> */
    public function supportedSchemaVersions(): array
    {
        return array_keys($this->readers);
    }
}
