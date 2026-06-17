@props([
    'target' => null,
    'variant' => 'indigo',
])

@php
    $variantClass = match ($variant) {
        'purple' => 'bg-purple-600 shadow-sm hover:bg-purple-700 focus:ring-purple-500 dark:bg-purple-500 dark:hover:bg-purple-600',
        'danger' => 'bg-red-600 shadow-sm hover:bg-red-700 focus:ring-red-500',
        'auth' => 'border border-transparent bg-gray-800 font-semibold text-xs uppercase tracking-widest text-white shadow-none hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-indigo-500 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:active:bg-gray-300',
        default => 'bg-indigo-600 shadow-sm hover:bg-indigo-700 focus:ring-indigo-500',
    };
@endphp

<button
    type="submit"
    @if($target)
        wire:loading.attr="disabled"
        wire:target="{{ $target }}"
    @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 '.($variant === 'auth' ? 'transition duration-150 ease-in-out dark:focus:ring-offset-gray-800 ' : 'text-white dark:focus:ring-offset-gray-900 ').$variantClass]) }}
>
    @if($target)
        <span wire:loading.remove wire:target="{{ $target }}" class="inline-flex items-center gap-2">
            {{ $slot }}
        </span>
        <span wire:loading.delay.200ms wire:target="{{ $target }}" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
            {{ __('Please wait…') }}
        </span>
    @else
        {{ $slot }}
    @endif
</button>
