@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="flex flex-wrap items-center gap-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
            @isset($icon)
                <span class="inline-flex shrink-0 text-gray-600 dark:text-gray-300" aria-hidden="true">{{ $icon }}</span>
            @endisset
            <span>{{ $title }}</span>
        </h1>
        @if ($description)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
