<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Upgrade;

use Capell\Core\Data\UpgradeContext;
use Capell\Core\Support\Upgrade\AbstractUpgradeStep;
use Capell\FoundationTheme\Actions\MoveStoredHeroEyebrowToKickerAction;
use Override;

/**
 * Keeps hero labels stored under the old `eyebrow` field visible once the
 * Platform, Editorial and Portfolio heroes read `kicker` only. It runs only
 * while such a stored label remains.
 */
final class MoveHeroEyebrowToKickerUpgradeStep extends AbstractUpgradeStep
{
    public const string TAG = 'capell.upgrade-steps';

    #[Override]
    public function id(): string
    {
        return 'capell-theme-foundation.move-hero-eyebrow-to-kicker';
    }

    #[Override]
    public function label(): string
    {
        return __('capell-theme-foundation::generic.upgrade_move_hero_eyebrow_to_kicker');
    }

    #[Override]
    public function package(): string
    {
        return 'capell-app/theme-foundation';
    }

    #[Override]
    public function shouldRun(UpgradeContext $context): bool
    {
        return MoveStoredHeroEyebrowToKickerAction::make()->hasStoredEyebrows();
    }

    #[Override]
    public function run(UpgradeContext $context): bool
    {
        if (! $context->dryRun) {
            MoveStoredHeroEyebrowToKickerAction::run();
        }

        return true;
    }
}
