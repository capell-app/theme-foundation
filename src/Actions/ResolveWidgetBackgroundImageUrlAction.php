<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveWidgetBackgroundImageUrlAction
{
    use AsFake;
    use AsObject;

    public function handle(Widget $widget): ?string
    {
        $value = $widget->getMeta('background_image');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // The value is written into a CSS url(). Spaces and parentheses occur in
        // real file names, so they are percent-encoded; anything else that could
        // close the function or start another declaration is refused.
        if (preg_match('/["\'\\\;<>]|[^\S ]/', $value) === 1) {
            return null;
        }

        $value = strtr($value, [' ' => '%20', '(' => '%28', ')' => '%29']);

        $parts = parse_url($value);

        if (is_array($parts) && is_string($parts['scheme'] ?? null) && $parts['scheme'] !== '') {
            return in_array(strtolower($parts['scheme']), ['http', 'https'], true) ? $value : null;
        }

        if (Str::startsWith($value, ['//', '/'])) {
            return $value;
        }

        return Storage::disk('public')->url(ltrim($value, '/'));
    }
}
