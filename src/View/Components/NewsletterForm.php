<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components;

use Capell\FoundationTheme\Actions\ResolveNewsletterFormDataAction;
use Capell\FoundationTheme\Data\NewsletterFormData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class NewsletterForm extends Component
{
    public readonly NewsletterFormData $form;

    public function __construct(
        string $fallbackAction = '#newsletter',
        string $source = 'public_newsletter',
    ) {
        $this->form = ResolveNewsletterFormDataAction::run(
            fallbackAction: $fallbackAction,
            source: $source,
        );
    }

    public function render(): View
    {
        return view('capell-theme-foundation::forms.newsletter');
    }
}
