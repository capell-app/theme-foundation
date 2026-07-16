<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Enums;

enum FoundationSectionWidgetComponentEnum: string
{
    case Navigation = 'capell.widget.foundation.navigation';
    case Hero = 'capell.widget.foundation.hero';
    case Features = 'capell.widget.foundation.features';
    case Proof = 'capell.widget.foundation.proof';
    case ContentListing = 'capell.widget.foundation.content-listing';
    case Search = 'capell.widget.foundation.search';
    case Pagination = 'capell.widget.foundation.pagination';
    case Form = 'capell.widget.foundation.form';
    case ContactSplit = 'capell.widget.foundation.contact-split';
    case Cta = 'capell.widget.foundation.cta';
    case Footer = 'capell.widget.foundation.footer';

    public static function fromSectionType(string $sectionType): ?self
    {
        return match ($sectionType) {
            'navigation' => self::Navigation,
            'hero' => self::Hero,
            'features' => self::Features,
            'proof' => self::Proof,
            'content-listing' => self::ContentListing,
            'search' => self::Search,
            'pagination' => self::Pagination,
            'form' => self::Form,
            'contact-split' => self::ContactSplit,
            'cta' => self::Cta,
            'footer' => self::Footer,
            default => null,
        };
    }
}
