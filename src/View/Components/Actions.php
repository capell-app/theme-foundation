<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components;

use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Frontend\Actions\Performance\RecordExtensionRenderContributionAction;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

final class Actions extends Component
{
    private const string PackageName = 'capell-app/theme-foundation';

    /** @var array<int, array<string, mixed>> */
    public array $resolvedActions;

    public function __construct(
        public string $align = 'start',
        public mixed $actions = '',
        public string $actionItemClass = '',
        public string $color = 'light',
        public string $buttonSize = 'lg',
        public string $buttonWeight = 'bold',
        public ?bool $buttonOutline = null,
        public string $buttonColor = 'primary',
    ) {
        $this->resolvedActions = $this->resolveActions();
    }

    public function render(): View
    {
        if ($this->hasPublicAction()) {
            RecordExtensionRenderContributionAction::run(
                packageName: self::PackageName,
                surface: 'frontend',
                contributionType: 'blade-component',
                contributionClass: self::class,
                elapsedMilliseconds: 0.0,
                frontendRenderBudgetMs: 5,
                cacheTags: ['theme-foundation', 'public-actions'],
                cacheable: false,
                sensitiveOutput: true,
                variesBy: ['site', 'locale', 'session'],
            );
        }

        return view('capell-theme-foundation::components.actions.index');
    }

    private function hasPublicAction(): bool
    {
        foreach ($this->resolvedActions as $resolvedAction) {
            if (($resolvedAction['kind'] ?? null) === 'public_action') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveActions(): array
    {
        if (! is_iterable($this->actions)) {
            return [];
        }

        $resolved = [];

        foreach ($this->actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $resolvedAction = $this->resolveAction($action);

            if ($resolvedAction !== null) {
                $resolved[] = $resolvedAction;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    private function resolveAction(array $action): ?array
    {
        $rawType = (string) ($action['type'] ?? '');

        if ($rawType === 'public_action') {
            return $this->resolvePublicAction($action);
        }

        if (ActionLinkEnum::tryFrom($rawType) === ActionLinkEnum::VideoPopup) {
            return $this->resolveVideoPopupAction($action);
        }

        $url = is_string($action['url'] ?? null) ? $action['url'] : '';
        $pageUrl = null;

        if (ActionLinkEnum::tryFrom($rawType) === ActionLinkEnum::Page) {
            $pageUrl = $this->resolvePageUrl($action);

            if (! $pageUrl instanceof PageUrl) {
                return null;
            }

            $url = $pageUrl->full_url;
        }

        if ($url === '') {
            return null;
        }

        return [
            ...$action,
            'kind' => 'link',
            'label' => $action['label'] ?? $pageUrl?->translation->link_text ?? '',
            'url' => $url,
            'wire_navigation' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    private function resolveVideoPopupAction(array $action): ?array
    {
        $videoUrl = is_string($action['video_url'] ?? null) ? $action['video_url'] : '';

        if ($videoUrl === '') {
            return null;
        }

        return [
            ...$action,
            'kind' => 'video_popup',
            'label' => $action['label'] ?? '',
            'video_url' => $videoUrl,
            'wire_navigation' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    private function resolvePublicAction(array $action): ?array
    {
        if (! Route::has('capell-public-actions.submit')) {
            return null;
        }

        $publicActionKey = is_string($action['public_action_key'] ?? null) ? $action['public_action_key'] : null;

        if ($publicActionKey === null || $publicActionKey === '') {
            return null;
        }

        return [
            ...$action,
            'kind' => 'public_action',
            'public_action_key' => $publicActionKey,
            'label' => $action['label'] ?? '',
            'payload' => array_filter([
                'area' => $action['access_gate_area'] ?? null,
                'requested_url' => url()->current(),
                'redirect' => $action['redirect'] ?? null,
                'source_type' => 'section_action',
                'source_id' => $action['source_id'] ?? null,
            ], static fn (mixed $payloadValue): bool => $payloadValue !== null && $payloadValue !== ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function resolvePageUrl(array $action): ?PageUrl
    {
        if (
            blank($action['site_id'] ?? null)
            || blank($action['pageable_type'] ?? null)
            || blank($action['pageable_id'] ?? null)
        ) {
            return null;
        }

        $site = Frontend::site();
        $language = Frontend::language();

        if (! $site instanceof Site || ! $language instanceof Language) {
            return null;
        }

        $targetSite = $action['site_id'] === $site->id
            ? $site
            : SiteLoader::getSites()->firstWhere('id', $action['site_id']);

        if (! $targetSite instanceof Site) {
            return null;
        }

        return PageLoader::getUrlById(
            pageType: (string) $action['pageable_type'],
            pageId: (int) $action['pageable_id'],
            site: $targetSite,
            language: $language,
        );
    }
}
