<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Actions\ColorConverterAction;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static string run(mixed $value, string $fallback)
 */
final class ResolveSafeCssColorTokenAction
{
    use AsFake;
    use AsObject;

    public static function isSafeCssColorToken(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F;{}<>]/', $value) === 1) {
            return false;
        }

        if (preg_match('/^#(?:[0-9A-Fa-f]{3}|[0-9A-Fa-f]{4}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color)\([A-Za-z0-9\s.,%\/+\-]*\)$/i', $value) === 1) {
            return true;
        }

        return preg_match('/^(?:black|white|transparent|currentColor|red|green|blue|yellow|orange|purple|pink|gray|grey|indigo|violet|cyan|teal|lime|navy|silver|maroon|olive|aqua|fuchsia)$/i', $value) === 1;
    }

    public function handle(mixed $value, string $fallback): string
    {
        $resolvedValue = is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
        $convertedValue = $this->convert($resolvedValue);

        if ($convertedValue !== null) {
            return $convertedValue;
        }

        $convertedFallback = $this->convert($fallback);

        if ($convertedFallback !== null) {
            return $convertedFallback;
        }

        if (trim($fallback) === '') {
            return '';
        }

        return self::isSafeCssColorToken($fallback) ? trim($fallback) : 'transparent';
    }

    private function convert(string $value): ?string
    {
        try {
            $convertedValue = ColorConverterAction::run($value);
        } catch (Throwable) {
            return self::isSafeCssColorToken($value) ? trim($value) : null;
        }

        return is_string($convertedValue) && self::isSafeCssColorToken($convertedValue)
            ? $convertedValue
            : null;
    }
}
