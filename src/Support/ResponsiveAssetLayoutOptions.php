<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use JsonException;

class ResponsiveAssetLayoutOptions
{
    public function __construct(
        public readonly ResponsiveLayoutPattern $pattern,
        public readonly bool $hasGridOverrides,
        public readonly int $smColumns,
        public readonly int $mdColumns,
        public readonly int $lgColumns,
        public readonly int $xlColumns,
        public readonly int $gridRows,
        public readonly float $mobileSlides,
        public readonly float $smSlides,
        public readonly int $carouselRows,
        public readonly bool $highlightActive,
        public readonly bool $carouselArrows,
        public readonly bool $carouselPagination,
        public readonly bool $carouselLoop,
        public readonly bool $carouselRewind,
        public readonly bool $carouselDrag,
        public readonly bool $carouselTouch,
        public readonly bool $carouselAutoPlay,
        public readonly bool $carouselPauseOnHover,
        public readonly bool $carouselDisableOnInteraction,
        public readonly int $carouselAutoDelay,
        public readonly int $carouselSpeed,
        public readonly string $carouselAlign,
    ) {}

    public static function fromWidget(Widget $widget, int $total): self
    {
        $legacyColumns = (int) self::meta($widget, 'columns');
        $fallbackColumns = $legacyColumns > 0 ? $legacyColumns : max(1, min($total, 4));

        return new self(
            pattern: self::responsiveLayoutPattern($widget),
            hasGridOverrides: self::hasAnyMeta($widget, [
                'responsive_grid_sm_columns',
                'responsive_grid_md_columns',
                'responsive_grid_lg_columns',
                'responsive_grid_xl_columns',
                'responsive_grid_rows',
            ]),
            smColumns: self::intMeta($widget, 'responsive_grid_sm_columns', min(2, $fallbackColumns), 1, 12),
            mdColumns: self::intMeta($widget, 'responsive_grid_md_columns', $fallbackColumns, 1, 12),
            lgColumns: self::intMeta($widget, 'responsive_grid_lg_columns', $fallbackColumns, 1, 12),
            xlColumns: self::intMeta($widget, 'responsive_grid_xl_columns', $fallbackColumns, 1, 12),
            gridRows: self::intMeta($widget, 'responsive_grid_rows', 0, 0, 12),
            mobileSlides: self::floatMeta($widget, 'responsive_carousel_mobile_slides', 1.1, 1.0, 6.0),
            smSlides: self::floatMeta($widget, 'responsive_carousel_sm_slides', 2.0, 1.0, 6.0),
            carouselRows: self::intMeta($widget, 'responsive_carousel_rows', 1, 1, 4),
            highlightActive: (bool) self::meta($widget, 'responsive_carousel_highlight_active', false),
            carouselArrows: (bool) self::meta($widget, 'carousel_arrows', false),
            carouselPagination: (bool) self::meta($widget, 'carousel_pagination', true),
            carouselLoop: (bool) self::meta($widget, 'carousel_loop', false),
            carouselRewind: (bool) self::meta($widget, 'carousel_rewind', true),
            carouselDrag: (bool) self::meta($widget, 'carousel_drag', true),
            carouselTouch: (bool) self::meta($widget, 'carousel_touch', true),
            carouselAutoPlay: (bool) self::meta($widget, 'carousel_auto_play', false),
            carouselPauseOnHover: (bool) self::meta($widget, 'carousel_pause_on_hover', true),
            carouselDisableOnInteraction: (bool) self::meta($widget, 'carousel_disable_on_interaction', true),
            carouselAutoDelay: self::intMeta($widget, 'carousel_auto_delay', 5000, 100, 60000),
            carouselSpeed: self::intMeta($widget, 'carousel_speed', 300, 0, 10000),
            carouselAlign: (string) self::meta($widget, 'carousel_align', 'start'),
        );
    }

    public function shouldUseResponsiveGrid(): bool
    {
        return $this->hasGridOverrides || $this->pattern !== ResponsiveLayoutPattern::Grid;
    }

    public function gridColumnsStyle(string $baseStyle = ''): string
    {
        return trim(sprintf(
            '%s --columns-sm: %d; --columns-md: %d; --columns-lg: %d; --columns-xl: %d;',
            $baseStyle,
            $this->smColumns,
            $this->mdColumns,
            $this->lgColumns,
            $this->xlColumns,
        ));
    }

    public function gridRowsStyle(string $gridId): ?HtmlString
    {
        if ($this->gridRows < 1) {
            return null;
        }

        $defaultLimit = $this->gridRows;
        $smLimit = $this->gridRows * $this->smColumns;
        $mdLimit = $this->gridRows * $this->mdColumns;
        $lgLimit = $this->gridRows * $this->lgColumns;
        $xlLimit = $this->gridRows * $this->xlColumns;
        $defaultHiddenFrom = $defaultLimit + 1;
        $smHiddenFrom = $smLimit + 1;
        $mdHiddenFrom = $mdLimit + 1;
        $lgHiddenFrom = $lgLimit + 1;
        $xlHiddenFrom = $xlLimit + 1;

        return new HtmlString(<<<HTML
<style>
    #{$gridId} > :nth-child(n) { display: revert; }
    #{$gridId} > :nth-child(n + {$defaultHiddenFrom}) { display: none; }
    @media (min-width: 640px) {
        #{$gridId} > :nth-child(n) { display: revert; }
        #{$gridId} > :nth-child(n + {$smHiddenFrom}) { display: none; }
    }
    @media (min-width: 768px) {
        #{$gridId} > :nth-child(n) { display: revert; }
        #{$gridId} > :nth-child(n + {$mdHiddenFrom}) { display: none; }
    }
    @media (min-width: 1024px) {
        #{$gridId} > :nth-child(n) { display: revert; }
        #{$gridId} > :nth-child(n + {$lgHiddenFrom}) { display: none; }
    }
    @media (min-width: 1280px) {
        #{$gridId} > :nth-child(n) { display: revert; }
        #{$gridId} > :nth-child(n + {$xlHiddenFrom}) { display: none; }
    }
</style>
HTML);
    }

    /**
     * @throws JsonException
     */
    public function carouselBreakpointsJson(): string
    {
        return json_encode([
            320 => [
                'slidesPerView' => $this->mobileSlides,
                'spaceBetween' => 16,
            ],
            520 => [
                'slidesPerView' => $this->smSlides,
                'spaceBetween' => 20,
            ],
            760 => [
                'slidesPerView' => max($this->smSlides, 3),
                'spaceBetween' => 24,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function carouselAlign(): string
    {
        if ($this->highlightActive) {
            return 'center';
        }

        return $this->carouselAlign;
    }

    public function carouselLoop(): bool
    {
        return $this->carouselRows > 1 ? false : $this->carouselLoop;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private static function hasAnyMeta(Widget $widget, array $keys): bool
    {
        foreach ($keys as $key) {
            if (self::meta($widget, $key) !== null) {
                return true;
            }
        }

        return false;
    }

    private static function intMeta(Widget $widget, string $key, int $default, int $min, int $max): int
    {
        $value = self::meta($widget, $key, $default);
        $value = is_numeric($value) ? (int) $value : $default;

        return min($max, max($min, $value));
    }

    private static function floatMeta(Widget $widget, string $key, float $default, float $min, float $max): float
    {
        $value = self::meta($widget, $key, $default);
        $value = is_numeric($value) ? (float) $value : $default;

        return min($max, max($min, $value));
    }

    private static function meta(Widget $widget, string $key, mixed $default = null): mixed
    {
        $meta = $widget->meta ?? [];

        if (Arr::has($meta, $key)) {
            $value = data_get($meta, $key);

            if (filled($value)) {
                return $value;
            }
        }

        $type = $widget->relationLoaded('blueprint')
            ? $widget->getRelation('blueprint')
            : (Model::getConnectionResolver() === null ? null : $widget->getRelationValue('blueprint'));

        if ($type instanceof Blueprint) {
            return $type->getMeta($key, $default);
        }

        return $default;
    }

    private static function responsiveLayoutPattern(Widget $widget): ResponsiveLayoutPattern
    {
        $value = self::meta($widget, 'responsive_layout_pattern');

        if ($value === null || $value === '') {
            return ResponsiveLayoutPattern::DesktopGridMobileCarousel;
        }

        return ResponsiveLayoutPattern::fromNullable($value);
    }
}
