<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

final class FoundationThemeFinalPathGenerator implements PathGenerator
{
    public function getPath(SpatieMedia $media): string
    {
        return 'media/';
    }

    public function getPathForConversions(SpatieMedia $media): string
    {
        return 'media/conversions/';
    }

    public function getPathForResponsiveImages(SpatieMedia $media): string
    {
        return 'media/responsive';
    }
}
