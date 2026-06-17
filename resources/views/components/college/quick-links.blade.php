@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if ($title || isset($header))
        @if (isset($header))
            {{ $header }}
        @else
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $title }}</span>
        @endif
    @endif
    
    <div class="rounded-xl border border-gray-200/80 bg-gray-50 p-4 shadow-sm dark:border-gray-700/80 dark:bg-gray-800/30">
        <div class="flex flex-wrap gap-2.5">
            {{ $slot }}
        </div>
    </div>
</div>
