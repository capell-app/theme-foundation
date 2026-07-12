<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
use JsonException;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildThemeDemoFormsPayloadAction
{
    use AsAction;

    /**
     * @param  array<int, ThemeDemoPageDefinition>  $definitions
     *
     * @throws JsonException
     */
    public function handle(array $definitions): string
    {
        $forms = [];

        foreach ($definitions as $definition) {
            foreach ($definition->sections() as $section) {
                if (($section['type'] ?? null) !== 'form') {
                    continue;
                }

                $handle = $this->stringValue($section, 'form_handle');

                if ($handle === null) {
                    continue;
                }

                $fields = $section['fields'] ?? [];

                $forms[$handle] = [
                    'handle' => $handle,
                    'name' => $this->stringValue($section, 'form_name')
                        ?? $this->stringValue($section, 'heading')
                        ?? $definition->name,
                    'description' => $this->stringValue($section, 'form_description')
                        ?? $this->stringValue($section, 'summary'),
                    'fields' => is_array($fields) ? array_values($fields) : [],
                    'success_message' => $this->stringValue($section, 'success_message'),
                ];
            }
        }

        return json_encode(array_values($forms), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function stringValue(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
