@props([
    'name',
    'title' => '',
    'type' => 'warning', // warning, danger, info, success
    'confirmText' => __('Confirm'),
    'cancelText' => __('Cancel'),
    'wireConfirm' => null,
])

@php
    $iconClass = match($type) {
        'danger' => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        'success' => 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        default => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    };

    $icon = match($type) {
        'danger' => 'fa-solid fa-triangle-exclamation',
        'success' => 'fa-solid fa-circle-check',
        'info' => 'fa-solid fa-circle-info',
        default => 'fa-solid fa-circle-exclamation',
    };
@endphp

<x-modal :name="$name" maxWidth="md" {{ $attributes }}>
    <div class="p-6">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconClass }}">
                <i class="{{ $icon }} text-lg"></i>
            </div>
            <div class="min-w-0 flex-1">
                @if($title)
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $slot }}
                </div>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', '{{ $name }}')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                {{ $cancelText }}
            </button>
            @if($wireConfirm)
                <button
                    type="button"
                    wire:click="{{ $wireConfirm }}"
                    x-on:click="$dispatch('close-modal', '{{ $name }}')"
                    class="rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm {{ $type === 'danger' ? 'bg-red-600 hover:bg-red-500' : 'bg-purple-600 hover:bg-purple-500' }}"
                >
                    {{ $confirmText }}
                </button>
            @endif
        </div>
    </div>
</x-modal>
