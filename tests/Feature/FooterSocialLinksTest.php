<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Feature;

use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireServiceProvider;
use Override;

final class FooterSocialLinksTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
    }

    public function test_it_does_not_fail_when_a_saved_social_icon_is_unavailable(): void
    {
        $this->blade(
            '<x-capell::footer.social-links :links="$links" />',
            [
                'links' => [
                    [
                        'icon' => 'fab-square-facebook',
                        'title' => null,
                        'type' => 'facebook',
                        'url' => 'https://facebook.com',
                    ],
                ],
            ],
        )
            ->assertElementExists('a[href="https://facebook.com"]');
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-theme-foundation';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
        ];
    }
}
