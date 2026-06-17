@props([
    'title' => null,
    'hideHeader' => false,
])

<x-layouts.college-shell
    :title="$title"
    :hideHeader="$hideHeader"
    :headerTitle="$headerTitle ?? null"
    :headerDescription="$headerDescription ?? null"
>
    <x-slot name="sidebar">
        <livewire:navigation.student-sidebar />
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
