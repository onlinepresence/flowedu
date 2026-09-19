@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}@if($required)<span class="ml-0.5 text-red-500" aria-hidden="true">*</span><span class="sr-only">{{ __('(required)') }}</span>@endif
</label>
