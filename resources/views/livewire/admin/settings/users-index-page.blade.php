<div class="mx-auto max-w-7xl space-y-6">
    @error('impersonate')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $message }}
        </div>
    @enderror

    @if ($canCreateUsers)
        <x-slot name="headerActions">
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-create-user')"
                class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all"
            >
                <i class="fa-solid fa-user-plus mr-2"></i>{{ __('New User') }}
            </button>
        </x-slot>
    @endif

    <!-- Search and Filter Bar -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <div class="relative w-full sm:flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                </div>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    id="user-search" 
                    type="search" 
                    class="block w-full rounded-lg border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white placeholder-gray-400" 
                    placeholder="{{ __('Search users by username, email, or full name...') }}" 
                />
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <x-card class="overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Username') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Account Type') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Email Address') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Active Status') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody wire:loading.remove.delay class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/40 transition-colors" wire:key="u-{{ $user->id }}">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ $user->username }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ 
                                    $user->type === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400' : (
                                    $user->type === 'teacher' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' : (
                                    $user->type === 'student' ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400' : 'bg-gray-50 text-gray-750 dark:bg-gray-700/50 dark:text-gray-300')) 
                                }}">
                                    <i class="fa-solid {{ 
                                        $user->type === 'admin' ? 'fa-user-tie' : (
                                        $user->type === 'teacher' ? 'fa-chalkboard-user' : (
                                        $user->type === 'student' ? 'fa-user-graduate' : 'fa-user')) 
                                    }} text-[10px]"></i>
                                    {{ $user->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300 font-mono">{{ $user->email ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                <!-- Interactive Toggle Switch for Active Status -->
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input 
                                        type="checkbox" 
                                        @checked($user->active) 
                                        wire:click="openToggleActiveModal({{ $user->id }})" 
                                        class="peer sr-only" 
                                        @disabled($currentUserId === $user->id)
                                    />
                                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700 disabled:opacity-50"></div>
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end">
                                    @if ($currentUserId === $user->id)
                                        <a href="{{ $currentUserProfileRoute }}" wire:navigate class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300">
                                            <i class="fa-solid fa-user-gear"></i>
                                            <span>{{ __('Profile') }}</span>
                                        </a>
                                    @else
                                        <x-dropdown align="right" width="48">
                                            <x-slot:trigger>
                                                <button type="button" class="inline-flex items-center gap-1 rounded-md bg-white p-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700 transition">
                                                    <i class="fa-solid fa-ellipsis-vertical text-sm w-4 text-center"></i>
                                                </button>
                                            </x-slot:trigger>
                                            <x-slot:content>
                                                @can('impersonate', $user)
                                                    @if (! session()->has('college_impersonator_id'))
                                                        <button
                                                            type="button"
                                                            wire:click="openImpersonateModal({{ $user->id }})"
                                                            class="flex w-full items-center px-4 py-2 text-left text-sm text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-gray-800 font-semibold transition"
                                                        >
                                                            <i class="fa-solid fa-user-secret mr-2.5 text-center w-4"></i>
                                                            {{ __('Impersonate') }}
                                                        </button>
                                                    @endif
                                                @endcan
                                                @can('updateForUserSettings', $user)
                                                    <button 
                                                        type="button" 
                                                        wire:click="openEditModal({{ $user->id }})" 
                                                        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-305 dark:hover:bg-gray-800 font-semibold transition"
                                                    >
                                                        <i class="fa-solid fa-user-pen mr-2.5 text-center w-4"></i>
                                                        {{ __('Edit') }}
                                                    </button>
                                                @endcan
                                                @can('sendPasswordResetForUserSettings', $user)
                                                    <button
                                                        type="button"
                                                        wire:click="sendPasswordReset({{ $user->id }})"
                                                        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-305 dark:hover:bg-gray-800 font-semibold transition"
                                                    >
                                                        <i class="fa-solid fa-key mr-2.5 text-center w-4"></i>
                                                        {{ __('Reset Password') }}
                                                    </button>
                                                @endcan
                                                @can('toggleActiveForUserSettings', $user)
                                                    <button 
                                                        type="button" 
                                                        wire:click="openToggleActiveModal({{ $user->id }})" 
                                                        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-105 dark:text-gray-305 dark:hover:bg-gray-800 font-semibold transition border-t border-gray-100 dark:border-gray-700/50 mt-1 pt-2"
                                                    >
                                                        <i class="fa-solid {{ $user->active ? 'fa-user-lock text-red-500' : 'fa-user-check text-green-500' }} mr-2.5 text-center w-4"></i>
                                                        {{ $user->active ? __('Lock Account') : __('Unlock Account') }}
                                                    </button>
                                                @endcan
                                            </x-slot:content>
                                        </x-dropdown>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <i class="fa-regular fa-folder-open text-3xl text-gray-300 dark:text-gray-650 mb-3 block"></i>
                                <p class="font-semibold">{{ __('No users match your search query.') }}</p>
                                @if (trim($search) !== '')
                                    <button
                                        type="button"
                                        wire:click="$set('search', '')"
                                        class="mt-4 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-705 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                                    >{{ __('Clear Search') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody wire:loading.delay class="divide-y divide-gray-200 dark:divide-gray-700">
                    <x-skeleton-table-rows :columns="5" :rows="8" />
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/10">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    <!-- Edit User Modal -->
    <x-college.modal name="users-edit" :title="__('Edit User Settings')" maxWidth="lg">
        <form id="users-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2 p-1">
            <div class="sm:col-span-2">
                <x-input-label :value="__('Full Name')" />
                <x-text-input wire:model="editName" type="text" class="mt-1.5 block w-full" required />
                <x-input-error :messages="$errors->get('editName')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('Username')" />
                <x-text-input wire:model="editUsername" type="text" class="mt-1.5 block w-full font-mono" required />
                <x-input-error :messages="$errors->get('editUsername')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('Email Address')" />
                <x-text-input wire:model="editEmail" type="email" class="mt-1.5 block w-full font-mono" />
                <x-input-error :messages="$errors->get('editEmail')" class="mt-1" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label :value="__('User Cohort Type')" />
                <x-select-input wire:model="editType" class="mt-1.5 block w-full" :disabled="$editType === 'student'">
                    <option value="student">{{ __('Student') }}</option>
                    <option value="teacher">{{ __('Teacher') }}</option>
                    <option value="admin">{{ __('Admin') }}</option>
                    <option value="staff">{{ __('Staff') }}</option>
                </x-select-input>
                @if($editType === 'student')
                    <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400 font-semibold"><i class="fa-solid fa-circle-info mr-1"></i>{{ __('Student account cohort types cannot be modified.') }}</p>
                @endif
                <x-input-error :messages="$errors->get('editType')" class="mt-1" />
            </div>
            
            <div class="flex items-center justify-between sm:col-span-2 mt-2 bg-gray-55/60 dark:bg-gray-900/40 p-3 rounded-lg border border-gray-150 dark:border-gray-700">
                <div class="space-y-0.5">
                    <label for="users-edit-active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Account Active') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Allows this user credentials to access their portal dashboard.') }}</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" wire:model="editActive" id="users-edit-active" class="peer sr-only" @disabled($editingUserId === $currentUserId)>
                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700 disabled:opacity-50"></div>
                </label>
                <x-input-error :messages="$errors->get('editActive')" class="ml-2" />
            </div>
        </form>
        <x-slot:footer>
            <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </button>
            <button type="submit" form="users-edit-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                {{ __('Save Changes') }}
            </button>
        </x-slot:footer>
    </x-college.modal>

    <!-- Create User Modal -->
    <x-college.modal name="users-create" :title="__('Add New System User')" maxWidth="lg">
        <form id="users-create-form" wire:submit.prevent="saveCreate" class="grid gap-4 sm:grid-cols-2 p-1">
            <div class="sm:col-span-2">
                <x-input-label :value="__('Full Name')" />
                <x-text-input wire:model="createName" type="text" class="mt-1.5 block w-full" placeholder="e.g. John Doe" required />
                <x-input-error :messages="$errors->get('createName')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('Username')" />
                <x-text-input wire:model="createUsername" type="text" class="mt-1.5 block w-full font-mono" placeholder="e.g. johndoe" required />
                <x-input-error :messages="$errors->get('createUsername')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('Email Address')" />
                <x-text-input wire:model="createEmail" type="email" class="mt-1.5 block w-full font-mono" placeholder="e.g. john@example.com" />
                <x-input-error :messages="$errors->get('createEmail')" class="mt-1" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label :value="__('User Cohort Type')" />
                <x-select-input wire:model="createType" class="mt-1.5 block w-full">
                    <option value="student">{{ __('Student') }}</option>
                    <option value="teacher">{{ __('Teacher') }}</option>
                    <option value="admin">{{ __('Admin') }}</option>
                    <option value="staff">{{ __('Staff') }}</option>
                </x-select-input>
                <x-input-error :messages="$errors->get('createType')" class="mt-1" />
            </div>
            
            <div class="flex items-center justify-between sm:col-span-2 mt-2 bg-gray-55/60 dark:bg-gray-900/40 p-3 rounded-lg border border-gray-150 dark:border-gray-700">
                <div class="space-y-0.5">
                    <label for="users-create-active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Account Active') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Allows this user credentials to access their portal dashboard.') }}</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" wire:model="createActive" id="users-create-active" class="peer sr-only">
                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                </label>
            </div>
            
            <div>
                <x-input-label :value="__('Password')" />
                <x-text-input wire:model="createPassword" type="password" class="mt-1.5 block w-full" required />
                <x-input-error :messages="$errors->get('createPassword')" class="mt-1" />
            </div>
            <div>
                <x-input-label :value="__('Confirm Password')" />
                <x-text-input wire:model="createPasswordConfirmation" type="password" class="mt-1.5 block w-full" required />
                <x-input-error :messages="$errors->get('createPasswordConfirmation')" class="mt-1" />
            </div>
        </form>
        <x-slot:footer>
            <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </button>
            <button type="submit" form="users-create-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                {{ __('Create User') }}
            </button>
        </x-slot:footer>
    </x-college.modal>

    <!-- Toggle Lock Status Confirmation Modal -->
    <x-college.modal name="users-toggle-active" :title="__('Confirm Status Change')" maxWidth="md">
        <div class="p-1">
            <p class="text-sm text-gray-650 dark:text-gray-400 font-semibold">{{ __('Are you sure you want to change this account\'s active status? Toggling active status will lock or unlock portal access for the user immediately.') }}</p>
        </div>
        <x-slot:footer>
            <button type="button" wire:click="closeToggleActiveModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </button>
            <button type="button" wire:click="confirmToggleActive" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                {{ __('Confirm') }}
            </button>
        </x-slot:footer>
    </x-college.modal>

    <!-- Impersonation Confirmation Modal -->
    <x-college.modal name="users-confirm-impersonate" :title="__('Confirm Impersonation')" maxWidth="md">
        <div class="p-1">
            <p class="text-sm text-gray-650 dark:text-gray-400 font-semibold">
                {{ __('Are you sure you want to impersonate this user? You will be logged into their account, redirected to their dashboard, and your administrative session will be suspended. You can exit impersonation at any time.') }}
            </p>
        </div>
        <x-slot:footer>
            <button type="button" wire:click="closeImpersonateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </button>
            <button type="button" wire:click="confirmImpersonate" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                {{ __('Confirm') }}
            </button>
        </x-slot:footer>
    </x-college.modal>
</div>
