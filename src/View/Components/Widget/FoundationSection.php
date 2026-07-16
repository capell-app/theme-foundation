<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget;

use Illuminate\Support\Fluent;

final class FoundationSection extends AbstractWidget
{
    /** @var array<string, string> */
    private const array SECTION_VIEWS = [
        'navigation' => 'capell-theme-foundation::theme.sections.navigation',
        'hero' => 'capell-theme-foundation::theme.sections.hero',
        'features' => 'capell-theme-foundation::theme.sections.features',
        'proof' => 'capell-theme-foundation::theme.sections.proof',
        'content-listing' => 'capell-theme-foundation::theme.sections.content-listing',
        'search' => 'capell-theme-foundation::theme.sections.search',
        'pagination' => 'capell-theme-foundation::theme.sections.pagination',
        'form' => 'capell-theme-foundation::theme.sections.form',
        'contact-split' => 'capell-theme-foundation::theme.sections.contact-split',
        'cta' => 'capell-theme-foundation::theme.sections.cta',
        'footer' => 'capell-theme-foundation::theme.sections.footer',
    ];

    /** @var list<string> */
    private const array ANCHORABLE_SECTION_TYPES = [
        'features',
        'proof',
        'content-listing',
        'search',
        'pagination',
        'form',
        'contact-split',
        'cta',
    ];

    protected static string $defaultView = 'capell-theme-foundation::components.widget.foundation-section';

    /**
     * @return array{
     *     anchorable: bool,
     *     section: Fluent<array-key, mixed>,
     *     sectionType: string,
     *     sectionView: string|null
     * }
     */
    protected function viewData(): array
    {
        $sectionMeta = is_array($this->widget->meta) ? $this->widget->meta : [];
        $sectionType = is_string($sectionMeta['type'] ?? null) ? $sectionMeta['type'] : '';

        if ($sectionType === 'features' && is_array($sectionMeta['items'] ?? null)) {
            $sectionMeta['features'] = $sectionMeta['items'];
        }

        return [
            'anchorable' => in_array($sectionType, self::ANCHORABLE_SECTION_TYPES, true),
            'section' => new Fluent($sectionMeta),
            'sectionType' => $sectionType,
            'sectionView' => self::SECTION_VIEWS[$sectionType] ?? null,
        ];
    }
}
