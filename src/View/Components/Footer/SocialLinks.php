<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Footer;

use Capell\Core\Support\Security\PublicUrlSanitizer;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

final class SocialLinks extends Component
{
    /** @var array<int|string, mixed> */
    public array $links;

    /**
     * @param  iterable<int|string, mixed>  $links
     */
    public function __construct(iterable $links, public string $size = 'md')
    {
        $this->links = [];

        foreach ($links as $key => $link) {
            if (! is_array($link)) {
                continue;
            }

            $this->links[$key] = [
                ...$link,
                'safe_url' => PublicUrlSanitizer::sanitize($link['url'] ?? null),
            ];
        }
    }

    public function render(): ViewContract
    {
        return view('capell::components.footer.social-links');
    }
}
