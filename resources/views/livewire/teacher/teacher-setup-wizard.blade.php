<div class="mx-auto max-w-md">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <label for="teacher-username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Username') }}</label>
                <input wire:model="username" id="teacher-username" type="text" autocomplete="username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required />
                @error('username') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="teacher-profile-pic" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Passport photo') }} <span class="font-normal text-gray-500">({{ __('optional') }})</span></label>
                <x-filepond
                    field="profilePicPond"
                    purpose="passport_photo"
                    :label="__('Passport photo')"
                    accept="image/jpeg,image/png,image/webp,image/avif"
                />
                @error('profilePicPond') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            @if ($teacher->password_reset_required)
                <div>
                    <label for="teacher-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Password') }}</label>
                    <input wire:model="password" id="teacher-password" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                    @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="teacher-password-confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirm password') }}</label>
                    <input wire:model="password_confirmation" id="teacher-password-confirmation" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                </div>
            @else
                <div>
                    <label for="teacher-password-opt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('New password') }} <span class="font-normal text-gray-500">({{ __('optional') }})</span></label>
                    <input wire:model="password" id="teacher-password-opt" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                    @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="teacher-password-confirmation-opt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirm password') }}</label>
                    <input wire:model="password_confirmation" id="teacher-password-confirmation-opt" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                </div>
            @endif
            <x-college-form-submit target="save" class="w-full justify-center">
                {{ __('Continue') }}
            </x-college-form-submit>
        </form>
    </div>
</div>
