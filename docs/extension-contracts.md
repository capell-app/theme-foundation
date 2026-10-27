# Worked extension examples

These developer-facing recipes are kept beside the package contract. Replace the example values with the site-specific records and data objects used by the calling workflow.

<!-- example: contract Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority -->

```php
<?php
declare(strict_types=1);
final class ExampleCompiledThemeReceiptSigningAuthorityImplementation implements \Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority
{
    public function issuer(): string
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function keyId(): string
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function releaseIdentity(): string
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function notBefore(): \DateTimeImmutable
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function notAfter(): ?\DateTimeImmutable
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function sign(string $message): string
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function verify(string $message, string $signature): bool
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority::class, ExampleCompiledThemeReceiptSigningAuthorityImplementation::class);
```

<!-- example: contract Capell\FoundationTheme\Contracts\DesignSpecMigrationReader -->

```php
<?php
declare(strict_types=1);
final class ExampleDesignSpecMigrationReaderImplementation implements \Capell\FoundationTheme\Contracts\DesignSpecMigrationReader
{
    public function schemaVersion(): int
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    /** @param array<string, mixed> $specification */
    public function read(array $specification): \Capell\FoundationTheme\Data\DesignSpec\DesignSpecData
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\DesignSpecMigrationReader::class, ExampleDesignSpecMigrationReaderImplementation::class);
```

<!-- example: contract Capell\FoundationTheme\Contracts\InstallsThemeDemo -->

```php
<?php
declare(strict_types=1);
final class ExampleInstallsThemeDemoImplementation implements \Capell\FoundationTheme\Contracts\InstallsThemeDemo
{
    public function handle(\Capell\FoundationTheme\Data\ThemeDemoInstallData $data): int
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\InstallsThemeDemo::class, ExampleInstallsThemeDemoImplementation::class);
```

<!-- example: contract Capell\FoundationTheme\Contracts\OptionalExtensionAvailability -->

```php
<?php
declare(strict_types=1);
final class ExampleOptionalExtensionAvailabilityImplementation implements \Capell\FoundationTheme\Contracts\OptionalExtensionAvailability
{
    /**
     * @param  list<class-string>  $requiredClasses
     */
    public function packageAvailable(string $packageName, array $requiredClasses = []): bool
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
    public function livewireComponentAvailable(string $packageName, string $componentName): bool
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\OptionalExtensionAvailability::class, ExampleOptionalExtensionAvailabilityImplementation::class);
```

<!-- example: contract Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent -->

```php
<?php
declare(strict_types=1);
final class ExampleProvidesThemeDemoContentImplementation implements \Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent::class, ExampleProvidesThemeDemoContentImplementation::class);
```

Resolvers can also implement `PaginatesResultsListing` to support opt-in GET
pagination without changing existing resolver implementations. See
[Results listings](results-listings.md) for the request and response contracts,
page limits and loader behaviour. Bind the implementation through the existing
`ResultsListingResolver` contract.

<!-- example: contract Capell\FoundationTheme\Contracts\ResultsListingResolver -->

```php
<?php
declare(strict_types=1);
final class ExampleResultsListingResolverImplementation implements \Capell\FoundationTheme\Contracts\ResultsListingResolver
{
    public function handle(\Capell\Core\Models\Site $site, \Capell\Core\Models\Language $language, \Capell\Core\Models\Page $page): \Capell\FoundationTheme\Data\ResultsListingData
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\ResultsListingResolver::class, ExampleResultsListingResolverImplementation::class);
```

<!-- example: action demo -->

```php
<?php
declare(strict_types=1);
$inputs = []; // Supply the arguments required by the action handle() method.
resolve(\Capell\FoundationTheme\Actions\InstallFoundationThemeDemoAction::class)->handle(...$inputs);
```

<!-- example: action setup -->

```php
<?php
declare(strict_types=1);
$inputs = []; // Supply the arguments required by the action handle() method.
resolve(\Capell\FoundationTheme\Actions\SetupFoundationThemePackageAction::class)->handle(...$inputs);
```

## PaginatesResultsListing adapter

This adapter delegates to the shipped implementation so the existing eligibility,
ownership or pagination bounds remain in force. Resolve the backend by its concrete
class to avoid resolving the replacement contract recursively.

Register through `ResultsListingResolver`: the results-section consumer checks the
resolved object for `PaginatesResultsListing` before calling `handlePaginated()`.
The inherited `handle()` remains available for widgets without pagination.

<!-- example: contract Capell\FoundationTheme\Contracts\PaginatesResultsListing -->

```php
<?php
declare(strict_types=1);

final class ExamplePaginatesResultsListingAdapter implements \Capell\FoundationTheme\Contracts\PaginatesResultsListing
{
    public function __construct(private readonly \Capell\FoundationTheme\Actions\ResolveResultsListingAction $backend) {}

    #[\Override]
    public function handle(\Capell\Core\Models\Site $site, \Capell\Core\Models\Language $language, \Capell\Core\Models\Page $page): \Capell\FoundationTheme\Data\ResultsListingData
    {
        return $this->backend->handle($site, $language, $page);
    }

    #[\Override]
    public function handlePaginated(\Capell\Core\Models\Site $site, \Capell\Core\Models\Language $language, \Capell\Core\Models\Page $page, \Capell\FoundationTheme\Data\ResultsPaginationRequestData $pagination): \Capell\FoundationTheme\Data\ResultsListingData
    {
        return $this->backend->handlePaginated($site, $language, $page, $pagination);
    }
}

app()->bind(\Capell\FoundationTheme\Contracts\ResultsListingResolver::class, ExamplePaginatesResultsListingAdapter::class);
```

## Section variant scope

The section variant picker uses the effective theme of each tracked page or layout containing the widget. A layout theme overrides the site theme. Shared widgets offer the intersection of supported variants, plus Default for the base section. Layout references and page assets cover all registered Pageable types, including Article and Event consumers, through Core’s public page-type registry. Widgets without tracked uses explicitly show options from the default admin theme. Clearing the picker saves Default. Unsupported saved variants remain stored during unrelated edits until replaced or explicitly cleared; the public view resolver retains its existing base-section fallback.
