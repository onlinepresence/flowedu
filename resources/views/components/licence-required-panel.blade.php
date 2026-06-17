@props([
    'message' => '',
    'feature' => '',
    'showLicenceSettingsLink' => false,
])

<div class="space-y-4">
    <h1 class="flex items-center gap-2 text-lg font-medium text-gray-900 dark:text-gray-100">
        <i class="fa-solid fa-lock text-indigo-600 dark:text-indigo-400" aria-hidden="true"></i>
        {{ __('Licence required') }}
    </h1>
    <p class="text-sm text-gray-600 dark:text-gray-300">
        {{ $message }}
    </p>
    @if ($feature !== '')
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('Feature') }}: <span class="font-mono">{{ $feature }}</span>
        </p>
    @endif
    @if (! empty($showLicenceSettingsLink))
        <p class="pt-2">
            <a href="{{ route('admin.settings.licence') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
                {{ __('View licence & subscription') }}
            </a>
        </p>
    @endif
</div>
