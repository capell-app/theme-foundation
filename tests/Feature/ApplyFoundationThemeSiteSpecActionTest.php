<?php

declare(strict_types=1);

use Capell\Core\Contracts\SiteSpec\SiteSpecApplier;
use Capell\Core\Data\SiteSpec\CapellSiteSpecData;
use Capell\FoundationTheme\Actions\SiteSpec\ApplyFoundationThemeSiteSpecAction;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;

beforeEach(function (): void {
    // Every property from the class defaults; spatie's fake reads missing ones from storage.
    FoundationThemeSettings::fake(array_intersect_key(
        get_class_vars(FoundationThemeSettings::class),
        array_flip(array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            new ReflectionClass(FoundationThemeSettings::class)->getProperties(ReflectionProperty::IS_PUBLIC),
        )),
    ));
});

function foundationSiteSpec(string $themeKey, ?string $primary): CapellSiteSpecData
{
    return CapellSiteSpecData::from([
        'site' => ['name' => 'Harbour Books'],
        'theme' => ['key' => $themeKey, 'colors' => ['primary' => $primary]],
        'pages' => [],
    ]);
}

it('is registered as a SiteSpec applier', function (): void {
    $appliers = collect(app()->tagged(SiteSpecApplier::TAG))->map(fn (SiteSpecApplier $applier): string => $applier::class);

    expect($appliers)->toContain(ApplyFoundationThemeSiteSpecAction::class);
});

it('writes a readable SiteSpec primary colour into the Foundation primary action colour', function (): void {
    expect(ApplyFoundationThemeSiteSpecAction::run(foundationSiteSpec('default', '#1D4ED8')))->toBeTrue()
        ->and(resolve(FoundationThemeSettings::class)->primary_action_color)->toBe('#1d4ed8');
});

it('keeps the current colour when white text would be unreadable, the colour is invalid or the theme is not Foundation', function (?string $primary, string $themeKey): void {
    $before = resolve(FoundationThemeSettings::class)->primary_action_color;

    expect(ApplyFoundationThemeSiteSpecAction::run(foundationSiteSpec($themeKey, $primary)))->toBeFalse()
        ->and(resolve(FoundationThemeSettings::class)->primary_action_color)->toBe($before);
})->with([
    'pale yellow fails white button text' => ['#fde68a', 'default'],
    'too light as text on the surface' => ['#3b82f6', 'default'],
    'not a hex colour' => ['red; background: url(x)', 'default'],
    'missing' => [null, 'default'],
    'another theme' => ['#1d4ed8', 'editorial'],
]);
