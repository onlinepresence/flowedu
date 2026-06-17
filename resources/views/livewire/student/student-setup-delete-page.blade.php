<div class="mx-auto max-w-lg space-y-6">
    <div class="overflow-hidden rounded-lg border border-red-200 bg-white shadow-sm dark:border-red-900/40 dark:bg-gray-800">
        <div class="border-b border-red-100 px-6 py-4 dark:border-red-900/30">
            <h1 class="text-lg font-medium text-red-800 dark:text-red-200">{{ __('Cancel registration') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('This permanently deletes your user account and application data (legacy delete-account).') }}</p>
        </div>
        <form wire:submit="destroy" class="space-y-4 p-6">
            <input type="hidden" wire:model="user_id" />
            <div>
                <label for="del-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirm password') }}</label>
                <input wire:model="password" id="del-password" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @error('user_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <x-college-form-submit target="destroy" variant="danger" class="inline-flex">
                {{ __('Delete my account') }}
            </x-college-form-submit>
        </form>
    </div>
</div>
