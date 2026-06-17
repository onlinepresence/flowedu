@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-600 dark:bg-gray-800']) }}>
    @if (isset($icon))
        <div class="mx-auto flex h-12 w-12 items-center justify-center text-gray-400">
            {{ $icon }}
        </div>
    @else
        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
    @endif

    <h2 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ $title }}</h2>
    @if ($description)
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if (isset($footer))
        <div class="mt-6">
            {{ $footer }}
        </div>
    @endif
</div>
