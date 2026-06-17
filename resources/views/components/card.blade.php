@props([
    'title' => null,
])

<div {{ $attributes->class(['overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    @isset($header)
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $header }}
        </div>
    @elseif($title)
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40">
            {{ $footer }}
        </div>
    @endisset
</div>
