<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-create-nt-modal.window="$wire.openCreateModal()"
>
    <x-slot name="headerActions">
        <button
            type="button"
            x-on:click="$dispatch('open-create-nt-modal')"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
            <i class="fa-solid fa-plus me-2"></i>
            {{ __('Add Non-Teaching Staff') }}
        </button>
    </x-slot>

    <!-- Filters Section -->
    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-3">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name, email, phone...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterDepartment" :value="__('Department')" />
            <select id="filterDepartment" wire:model.live="filterDepartment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterPosition" :value="__('Position')" />
            <select id="filterPosition" wire:model.live="filterPosition" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Positions') }}</option>
                @foreach ($positions as $pos)
                    <option value="{{ $pos }}">{{ $pos }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table Section -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('User') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Position') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="nts-{{ $row->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $row->user?->name ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $row->user?->email }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('Staff No:') }} {{ $row->user?->username }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->position ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->department?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($row->status === 'active')
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950/40 dark:text-green-300">
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-950/40 dark:text-red-300">
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-end text-sm font-medium">
                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $row->id }})"
                                        class="text-indigo-600 hover:text-indigo-950 dark:text-indigo-400 dark:hover:text-indigo-200"
                                        title="{{ __('Edit') }}"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                                    </button>
                                    @if ($row->status === 'active')
                                        <button
                                            type="button"
                                            wire:click="openDeactivateModal({{ $row->id }})"
                                            class="text-red-600 hover:text-red-950 dark:text-red-400 dark:hover:text-red-200"
                                            title="{{ __('Deactivate') }}"
                                        >
                                            <i class="fa-solid fa-user-slash text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No records found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    <!-- Predefined Positions datalist -->
    <datalist id="predefined-positions">
        <option value="Account Clerk"></option>
        <option value="Administrative Assistant"></option>
        <option value="IT Support Specialist"></option>
        <option value="Security Guard"></option>
        <option value="Janitor / Cleaner"></option>
        <option value="Cook / Kitchen Staff"></option>
        <option value="Driver"></option>
        <option value="Library Assistant"></option>
        <option value="Lab Assistant"></option>
        <option value="Storekeeper"></option>
        <option value="Gardener / Groundskeeper"></option>
        <option value="Nurse / Clinic Assistant"></option>
    </datalist>

    <!-- Create Modal -->
    @if ($showCreateModal)
        <x-college.modal name="nts-create" :title="__('Add Non-Teaching Staff')" :show="true" maxWidth="lg" livewireSynced>
            <form id="nts-create-form" wire:submit.prevent="saveCreate" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" :value="__('Full Name')" />
                    <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="username" :value="__('Staff Number (Username)')" />
                    <x-text-input id="username" type="text" class="mt-1 block w-full" wire:model="username" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" type="email" class="mt-1 block w-full" wire:model="email" required autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="password" :value="__('Initial Password')" />
                    <x-text-input id="password" type="password" class="mt-1 block w-full" wire:model="password" autocomplete="new-password" placeholder="{{ __('Defaults to Password@1 if empty') }}" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Leave empty to assign default password Password@1.') }}</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="position" :value="__('Position / Role')" />
                    <x-text-input id="position" type="text" class="mt-1 block w-full" wire:model="position" list="predefined-positions" required placeholder="{{ __('Type or select a position…') }}" />
                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone_number" :value="__('Phone Number')" />
                    <x-text-input id="phone_number" type="text" class="mt-1 block w-full" wire:model="phone_number" required />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="nts-create-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Create') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Edit Modal -->
    @if ($showEditModal)
        <x-college.modal name="nts-edit" :title="__('Edit Non-Teaching Staff')" :show="true" maxWidth="lg" livewireSynced>
            <form id="nts-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="edit-name" :value="__('Full Name')" />
                    <x-text-input id="edit-name" type="text" class="mt-1 block w-full" wire:model="name" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-username" :value="__('Staff Number (Username)')" />
                    <x-text-input id="edit-username" type="text" class="mt-1 block w-full" wire:model="username" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-email" :value="__('Email')" />
                    <x-text-input id="edit-email" type="email" class="mt-1 block w-full" wire:model="email" required autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-password" :value="__('New Password (optional)')" />
                    <x-text-input id="edit-password" type="password" class="mt-1 block w-full" wire:model="password" autocomplete="new-password" placeholder="{{ __('Leave blank to keep current password') }}" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-position" :value="__('Position / Role')" />
                    <x-text-input id="edit-position" type="text" class="mt-1 block w-full" wire:model="position" list="predefined-positions" required />
                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-department_id" :value="__('Department')" />
                    <select id="edit-department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-phone_number" :value="__('Phone Number')" />
                    <x-text-input id="edit-phone_number" type="text" class="mt-1 block w-full" wire:model="phone_number" required />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-status" :value="__('Status')" />
                    <select id="edit-status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="nts-edit-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Deactivate Modal -->
    @if ($showDeactivateModal)
        <x-college.modal name="nts-deactivate" :title="__('Deactivate staff member?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('They will no longer be able to sign in, and their non-teaching profile will be marked inactive.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeDeactivateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmDeactivate" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Deactivate') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
