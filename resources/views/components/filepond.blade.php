@props([
    'field',
    'purpose' => 'generic_image',
    'label' => null,
    'accept' => null,
])

@php
    $instanceId = 'fp-'.preg_replace('/[^a-zA-Z0-9]/', '_', $field);
    $syncId = $instanceId.'-sync';
@endphp

<div class="space-y-1">
    @if ($label)
        <label for="{{ $instanceId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <input type="hidden" id="{{ $syncId }}" wire:model.live="{{ $field }}" data-model="{{ $field }}" />

    <div
        wire:ignore
        class="college-filepond-root"
        data-college-filepond
        data-purpose="{{ $purpose }}"
        data-process-url="{{ route('college.filepond.process') }}"
        data-revert-url="{{ route('college.filepond.revert') }}"
        data-sync-selector="#{{ $syncId }}"
        @if ($accept) data-accept="{{ $accept }}" @endif
    >
        <input type="file" id="{{ $instanceId }}" data-filepond-input />
    </div>

    @error($field)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
