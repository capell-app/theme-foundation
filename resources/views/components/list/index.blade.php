@props(['item', 'gap' => 'gap-y-1.5'])
<ul {{ $attributes->class(['list-items flex list-disc flex-col ps-4', $gap]) }}>
    {{ $slot }}
</ul>
