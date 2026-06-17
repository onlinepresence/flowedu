@props([
    'title',
    'value',
    'icon' => null,
    'href' => null,
    'color' => 'purple', // purple, green, amber, red, blue
])

@php
$colorClasses = [
    'purple' => [
        'text' => 'text-purple-600 dark:text-purple-400',
        'bg' => 'bg-purple-50 dark:bg-purple-900/30',
        'border' => 'hover:border-purple-300 dark:hover:border-purple-700',
    ],
    'green' => [
        'text' => 'text-green-600 dark:text-green-400',
        'bg' => 'bg-green-50 dark:bg-green-900/30',
        'border' => 'hover:border-green-300 dark:hover:border-green-700',
    ],
    'amber' => [
        'text' => 'text-amber-600 dark:text-amber-400',
        'bg' => 'bg-amber-50 dark:bg-amber-900/30',
        'border' => 'hover:border-amber-300 dark:hover:border-amber-700',
    ],
    'red' => [
        'text' => 'text-red-600 dark:text-red-400',
        'bg' => 'bg-red-50 dark:bg-red-900/30',
        'border' => 'hover:border-red-300 dark:hover:border-red-700',
    ],
    'blue' => [
        'text' => 'text-blue-600 dark:text-blue-400',
        'bg' => 'bg-blue-50 dark:bg-blue-900/30',
        'border' => 'hover:border-blue-300 dark:hover:border-blue-700',
    ],
][$color] ?? [
    'text' => 'text-purple-600 dark:text-purple-400',
    'bg' => 'bg-purple-50 dark:bg-purple-900/30',
    'border' => 'hover:border-purple-300 dark:hover:border-purple-700',
];

$classes = 'relative overflow-hidden rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm transition-all duration-200 dark:border-gray-700/80 dark:bg-gray-800';
if ($href) {
    $classes .= ' block hover:-translate-y-0.5 hover:shadow-md ' . $colorClasses['border'];
}
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</span>
                <span class="block text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $value ?? $slot }}</span>
            </div>
            @if ($icon)
                <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $colorClasses['bg'] }} {{ $colorClasses['text'] }} transition-transform duration-200 group-hover:scale-110">
                    @if ($icon instanceof \Illuminate\View\ComponentSlot || str_contains((string) $icon, '<'))
                        {{ $icon }}
                    @else
                        <i class="{{ $icon }} text-xl"></i>
                    @endif
                </div>
            @endif
        </div>
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</span>
                <span class="block text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $value ?? $slot }}</span>
            </div>
            @if ($icon)
                <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $colorClasses['bg'] }} {{ $colorClasses['text'] }}">
                    @if ($icon instanceof \Illuminate\View\ComponentSlot || str_contains((string) $icon, '<'))
                        {{ $icon }}
                    @else
                        <i class="{{ $icon }} text-xl"></i>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
