<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;

interface DesignSpecMigrationReader
{
    public function schemaVersion(): int;

    /** @param array<string, mixed> $specification */
    public function read(array $specification): DesignSpecData;
}
