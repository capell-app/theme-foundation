<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\SiteSpec;

use Capell\Core\Contracts\SiteSpec\SiteSpecApplier;
use Capell\Core\Data\SiteSpec\CapellSiteSpecData;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\ThemeStudio\Assets\ThemeTokenValidator;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Override;

/**
 * Writes a SiteSpec's brand colour into Foundation's theme settings when the
 * spec chooses the Foundation theme. The primary colour becomes the primary
 * action colour only if it stays readable both ways Foundation uses it, under
 * white button text and as text on the light surface (4.5:1 each); otherwise
 * the current colour is kept. The logo is not handled here: Core
 * attaches it to the site, and Foundation's header renders the site logo.
 *
 * @method static bool run(CapellSiteSpecData $spec)
 */
final class ApplyFoundationThemeSiteSpecAction implements SiteSpecApplier
{
    use AsFake;
    use AsObject;

    private const string BUTTON_TEXT_COLOUR = '#ffffff';

    public function __construct(private readonly ThemeTokenValidator $validator) {}

    #[Override]
    public function key(): string
    {
        return 'theme-foundation';
    }

    /** @param array<string, Page> $pagesBySlug */
    #[Override]
    public function apply(CapellSiteSpecData $spec, Site $site, array $pagesBySlug): void
    {
        $this->handle($spec);
    }

    public function handle(CapellSiteSpecData $spec): bool
    {
        if ($spec->theme->key !== FoundationThemeServiceProvider::THEME_KEY) {
            return false;
        }

        $primary = $spec->theme->colors->primary;

        if (! is_string($primary) || preg_match('/^#[0-9a-fA-F]{6}$/', $primary) !== 1) {
            return false;
        }

        // Resolved here, not injected: the tagged applier is built on every
        // SiteSpec apply, including installs where Foundation has no settings.
        $settings = resolve(FoundationThemeSettings::class);

        if ($this->validator->contrastIssues(self::BUTTON_TEXT_COLOUR, $primary) !== []
            || $this->validator->contrastIssues($primary, $settings->surface_background_color) !== []) {
            return false;
        }

        $settings->primary_action_color = strtolower($primary);
        $settings->save();

        return true;
    }
}
