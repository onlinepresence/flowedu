<div class="mx-auto max-w-lg space-y-6">
    <x-card :title="__('Approve admission')">
        <div class="space-y-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Index number') }}: <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $index_number }}</span></p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Student') }}: <span class="font-medium text-gray-900 dark:text-white">{{ $student->lastname }}</span></p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Guardian complete') }}: {{ $guardianComplete ? __('Yes') : __('No') }}</p>
            @if ($student->approved)
                <p class="flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-300">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    {{ __('This student is already approved.') }}
                </p>
            @endif
        </div>

        <x-slot name="footer">
            @error('approve')
                <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @if (! $student->approved)
                <x-college-submit-button action="approve">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    {{ __('Approve admission') }}
                </x-college-submit-button>
            @endif
        </x-slot>
    </x-card>
</div>
