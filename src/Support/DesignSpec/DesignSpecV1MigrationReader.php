<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\DesignSpec;

use Capell\FoundationTheme\Actions\DesignSpec\ValidateDesignSpecAction;
use Capell\FoundationTheme\Contracts\DesignSpecMigrationReader;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;

final readonly class DesignSpecV1MigrationReader implements DesignSpecMigrationReader
{
    public function schemaVersion(): int
    {
        return DesignSpecConstraints::SCHEMA_VERSION;
    }

    public function read(array $specification): DesignSpecData
    {
        return ValidateDesignSpecAction::run($specification);
    }
}
