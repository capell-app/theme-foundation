<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\AuthMenuInputData;
use Capell\FoundationTheme\Data\AuthMenuRenderData;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadBatchData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final readonly class ResolveAuthMenuPayloadsAction implements WidgetExtensionBatchPayloadResolver
{
    public function __construct(private Request $request) {}

    /** @return array<string, AuthMenuRenderData> */
    public function resolve(WidgetExtensionPayloadBatchData $batch): array
    {
        $isLazyResponse = $this->isLazyResponse();
        $user = $isLazyResponse ? $this->request->user() : null;
        $authenticatedUser = $user instanceof Authenticatable ? $user : null;
        $displayName = $authenticatedUser === null ? null : $this->displayName($authenticatedUser);
        $avatarInitials = $displayName === null ? null : $this->avatarInitials($displayName);
        $accountUrl = $authenticatedUser === null ? null : $this->accountUrl();
        $payloads = [];

        foreach ($batch->items as $item) {
            if (! $item->input instanceof AuthMenuInputData) {
                continue;
            }

            $payloads[$item->instanceId] = new AuthMenuRenderData(
                instanceId: $item->instanceId,
                loginUrl: $this->loginUrl(),
                isLazyResponse: $isLazyResponse,
                isAuthenticated: $authenticatedUser !== null,
                displayName: $displayName,
                avatarInitials: $avatarInitials,
                accountUrl: $accountUrl,
            );
        }

        return $payloads;
    }

    private function isLazyResponse(): bool
    {
        return $this->request->routeIs('capell-layout-builder.layout-widgets.*');
    }

    private function loginUrl(): string
    {
        return Route::has('login') ? route('login') : '/login';
    }

    private function accountUrl(): string
    {
        return Route::has('dashboard') ? route('dashboard') : '/dashboard';
    }

    private function displayName(Authenticatable $user): string
    {
        $name = data_get($user, 'name');
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $email = data_get($user, 'email');

        return is_string($email) && trim($email) !== ''
            ? trim($email)
            : __('capell-theme-foundation::generic.account');
    }

    private function avatarInitials(string $displayName): string
    {
        $parts = preg_split('/\s+/', trim($displayName)) ?: [];
        $initials = collect($parts)
            ->filter(static fn (mixed $part): bool => is_string($part) && $part !== '')
            ->take(2)
            ->map(static fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'A';
    }
}
