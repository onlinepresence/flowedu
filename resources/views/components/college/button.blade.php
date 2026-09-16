@props([
    'variant' => 'primary',
    'type' => 'button',
])

{{--
    Shared button. Variants: primary | secondary | tertiary | danger.
    Only valid Tailwind palette shades (no gray-250/350/750-style values that
    Tailwind never generates — those silently render wrong in dark mode).

    Attribute-forwarding: wire:click, x-on:click, form, wire:loading.attr,
    disabled, title, etc. all pass through, so it is safe inside Livewire.
--}}

@php
    $styles = match ($variant) {
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-400',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline-indigo-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
        'tertiary' => 'text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500 focus-visible:outline-indigo-600 dark:text-indigo-400 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-300',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500 focus-visible:outline-red-600 dark:bg-red-500 dark:hover:bg-red-400 dark:focus-visible:outline-red-400',
        default => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50 {$styles}"]) }}
>{{ $slot }}</button>
