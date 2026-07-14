<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Interceptors\Themes;

use Capell\Core\Contracts\ModelInterceptors\ThemeInterceptorInterface;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Theme;

final class FoundationThemeInterceptor implements ThemeInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        if (! isset($data['meta'])) {
            $data['meta'] = [];
        }

        $data['meta'] = array_merge([
            'assets' => ['resources/css/capell/frontend.css'],
            'assets_path' => 'build',
            'header_divider' => false,
            'header_over_hero' => false,
            'header_border_color' => DefaultColorEnum::LightGray->getColor(),
            'header_shadow' => 'none',
            'footer_background_color' => '#edf2ee',
            'footer_divider' => true,
            'footer_border_color' => '#cfd9d3',
            'footer_color' => '#244c43',
            'footer_dark_background_color' => '#0b1716',
            'footer_dark_border_color' => '#31423c',
            'footer_dark_color' => '#dceae5',
            'footer_spacing' => 'default',
            'sticky_header' => true,
            'dark_mode_toggle' => true,
            'content_divider' => 'below_heading',
        ], $data['meta']);

        return $data;
    }

    public function afterCreated(Theme $theme, array $data): void {}
}
