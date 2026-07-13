<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components;

use Capell\FoundationTheme\Actions\ResolveThemeFormEmbedDataAction;
use Capell\FoundationTheme\Data\ThemeFormEmbedData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class ThemeFormEmbed extends Component
{
    public readonly ThemeFormEmbedData $formEmbed;

    public function __construct(
        int|string|null $handle = null,
        string $instanceId = 'theme-form',
        string $fallbackMessage = '',
        string $fallbackLabel = '',
        string $fallbackUrl = '',
    ) {
        $this->formEmbed = app(ResolveThemeFormEmbedDataAction::class)->handle(
            handle: $handle,
            instanceId: $instanceId,
            fallbackMessage: $fallbackMessage,
            fallbackLabel: $fallbackLabel,
            fallbackUrl: $fallbackUrl,
        );
    }

    public function render(): View
    {
        return view('capell-theme-foundation::forms.embed');
    }
}
