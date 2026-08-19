<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\View;

use Capell\Core\Support\Security\PublicUrlSanitizer;
use Capell\FoundationTheme\Data\AuthMenuRenderData;
use Capell\Frontend\Data\PublicPageRenderData;
use Illuminate\Contracts\View\View;

final readonly class FoundationThemePublicUrlViewComposer
{
    public function compose(View $view): void
    {
        if (str_ends_with($view->name(), 'widgets.auth-menu')) {
            $this->composeAuthMenu($view);

            return;
        }

        $this->composeButtonUrls($view);
    }

    private function composeAuthMenu(View $view): void
    {
        $data = $view->getData();
        $renderData = $data['renderData'] ?? null;
        $publicPageRenderData = $data['publicPageRenderData'] ?? null;

        $fragmentUrl = $renderData instanceof AuthMenuRenderData
            && ! $renderData->isLazyResponse
            && $publicPageRenderData instanceof PublicPageRenderData
            ? $publicPageRenderData->widgetInteractionLocator($renderData->instanceId)
            : null;

        $view->with('authMenuFragmentUrl', $fragmentUrl);
    }

    private function composeButtonUrls(View $view): void
    {
        $data = $view->getData();

        $view->with([
            'safePrimaryButtonUrl' => $this->safeUrl($this->url($data, 'primaryButtonUrl', 'primary_button_url')),
            'safeSecondaryButtonUrl' => $this->safeUrl($this->url($data, 'secondaryButtonUrl', 'secondary_button_url')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function url(array $data, string $viewKey, string $metaKey): mixed
    {
        if (array_key_exists($viewKey, $data)) {
            return $data[$viewKey];
        }

        $widget = $data['widget'] ?? null;

        return is_object($widget) && method_exists($widget, 'getMeta')
            ? $widget->getMeta($metaKey, '#')
            : '#';
    }

    private function safeUrl(mixed $url): string
    {
        return PublicUrlSanitizer::sanitize($url) ?? '#';
    }
}
