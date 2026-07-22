<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\DesignSpec\ReadDesignSpecAction;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecMigrationReaderRegistry;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecV1MigrationReader;

function designSpecReaderFixture(string $name): string
{
    $contents = file_get_contents(dirname(__DIR__, 2) . "/Fixtures/design-spec/{$name}.json");

    return $contents === false ? throw new RuntimeException('Unable to load DesignSpec reader fixture.') : $contents;
}

it('reads the released v1 contract through its explicit registered reader', function (): void {
    $registry = DesignSpecMigrationReaderRegistry::released();
    $specification = runBoundAction(
        ReadDesignSpecAction::class,
        new ReadDesignSpecAction($registry),
        designSpecReaderFixture('v1-canonical'),
    );

    expect($registry->supportedSchemaVersions())->toBe([1])
        ->and($specification)->toBeInstanceOf(DesignSpecData::class)
        ->and($specification->schemaVersion)->toBe(1)
        ->and($specification->template)->toBe('foundation');
});

it('fails closed for legacy, future, missing, and non-integer schema versions', function (mixed $schemaVersion): void {
    $payload = json_decode(designSpecReaderFixture('v1-canonical'), true, 64, JSON_THROW_ON_ERROR);

    if ($schemaVersion === null) {
        unset($payload['schemaVersion']);
    } else {
        $payload['schemaVersion'] = $schemaVersion;
    }

    ReadDesignSpecAction::run($payload);
})->with([
    'legacy' => 0,
    'future' => 2,
    'string' => '1',
    'missing' => null,
])->throws(InvalidArgumentException::class);

it('does not mutate caller-owned input while reading', function (): void {
    $payload = json_decode(designSpecReaderFixture('v1-canonical'), true, 64, JSON_THROW_ON_ERROR);
    $before = serialize($payload);

    ReadDesignSpecAction::run($payload);

    expect(serialize($payload))->toBe($before);
});

it('rejects duplicate migration readers rather than selecting by registration order', function (): void {
    new DesignSpecMigrationReaderRegistry([
        new DesignSpecV1MigrationReader,
        new DesignSpecV1MigrationReader,
    ]);
})->throws(InvalidArgumentException::class, 'design_spec.reader_registry.invalid');
