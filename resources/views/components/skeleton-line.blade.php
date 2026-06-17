@props([
    'extraClass' => 'h-4 w-full',
])

<div
    role="presentation"
    aria-hidden="true"
    {{ $attributes->class(['animate-pulse rounded-md bg-gray-200 dark:bg-gray-700 '.$extraClass]) }}
></div>
