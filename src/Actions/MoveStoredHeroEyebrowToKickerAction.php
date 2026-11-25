<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * The Platform, Editorial and Portfolio heroes label themselves from the
 * `kicker` field. Sites built before that stored the label under `eyebrow`,
 * so this moves the stored value across. A hero that already has a kicker
 * keeps it, and running it again changes nothing.
 */
final class MoveStoredHeroEyebrowToKickerAction
{
    use AsFake;
    use AsObject;

    /** @var list<string> */
    private const array THEME_KEYS = ['platform', 'editorial', 'portfolio'];

    /** @return int the number of widgets and pages rewritten */
    public function handle(): int
    {
        $moved = 0;

        $this->widgets()->chunkById(200, function (iterable $widgets) use (&$moved): void {
            foreach ($widgets as $widget) {
                $meta = $widget->getAttribute('meta');

                if (! is_array($meta)) {
                    continue;
                }

                $updated = $this->moveInSection($meta);

                if ($updated !== $meta) {
                    $widget->forceFill(['meta' => $updated])->saveQuietly();
                    $moved++;
                }
            }
        });

        $this->pages()->chunkById(200, function (iterable $pages) use (&$moved): void {
            foreach ($pages as $page) {
                $meta = $page->getAttribute('meta');
                $sections = is_array($meta) ? data_get($meta, 'theme_demo.render_data.sections') : null;

                if (! is_array($meta) || ! is_array($sections)) {
                    continue;
                }

                $updated = array_map(
                    fn (mixed $section): mixed => is_array($section) ? $this->moveInSection($section) : $section,
                    $sections,
                );

                if ($updated !== $sections) {
                    data_set($meta, 'theme_demo.render_data.sections', $updated);
                    $page->forceFill(['meta' => $meta])->saveQuietly();
                    $moved++;
                }
            }
        });

        return $moved;
    }

    public function hasStoredEyebrows(): bool
    {
        return $this->widgets()->exists() || $this->pages()->exists();
    }

    /** @return Builder<Widget> */
    private function widgets(): Builder
    {
        return Widget::query()
            ->whereIn('component', array_map(
                static fn (string $themeKey): string => sprintf('capell.widget.%s.hero', $themeKey),
                self::THEME_KEYS,
            ))
            ->where('meta', 'like', '%"eyebrow"%');
    }

    /** @return Builder<Page> */
    private function pages(): Builder
    {
        return Page::query()
            ->where('meta', 'like', '%"theme_demo"%')
            ->where('meta', 'like', '%"eyebrow"%')
            ->where(function (Builder $query): void {
                foreach (self::THEME_KEYS as $themeKey) {
                    $query->orWhere('meta', 'like', sprintf('%%"theme_key":"%s"%%', $themeKey));
                }
            });
    }

    /**
     * @param  array<array-key, mixed>  $section
     * @return array<array-key, mixed>
     */
    private function moveInSection(array $section): array
    {
        $type = $section['type'] ?? null;

        if (! is_string($type) || ! str_starts_with($type, 'hero') || ! array_key_exists('eyebrow', $section)) {
            return $section;
        }

        if (blank($section['kicker'] ?? null) && filled($section['eyebrow'])) {
            $section['kicker'] = $section['eyebrow'];
        }

        unset($section['eyebrow']);

        return $section;
    }
}
