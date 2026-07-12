<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\LayoutBuilder\Models\Widget;

/**
 * @return array{meta: array<string, mixed>, widgets: array<int, array<string, mixed>>}
 */
function foundationThemeSetupMainContainer(Layout $layout): array
{
    $containers = $layout->containers;
    $main = is_array($containers) ? ($containers['main'] ?? null) : null;
    $meta = is_array($main) && is_array($main['meta'] ?? null) ? $main['meta'] : null;
    $widgets = is_array($main) && is_array($main['widgets'] ?? null) ? $main['widgets'] : null;

    throw_if(! is_array($meta) || ! is_array($widgets), RuntimeException::class, 'Expected the foundation home layout to have a main container.');

    return ['meta' => $meta, 'widgets' => $widgets];
}

it('installs Foundation theme layout defaults without owning the home hero', function (): void {
    $homeLayout = resolve(LayoutCreator::class)->createHomeLayout();
    $homeLayout->update(['containers' => []]);
    $homeLayout->refresh();

    expect($homeLayout->containers)->not->toHaveKey('hero')
        ->and($homeLayout->widgets)->toBe([])
        ->and(Layout::query()->where('key', LayoutEnum::Results->value)->exists())->toBeFalse();

    test()->artisan('capell:theme-foundation-setup')->assertSuccessful();

    $homeLayout->refresh();
    $mainContainer = foundationThemeSetupMainContainer($homeLayout);

    expect($homeLayout->containers)->not->toHaveKey('hero')
        ->and($homeLayout->containers)->toHaveKey('main')
        ->and($mainContainer['meta']['colspan'] ?? null)->toBe(12)
        ->and($mainContainer['widgets'])->toBe([
            ['widget_key' => 'page-content'],
        ])
        ->and($homeLayout->widgets)->toBe(['page-content'])
        ->and(Layout::query()->where('key', LayoutEnum::Results->value)->exists())->toBeFalse();
});

it('keeps home page content defaults stable on repeated setup', function (): void {
    resolve(LayoutCreator::class)
        ->createHomeLayout()
        ->update([
            'containers' => [
                'hero' => [
                    'widgets' => [
                        ['widget_key' => 'hero'],
                    ],
                ],
            ],
        ]);

    test()->artisan('capell:theme-foundation-setup')->assertSuccessful();
    test()->artisan('capell:theme-foundation-setup')->assertSuccessful();

    $homeLayout = Layout::query()->where('key', LayoutEnum::Home->value)->firstOrFail();
    $mainContainer = foundationThemeSetupMainContainer($homeLayout);
    $containers = $homeLayout->containers ?? [];

    expect($containers)->toHaveKey('hero')
        ->and($containers)->toHaveKey('main')
        ->and(array_keys($containers))->toBe(['hero', 'main'])
        ->and($mainContainer['meta']['colspan'] ?? null)->toBe(12)
        ->and($mainContainer['widgets'])->toBe([
            ['widget_key' => 'page-content'],
        ])
        ->and($homeLayout->widgets)->toBe(['hero', 'page-content']);
});

it('repairs custom page layouts that are missing page content without inserting demo widgets', function (): void {
    Widget::factory()->create(['key' => 'hero']);
    Widget::factory()->create(['key' => 'custom-feature']);

    Layout::factory()->create([
        'key' => 'landing-page',
        'group' => 'default',
        'containers' => [
            'hero' => [
                'widgets' => [
                    ['widget_key' => 'hero'],
                ],
            ],
            'main' => [
                'meta' => ['colspan' => 12],
                'widgets' => [
                    ['widget_key' => 'custom-feature'],
                ],
            ],
        ],
    ]);

    test()->artisan('capell:theme-foundation-setup')->assertSuccessful();

    $layout = Layout::query()->where('key', 'landing-page')->firstOrFail();
    $containers = $layout->containers ?? [];

    expect(array_keys($containers))->toBe(['hero', 'main'])
        ->and(data_get($containers, 'main.widgets'))->toBe([
            ['widget_key' => 'page-content'],
            ['widget_key' => 'custom-feature'],
        ])
        ->and($layout->widgets)->toBe(['hero', 'page-content', 'custom-feature'])
        ->and(Widget::query()->where('key', 'kitchen-sink-rich-text')->exists())->toBeFalse();
});
