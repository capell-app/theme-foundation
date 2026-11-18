<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Frontend\Contracts\FrontendContextReader;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Decides which heading level a hero renders, in PHP, so no template has to
 * claim the page's single h1 itself. Heroes render in document order: the
 * first one with a heading takes the h1 and records that on the frontend
 * context, every later hero (and the shared page-content widget) drops to h2.
 */
final class ResolveHeroHeadingLevelAction
{
    use AsFake;
    use AsObject;

    public const string PRIMARY = 'h1';

    public const string SECONDARY = 'h2';

    private const string FRONTEND_FLAG = 'has_primary_heading';

    /**
     * @param  string|null  $configuredLevel  An editor-chosen h1, h2 or h3, which wins when valid.
     * @param  bool  $hasHeading  False when the hero has no heading text, so it must not take the h1.
     * @return 'h1'|'h2'|'h3'
     */
    public function handle(?string $configuredLevel = null, bool $hasHeading = true): string
    {
        $context = app()->bound(FrontendContextReader::class) ? resolve(FrontendContextReader::class) : null;
        $taken = $context !== null && (bool) $context->getFrontendData(self::FRONTEND_FLAG);

        $level = match (true) {
            $configuredLevel === 'h1' => 'h1',
            $configuredLevel === 'h2' => 'h2',
            $configuredLevel === 'h3' => 'h3',
            default => $taken ? 'h2' : 'h1',
        };

        if ($level === 'h1' && $hasHeading && ! $taken) {
            $context?->setFrontendData(self::FRONTEND_FLAG, true);
        }

        return $level;
    }
}
