@props([
    'cols' => 4,
])

<div {{ $attributes->merge(['class' => "grid gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-750 dark:bg-gray-800 sm:grid-cols-2 md:grid-cols-{$cols}"]) }}>
    {{ $slot }}
</div>
