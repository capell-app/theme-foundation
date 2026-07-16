<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Support\Security\PublicUrlSanitizer;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\FoundationTheme\Data\ThemeFormEmbedData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveThemeFormEmbedDataAction
{
    use AsFake;
    use AsObject;

    private const string COMPONENT_NAME = 'public-form';

    private const string PACKAGE_NAME = 'capell-app/form-builder';

    public function __construct(
        private readonly OptionalExtensionAvailability $availability,
    ) {}

    public function handle(
        int|string|null $handle,
        string $instanceId = 'theme-form',
        string $fallbackMessage = '',
        string $fallbackLabel = '',
        string $fallbackUrl = '',
    ): ThemeFormEmbedData {
        $handle = $this->normalizeHandle($handle);
        $instanceId = Str::slug($instanceId);
        $instanceId = $instanceId !== '' ? $instanceId : 'theme-form';

        return new ThemeFormEmbedData(
            handle: $handle,
            componentName: self::COMPONENT_NAME,
            instanceId: $instanceId,
            widgetData: array_filter([
                'instance_id' => $instanceId,
                'fallback_message' => trim($fallbackMessage),
                'fallback_label' => trim($fallbackLabel),
                'fallback_url' => PublicUrlSanitizer::sanitize($fallbackUrl),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            available: $handle !== null
                && $this->availability->livewireComponentAvailable(
                    self::PACKAGE_NAME,
                    self::COMPONENT_NAME,
                ),
        );
    }

    private function normalizeHandle(int|string|null $handle): int|string|null
    {
        if (is_int($handle)) {
            return $handle > 0 ? $handle : null;
        }

        if (! is_string($handle)) {
            return null;
        }

        $handle = trim($handle);

        return $handle !== '' ? $handle : null;
    }
}
