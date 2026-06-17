<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-create-admin-modal.window="$wire.openCreateModal()"
>
    <x-slot name="headerActions">
        <button
            type="button"
            x-on:click="$dispatch('open-create-admin-modal')"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
            <i class="fa-solid fa-plus me-2"></i>
            {{ __('Add Administrator') }}
        </button>
    </x-slot>

    <!-- Filters Section -->
    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-2 md:grid-cols-5">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name, email, phone...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterRole" :value="__('Role')" />
            <select id="filterRole" wire:model.live="filterRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Roles') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                @endforeach
            </select>
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
            <x-input-label for="filterFaculty" :value="__('Faculty')" />
            <select id="filterFaculty" wire:model.live="filterFaculty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Faculties') }}</option>
                @foreach ($faculties as $fac)
                    <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterStatus" :value="__('Status')" />
            <select id="filterStatus" wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Role') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Position') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Department/Faculty') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($admins as $admin)
                        <tr wire:key="admin-{{ $admin->id }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $admin->othernames }} {{ $admin->lastname }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $admin->user?->email }}
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ __('Staff No:') }} {{ $admin->user?->username }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-700/30">
                                    {{ $admin->role?->display_name ?? __('N/A') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $admin->position_title ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ $admin->department?->name ?? '—' }}</div>
                                @if ($admin->faculty)
                                    <div class="text-xs text-gray-400">{{ $admin->faculty->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($admin->status === 'active')
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
                                        wire:click="openEditModal({{ $admin->id }})"
                                        class="text-indigo-600 hover:text-indigo-950 dark:text-indigo-400 dark:hover:text-indigo-200"
                                        title="{{ __('Edit') }}"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                                    </button>
                                    @if ($admin->status === 'active' && $admin->user_id !== auth()->id() && !$admin->user?->isAdminOwner())
                                        <button
                                            type="button"
                                            wire:click="openDeactivateModal({{ $admin->id }})"
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
                            <td colspan="6" class="px-6 py-10 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No administrators found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $admins->links() }}
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    @if ($showCreateModal)
        <x-college.modal name="admin-create" :title="__('Add Administrator')" :show="true" maxWidth="lg" livewireSynced>
            <form id="admin-create-form" wire:submit.prevent="saveCreate" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="lastname" :value="__('Last Name')" />
                    <x-text-input id="lastname" type="text" class="mt-1 block w-full" wire:model="lastname" required />
                    <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="othernames" :value="__('Other Names')" />
                    <x-text-input id="othernames" type="text" class="mt-1 block w-full" wire:model="othernames" required />
                    <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
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
                <div>
                    <x-input-label for="type" :value="__('Administrative Role')" />
                    <select id="type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Role…') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="position_title" :value="__('Position Title')" />
                    <x-text-input id="position_title" type="text" class="mt-1 block w-full" wire:model="position_title" placeholder="{{ __('e.g. Senior Registrar') }}" />
                    <x-input-error :messages="$errors->get('position_title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="faculty_id" :value="__('Faculty')" />
                    <select id="faculty_id" wire:model="faculty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Faculty…') }}</option>
                        @foreach ($faculties as $fac)
                            <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone_number" :value="__('Phone Number')" />
                    <x-text-input id="phone_number" type="text" class="mt-1 block w-full" wire:model="phone_number" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="gender" :value="__('Gender')" />
                    <select id="gender" wire:model="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date_of_appointment" :value="__('Date of Appointment')" />
                    <x-text-input id="date_of_appointment" type="date" class="mt-1 block w-full" wire:model="date_of_appointment" />
                    <x-input-error :messages="$errors->get('date_of_appointment')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="ghana_card" :value="__('Ghana Card PIN')" />
                    <x-text-input id="ghana_card" type="text" class="mt-1 block w-full" wire:model="ghana_card" placeholder="e.g. GHA-123456789-0" />
                    <x-input-error :messages="$errors->get('ghana_card')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="admin-create-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Create') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Edit Modal -->
    @if ($showEditModal)
        <x-college.modal name="admin-edit" :title="__('Edit Administrator')" :show="true" maxWidth="lg" livewireSynced>
            <form id="admin-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit-lastname" :value="__('Last Name')" />
                    <x-text-input id="edit-lastname" type="text" class="mt-1 block w-full" wire:model="lastname" required />
                    <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-othernames" :value="__('Other Names')" />
                    <x-text-input id="edit-othernames" type="text" class="mt-1 block w-full" wire:model="othernames" required />
                    <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
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
                <div>
                    <x-input-label for="edit-type" :value="__('Administrative Role')" />
                    <select id="edit-type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Role…') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-position_title" :value="__('Position Title')" />
                    <x-text-input id="edit-position_title" type="text" class="mt-1 block w-full" wire:model="position_title" />
                    <x-input-error :messages="$errors->get('position_title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-department_id" :value="__('Department')" />
                    <select id="edit-department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-faculty_id" :value="__('Faculty')" />
                    <select id="edit-faculty_id" wire:model="faculty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select Faculty…') }}</option>
                        @foreach ($faculties as $fac)
                            <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-phone_number" :value="__('Phone Number')" />
                    <x-text-input id="edit-phone_number" type="text" class="mt-1 block w-full" wire:model="phone_number" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-gender" :value="__('Gender')" />
                    <select id="edit-gender" wire:model="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-date_of_appointment" :value="__('Date of Appointment')" />
                    <x-text-input id="edit-date_of_appointment" type="date" class="mt-1 block w-full" wire:model="date_of_appointment" />
                    <x-input-error :messages="$errors->get('date_of_appointment')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-ghana_card" :value="__('Ghana Card PIN')" />
                    <x-text-input id="edit-ghana_card" type="text" class="mt-1 block w-full" wire:model="ghana_card" />
                    <x-input-error :messages="$errors->get('ghana_card')" class="mt-1" />
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
                <button type="submit" form="admin-edit-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Deactivate Modal -->
    @if ($showDeactivateModal)
        <x-college.modal name="admin-deactivate" :title="__('Deactivate Administrator?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('They will no longer be able to sign in, and their administrative profile will be marked inactive.') }}
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
