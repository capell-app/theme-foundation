@props ([
    'background' => 'bg-white/95',
    'containerClass' => 'relative',
    'containerTag' => 'div',
    'dropdownClass' => 'absolute top-full z-50 mt-2 min-w-[230px] origin-top-right overflow-hidden border border-gray-200/80 backdrop-blur-sm focus:outline-hidden',
    'darkMode' => false,
    'rounded' => 'rounded-lg',
    'shadow' => '',
    'label' => '',
    'name' => '',
    'offset' => 8,
    'panelClickOutside' => '',
    'panelHiddenClass' => '',
    'panelTag' => 'div',
    'panelVisibleClass' => '',
    'placement' => 'bottom-start',
    'shift' => false,
    'stopTriggerClickPropagation' => false,
    'teleport' => false,
    'triggerClass' => 'focus-visible:ring-primary/40 inline-flex items-center justify-center rounded-md p-1 text-sm font-medium transition-colors focus:outline-hidden focus-visible:ring-2',
    'triggerClick' => 'toggle($event)',
    'triggerType' => 'button',
    'useFloat' => true,
])
@php
    use Illuminate\View\ComponentAttributeBag;

    $dropdownName = $name !== '' ? $name : 'dropdown';

    $floatDropdownData = '{}';

    $inlineDropdownData = "{\n        isOpen: false,\n        toggle: function () {\n            if (this.isOpen) {\n                return this.close()\n            }\n\n            \$refs['{$dropdownName}_toggle'].focus()\n\n            this.isOpen = true\n        },\n\n        open: function () {\n            this.isOpen = true\n        },\n\n        close: function (focusAfter) {\n            if (! this.isOpen) return\n\n            this.isOpen = false\n\n            focusAfter && focusAfter.focus()\n        },\n    }";

    $dropdownData = $useFloat ? $floatDropdownData : $inlineDropdownData;
    $floatingDropdownRef = preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $dropdownName) === 1
        ? "\$refs.{$dropdownName}_dropdown"
        : "\$refs['{$dropdownName}_dropdown']";
    $resolvedTriggerClick = $useFloat && $triggerClick === 'toggle($event)'
        ? "{$floatingDropdownRef}.toggle(\$event)"
        : $triggerClick;

    $containerAttributes = $attributes->except('class')->merge(['class' => $containerClass]);

    $panelAttributes = (new ComponentAttributeBag)->class([
        $dropdownClass,
        $rounded,
        $shadow,
        'hidden' => $useFloat,
        'absolute' => $useFloat,
        "divide-gray-100 {$background} text-gray-900" => ! $darkMode,
        'divide-gray-800 border-gray-800/80 bg-gray-950/95 text-gray-100' => $darkMode,
    ]);
@endphp

@if ($containerTag === 'li')
    <li
        x-data="{{ $dropdownData }}"
        {{ $containerAttributes }}
        x-id="['{{ $dropdownName }}-dropdown-button']"
        class="capell-dropdown"
    >
        <button
            @if ($triggerType === 'button')
                type="button"
            @endif
            x-ref="{{ $dropdownName }}_toggle"
            @if ($stopTriggerClickPropagation)
                x-on:click.stop="{{ $resolvedTriggerClick }}"
            @else
                x-on:click="{{ $resolvedTriggerClick }}"
            @endif
            @if (! $useFloat)
                :aria-expanded="isOpen"
                :aria-controls="$id('{{ $dropdownName }}-dropdown-button')"
            @endif
            {{ $trigger->attributes->class([$triggerClass]) }}
        >
            {{ $trigger }}
        </button>

        @if ($panelTag === 'ul')
            <ul
                role="menu"
                tabindex="-1"
                x-ref="{{ $dropdownName }}_dropdown"
                x-cloak
                @if ($panelClickOutside !== '')
                    x-on:click.outside="{{ $panelClickOutside }}"
                @endif
                @if ($useFloat)
                    x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave-end="opacity-0"
                    wire:ignore
                @endif
                @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                    x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
                @endif
                @if (! $useFloat)
                    x-bind:aria-hidden="(!isOpen).toString()"
                    x-bind:inert="!isOpen"
                @endif
                {{ $panelAttributes }}
                :id="$id('{{ $dropdownName }}-dropdown-button')"
            >
                {{ $slot }}
            </ul>
        @else
            <div
                role="menu"
                tabindex="-1"
                x-ref="{{ $dropdownName }}_dropdown"
                x-cloak
                @if ($panelClickOutside !== '')
                    x-on:click.outside="{{ $panelClickOutside }}"
                @endif
                @if ($useFloat)
                    x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave-end="opacity-0"
                    wire:ignore
                @endif
                @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                    x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
                @endif
                @if (! $useFloat)
                    x-bind:aria-hidden="(!isOpen).toString()"
                    x-bind:inert="!isOpen"
                @endif
                {{ $panelAttributes }}
                :id="$id('{{ $dropdownName }}-dropdown-button')"
            >
                {{ $slot }}
            </div>
        @endif
    </li>
@else
    <div
        x-data="{{ $dropdownData }}"
        {{ $containerAttributes }}
        x-id="['{{ $dropdownName }}-dropdown-button']"
    >
        <button
            @if ($triggerType === 'button')
                type="button"
            @endif
            x-ref="{{ $dropdownName }}_toggle"
            @if ($stopTriggerClickPropagation)
                x-on:click.stop="{{ $resolvedTriggerClick }}"
            @else
                x-on:click="{{ $resolvedTriggerClick }}"
            @endif
            @if (! $useFloat)
                :aria-expanded="isOpen"
                :aria-controls="$id('{{ $dropdownName }}-dropdown-button')"
            @endif
            {{ $trigger->attributes->class([$triggerClass]) }}
        >
            {{ $trigger }}
        </button>

        @if ($panelTag === 'ul')
            <ul
                role="menu"
                tabindex="-1"
                x-ref="{{ $dropdownName }}_dropdown"
                x-cloak
                @if ($panelClickOutside !== '')
                    x-on:click.outside="{{ $panelClickOutside }}"
                @endif
                @if ($useFloat)
                    x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave-end="opacity-0"
                    wire:ignore
                @endif
                @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                    x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
                @endif
                @if (! $useFloat)
                    x-bind:aria-hidden="(!isOpen).toString()"
                    x-bind:inert="!isOpen"
                @endif
                {{ $panelAttributes }}
                :id="$id('{{ $dropdownName }}-dropdown-button')"
            >
                {{ $slot }}
            </ul>
        @else
            <div
                role="menu"
                tabindex="-1"
                x-ref="{{ $dropdownName }}_dropdown"
                x-cloak
                @if ($panelClickOutside !== '')
                    x-on:click.outside="{{ $panelClickOutside }}"
                @endif
                @if ($useFloat)
                    x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave-end="opacity-0"
                    wire:ignore
                @endif
                @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                    x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
                @endif
                @if (! $useFloat)
                    x-bind:aria-hidden="(!isOpen).toString()"
                    x-bind:inert="!isOpen"
                @endif
                {{ $panelAttributes }}
                :id="$id('{{ $dropdownName }}-dropdown-button')"
            >
                {{ $slot }}
            </div>
        @endif
    </div>
@endif
