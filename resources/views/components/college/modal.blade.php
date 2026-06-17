@props([
    'name',
    'title' => '',
    'maxWidth' => '2xl',
    'show' => false,
    /** When true, backdrop/Escape match Livewire @if toggles (use explicit wire:click close in footer). */
    'livewireSynced' => false,
])

@php
$closeOnBackdrop = ! $livewireSynced;
$closeOnEscape = ! $livewireSynced;
@endphp

<x-modal
    :name="$name"
    :show="$show"
    :maxWidth="$maxWidth"
    :closeOnBackdrop="$closeOnBackdrop"
    :closeOnEscape="$closeOnEscape"
    {{ $attributes->whereStartsWith('focusable') }}
>
    @if ($title !== '')
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
        </div>
    @endif

    <div class="px-6 py-4">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40">
            {{ $footer }}
        </div>
    @endisset
</x-modal>
