<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Support\Facades\Blade;
use Livewire\Blaze\Blaze;

/**
 * @param  array{0: int, 1: int, 2: int}  $from
 * @param  array{0: int, 1: int, 2: int}  $to
 * @return array{0: int, 1: int, 2: int}
 */
function footerContrastMix(array $from, array $to, float $towards): array
{
    return [
        (int) round($from[0] * (1 - $towards) + $to[0] * $towards),
        (int) round($from[1] * (1 - $towards) + $to[1] * $towards),
        (int) round($from[2] * (1 - $towards) + $to[2] * $towards),
    ];
}

/**
 * @param  array{0: int, 1: int, 2: int}  $colour
 */
function footerContrastLuminance(array $colour): float
{
    [$r, $g, $b] = array_map(static function (int $channel): float {
        $value = $channel / 255;

        return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, $colour);

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * @param  array{0: int, 1: int, 2: int}  $a
 * @param  array{0: int, 1: int, 2: int}  $b
 */
function footerContrastRatio(array $a, array $b): float
{
    $lighter = max(footerContrastLuminance($a), footerContrastLuminance($b));
    $darker = min(footerContrastLuminance($a), footerContrastLuminance($b));

    return ($lighter + 0.05) / ($darker + 0.05);
}

it('keeps the default footer muted text at WCAG AA contrast on the footer surfaces', function (): void {
    Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');

    $language = Language::factory()->english()->create();
    $theme = Theme::factory()->defaultMeta()->create();
    $site = Site::factory()->language($language)->theme($theme)->withTranslations($language, ['title' => 'Footer Site'])->create();
    $site->load('translation');
    $layout = Layout::factory()->site($site)->create(['admin' => []]);
    resolve(FrontendState::class)->withLanguage($language)->withSite($site)->withTheme($theme)->withLayout($layout);

    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = view('capell::components.footer.index')->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    preg_match('/:root \{(.*?)\}/s', $html, $block);
    expect($block)->toHaveCount(2);
    $body = $block[1] ?? '';

    $custom = static function (string $name) use ($body): string {
        preg_match('/--' . preg_quote($name, '/') . ': ([^;]+);/', $body, $match);

        return $match[1] ?? '';
    };

    $hex = static function (string $value): array {
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $value, $rgb) === 1) {
            return [(int) $rgb[1], (int) $rgb[2], (int) $rgb[3]];
        }

        return [(int) hexdec(substr($value, 1, 2)), (int) hexdec(substr($value, 3, 2)), (int) hexdec(substr($value, 5, 2))];
    };

    $foreground = $hex($custom('color-footer'));
    $background = $hex($custom('bg-color-footer'));

    preg_match('/--color-footer-muted: color-mix\(in srgb, var\(--color-footer\), var\(--bg-color-footer\) (\d+)%\)/', $body, $mix);
    expect($mix)->toHaveCount(2);
    $percentage = (int) ($mix[1] ?? 0);

    $muted = footerContrastMix($foreground, $background, $percentage / 100);

    foreach ([$background, footerContrastMix($background, [2, 6, 23], 0.03), footerContrastMix($background, [2, 6, 23], 0.06)] as $surface) {
        expect(footerContrastRatio($muted, $surface))->toBeGreaterThanOrEqual(4.5);
    }
});
