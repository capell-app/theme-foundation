<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Tests\Fixtures\RegistersOptionalThemeSectionAvailabilityTestProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;

/*
|--------------------------------------------------------------------------
| RegistersOptionalThemeSectionAvailability trait test
|--------------------------------------------------------------------------
|
| Seventeen shipped theme providers use this trait to hide sections whose
| owning package is not installed. Until now it had no test of its own: the
| gate was only exercised indirectly through each consuming theme, so
| theme-foundation carried the behaviour without pinning it down. This
| exercises the trait directly, against a throwaway ServiceProvider and a
| fixture view, so the composer contract holds independently of any one
| theme's usage.
|
*/

function foundationOptionalSectionWidget(string $type): object
{
    return new class($type)
    {
        public function __construct(private readonly string $type) {}

        public function getAttribute(string $key): mixed
        {
            return $key === 'meta' ? ['type' => $this->type] : null;
        }
    };
}

/**
 * @param  array<string, mixed>  $data
 */
function foundationOptionalSectionRender(array $data): string
{
    /** @var ViewFactory $factory */
    $factory = resolve(ViewFactory::class);

    return $factory->make(
        'capell-optional-section-fixture::optional-section-availability',
        $data,
    )->render();
}

beforeEach(function (): void {
    /** @var ViewFactory $factory */
    $factory = resolve(ViewFactory::class);
    $factory->addNamespace(
        'capell-optional-section-fixture',
        __DIR__ . '/../Fixtures/views',
    );
});

it('hides an optional section when its owning package is not available', function (): void {
    CapellCore::shouldReceive('isPackageAvailable')
        ->once()
        ->with('capell-app/newsletter')
        ->andReturnFalse();

    (new RegistersOptionalThemeSectionAvailabilityTestProvider($this->app))
        ->registerAvailability(
            'capell-optional-section-fixture::optional-section-availability',
            ['newsletter' => 'capell-app/newsletter'],
        );

    $rendered = foundationOptionalSectionRender(['widget' => foundationOptionalSectionWidget('newsletter')]);

    expect(trim($rendered))->toBe('unavailable');
});

it('shows a section that no optional package gates', function (): void {
    (new RegistersOptionalThemeSectionAvailabilityTestProvider($this->app))
        ->registerAvailability(
            'capell-optional-section-fixture::optional-section-availability',
            ['newsletter' => 'capell-app/newsletter'],
        );

    $rendered = foundationOptionalSectionRender(['widget' => foundationOptionalSectionWidget('hero')]);

    expect(trim($rendered))->toBe('available');
});

it('treats a view with no widget in scope as available', function (): void {
    (new RegistersOptionalThemeSectionAvailabilityTestProvider($this->app))
        ->registerAvailability(
            'capell-optional-section-fixture::optional-section-availability',
            ['newsletter' => 'capell-app/newsletter'],
        );

    $rendered = foundationOptionalSectionRender([]);

    expect(trim($rendered))->toBe('available');
});
