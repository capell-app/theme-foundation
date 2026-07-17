<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget;

use Capell\FoundationTheme\Support\View\FoundationThemeViewName;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Models\Widget;
use Closure;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use stdClass;
use Throwable;

abstract class AbstractWidget extends Component
{
    protected static string $defaultView = 'capell-theme-foundation::components.widget.default';

    protected bool $skipRender = false;

    /**
     * @param  array<array-key, mixed>  $widgetData
     * @param  array<array-key, mixed>  $container
     */
    public function __construct(
        public array $container,
        public string $containerKey,
        public int $widgetIndex,
        public stdClass $loop,
        public Widget $widget,
        public array $widgetData = [],
        public mixed $pageSlot = null,
        public int $occurrence = 1,
    ) {
        $this->mountWidget();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function render(array $data = []): View|string|Closure
    {
        $data = [
            'container' => $this->container,
            'containerKey' => $this->containerKey,
            'loop' => $this->loop,
            'language' => $this->frontendContextValue('language'),
            'layout' => $this->frontendContextValue('layout'),
            'pageRecord' => $this->frontendContextValue('page'),
            'site' => $this->frontendContextValue('site'),
            'theme' => $this->frontendContextValue('theme'),
            'urlParams' => $this->frontendParams(),
            'widget' => $this->widget,
            'widgetData' => $this->widgetData,
            'widgetIndex' => $this->widgetIndex,
            'pageSlot' => $this->pageSlot,
            ...$this->viewData(),
            ...$data,
        ];

        if ($this->skipRender && config('capell-layout-builder.widget.skip_render_empty', true) === true) {
            return '';
        }

        $data['component_item'] = $this->getComponentItem();

        return resolve(Factory::class)->make(FoundationThemeViewName::canonical($this->getViewFile()), $data);
    }

    protected function getComponentItem(): ?string
    {
        return $this->widget->getComponentItem();
    }

    protected function getViewFile(): string
    {
        return $this->widget->getViewFile() ?? static::$defaultView;
    }

    protected function mountWidget(): void {}

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
    }

    private function frontendContextValue(string $method): mixed
    {
        try {
            return match ($method) {
                'language' => Frontend::language(),
                'layout' => Frontend::layout(),
                'page' => Frontend::page(),
                'site' => Frontend::site(),
                'theme' => Frontend::theme(),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function frontendParams(): array
    {
        try {
            return Frontend::params();
        } catch (Throwable) {
            return [];
        }
    }
}
