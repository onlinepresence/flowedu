@props([
    'title' => null,
])

<x-layouts.college-shell
    :title="$title"
    :headerTitle="$headerTitle ?? null"
    :headerDescription="$headerDescription ?? null"
>
    <x-slot name="sidebar">
        <livewire:navigation.teacher-sidebar />
    </x-slot>

    @isset($headerActions)
        <x-slot name="headerActions">
            {{ $headerActions }}
        </x-slot>
    @endisset

    @isset($headerIcon)
        <x-slot name="headerIcon">
            {{ $headerIcon }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-layouts.college-shell>
