<?php

declare(strict_types=1);

use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component as LivewireComponent;
use PHPUnit\Framework\Assert;

it('attaches the translated heading scale helper to the live settings field', function (): void {
    $livewire = new class extends LivewireComponent implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $schema = Schema::make($livewire);
    /** @var array<int, Htmlable|string> $settingsComponents */
    $settingsComponents = FoundationThemeSettingsSchema::make($schema);
    $schema->components($settingsComponents);

    $schemaComponents = array_values(array_filter(
        $schema->getComponents(withHidden: true),
        static fn (mixed $component): bool => $component instanceof Component,
    ));
    $headingScale = collect(flattenFoundationThemeSettingsComponents($schemaComponents))
        ->first(static fn (Component $component): bool => method_exists($component, 'getName')
            && $component->getName() === 'heading_scale');

    Assert::assertInstanceOf(Component::class, $headingScale);

    expect(foundationThemeHelperText($headingScale))
        ->toBe('Controls the relative sizes and line heights of h1, h2 and h3 headings in the Foundation theme.');
});

/**
 * @param  array<int, Component>  $components
 * @return list<Component>
 */
function flattenFoundationThemeSettingsComponents(array $components): array
{
    $flattened = [];

    foreach ($components as $component) {
        $flattened[] = $component;

        foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
            $childComponents = array_values(array_filter(
                $childSchema->getComponents(withHidden: true),
                static fn (mixed $childComponent): bool => $childComponent instanceof Component,
            ));

            array_push($flattened, ...flattenFoundationThemeSettingsComponents($childComponents));
        }
    }

    return $flattened;
}

function foundationThemeHelperText(Component $component): string
{
    $belowContent = $component->getChildComponents('below_content');

    Assert::assertCount(1, $belowContent);
    Assert::assertInstanceOf(Text::class, $belowContent[0]);

    $content = $belowContent[0]->getContent();
    Assert::assertIsString($content);

    return $content;
}

it('offers an image loading control and build guidance without an editable minification switch', function (): void {
    $livewire = new class extends LivewireComponent implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $schema = Schema::make($livewire);
    $schema->components(array_map(static function (mixed $component): Htmlable|string {
        throw_unless($component instanceof Htmlable || is_string($component), RuntimeException::class, 'Expected a valid settings component.');

        return $component;
    }, FoundationThemeSettingsSchema::make($schema)));

    $components = flattenFoundationThemeSettingsComponents(array_values(array_filter($schema->getComponents(withHidden: true), static fn (mixed $component): bool => $component instanceof Component)));
    $fields = collect($components)->filter(static fn (Component $component): bool => $component instanceof Field);
    $names = $fields->map(static fn (Field $field): string => $field->getName())->all();
    expect($names)->toContain('enable_lazy_loading')->not->toContain('minify_assets');
    expect(collect($components)->filter(static fn (Component $component): bool => $component instanceof Text)->isNotEmpty())->toBeTrue();
});
