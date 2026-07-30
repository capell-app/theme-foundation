@props([
    'color',
    'position',
])

<div
    @class([
        'capell-asset-extended-background',
        'absolute top-0 -z-1 h-full w-1/2',
        match ($position) {
            'left' => 'left-0',
            'right' => 'right-0',
        },
        match ($color) {
            'danger' => 'bg-danger',
            'dark-gray' => 'bg-dark-gray',
            'gray' => 'bg-gray',
            'info' => 'bg-info',
            'light-gray' => 'bg-light-gray',
            'primary' => 'bg-primary',
            'secondary' => 'bg-secondary',
            'success' => 'bg-success',
            'warning' => 'bg-warning',
            'white' => 'bg-white',
        },
    ])
></div>
