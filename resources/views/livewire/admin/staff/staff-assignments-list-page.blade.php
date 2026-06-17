<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-create-assignment-modal.window="$wire.openCreateModal()"
>
    <x-slot name="headerActions">
        <button
            type="button"
            x-on:click="$dispatch('open-create-assignment-modal')"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
            <i class="fa-solid fa-plus me-2"></i>
            {{ __('Create Assignment') }}
        </button>
    </x-slot>

    <!-- Filters Section -->
    <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-2 md:grid-cols-4">
        <!-- Search -->
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input
                id="search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Name, staff no, office, title…') }}"
                class="mt-1 block w-full text-sm"
            />
        </div>

        <!-- Department Filter -->
        <div>
            <x-input-label for="filterDepartment" :value="__('Department')" />
            <select
                id="filterDepartment"
                wire:model.live="filterDepartment"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"
            >
                <option value="all">{{ __('All Departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Role Filter -->
        <div>
            <x-input-label for="filterRole" :value="__('Administrative Role')" />
            <select
                id="filterRole"
                wire:model.live="filterRole"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"
            >
                <option value="all">{{ __('All Roles') }}</option>
                @foreach ($roleOptions as $option)
                    <option value="{{ $option->name }}">
                        {{ $option->display_name ?: (strtolower($option->name) === 'it_support' ? 'IT Support' : ucwords(str_replace('_', ' ', $option->name))) }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <x-input-label for="filterStatus" :value="__('Status')" />
            <select
                id="filterStatus"
                wire:model.live="filterStatus"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"
            >
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
                <option value="ended">{{ __('Ended') }}</option>
            </select>
        </div>
    </div>

    <!-- Table Section -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Staff Member') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Position & Office') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Date Assigned') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="sa-{{ $row->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $row->staff?->name ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $row->staff?->email }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ __('Staff No:') }} {{ $row->staff?->username }}
                                </div>
                                @php
                                    $activeRoles = $row->staff?->staffRoles->where('status', 'active') ?? collect();
                                @endphp
                                @if ($activeRoles->isNotEmpty())
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach ($activeRoles as $sr)
                                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-950/40 dark:text-purple-300 dark:ring-purple-700/30" title="{{ $sr->description }}">
                                                <i class="fa-solid fa-shield-halved me-1 text-[10px]"></i>
                                                {{ $sr->roleModel?->display_name ?? (strtolower($sr->role) === 'it_support' ? 'IT Support' : ucwords(str_replace('_', ' ', $sr->role))) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->department?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $row->position_title ?? '—' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Office:') }} {{ $row->office ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->assignment_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($row->status === 'active')
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950/40 dark:text-green-300">
                                        {{ __('Active') }}
                                    </span>
                                @elseif ($row->status === 'ended')
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300">
                                        {{ __('Ended') }}
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
                                    @if ($row->staff?->staffRoles->where('status', 'active')->isNotEmpty())
                                        <button
                                            type="button"
                                            wire:click="openRevokeRoleModal({{ $row->id }})"
                                            class="text-red-600 hover:text-red-950 dark:text-red-400 dark:hover:text-red-200"
                                            title="{{ __('Revoke Administrative Role') }}"
                                        >
                                            <i class="fa-solid fa-user-minus text-lg"></i>
                                        </button>
                                    @endif
                                    @if ($row->status === 'active')
                                        <button
                                            type="button"
                                            wire:click="openEndModal({{ $row->id }})"
                                            class="text-amber-600 hover:text-amber-950 dark:text-amber-400 dark:hover:text-amber-200"
                                            title="{{ __('End Assignment') }}"
                                        >
                                            <i class="fa-solid fa-stop text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No staff office assignments yet.') }}</p>
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

    <!-- Create Modal -->
    @if ($showCreateModal)
        <x-college.modal name="sa-create" :title="__('Create Staff Assignment')" :show="true" maxWidth="lg" livewireSynced>
            <form id="sa-create-form" wire:submit.prevent="saveCreate" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="staff_id" :value="__('Staff User')" />
                    <select id="staff_id" wire:model="staff_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Staff Member…') }}</option>
                        @foreach ($staffUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->username ?? $u->email }} @if($u->name) — {{ $u->name }} @endif</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="office" :value="__('Office / Room')" />
                    <x-text-input id="office" type="text" class="mt-1 block w-full" wire:model="office" required placeholder="{{ __('e.g. Room 10B') }}" />
                    <x-input-error :messages="$errors->get('office')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="position_title" :value="__('Position Title')" />
                    <x-text-input id="position_title" type="text" class="mt-1 block w-full" wire:model="position_title" required placeholder="{{ __('e.g. Accounts Officer') }}" />
                    <x-input-error :messages="$errors->get('position_title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="assignment_date" :value="__('Assignment Date')" />
                    <x-text-input id="assignment_date" type="date" class="mt-1 block w-full" wire:model="assignment_date" />
                    <x-input-error :messages="$errors->get('assignment_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="ended">{{ __('Ended') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

                <!-- Combined Administrative Role Section -->
                <div class="sm:col-span-2">
                    <hr class="my-2 border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('System Administrative Role (Optional)') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Assign a system-level administrative security role and permissions to this staff member.') }}</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="role" :value="__('Administrative Role')" />
                    <select id="role" wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($roleOptions as $option)
                            <option value="{{ $option->name }}">
                                {{ $option->display_name ?: (strtolower($option->name) === 'it_support' ? 'IT Support' : ucwords(str_replace('_', ' ', $option->name))) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="role_description" :value="__('Role Description (optional)')" />
                    <textarea id="role_description" wire:model="role_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
                    <x-input-error :messages="$errors->get('role_description')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="role_status" :value="__('Role Access Status')" />
                    <select id="role_status" wire:model="role_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('role_status')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="sa-create-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Edit Modal -->
    @if ($showEditModal)
        <x-college.modal name="sa-edit" :title="__('Edit Assignment')" :show="true" maxWidth="lg" livewireSynced>
            <form id="sa-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="edit-staff_id" :value="__('Staff User')" />
                    <select id="edit-staff_id" wire:model="staff_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Staff Member…') }}</option>
                        @foreach ($staffUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->username ?? $u->email }} @if($u->name) — {{ $u->name }} @endif</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-department_id" :value="__('Department')" />
                    <select id="edit-department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Department…') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-office" :value="__('Office / Room')" />
                    <x-text-input id="edit-office" type="text" class="mt-1 block w-full" wire:model="office" required />
                    <x-input-error :messages="$errors->get('office')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-position_title" :value="__('Position Title')" />
                    <x-text-input id="edit-position_title" type="text" class="mt-1 block w-full" wire:model="position_title" required />
                    <x-input-error :messages="$errors->get('position_title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-assignment_date" :value="__('Assignment Date')" />
                    <x-text-input id="edit-assignment_date" type="date" class="mt-1 block w-full" wire:model="assignment_date" />
                    <x-input-error :messages="$errors->get('assignment_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-status" :value="__('Status')" />
                    <select id="edit-status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="ended">{{ __('Ended') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

                <!-- Combined Administrative Role Section -->
                <div class="sm:col-span-2">
                    <hr class="my-2 border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('System Administrative Role (Optional)') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Assign a system-level administrative security role and permissions to this staff member.') }}</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="edit-role" :value="__('Administrative Role')" />
                    <select id="edit-role" wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($roleOptions as $option)
                            <option value="{{ $option->name }}">
                                {{ $option->display_name ?: (strtolower($option->name) === 'it_support' ? 'IT Support' : ucwords(str_replace('_', ' ', $option->name))) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="edit-role_description" :value="__('Role Description (optional)')" />
                    <textarea id="edit-role_description" wire:model="role_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
                    <x-input-error :messages="$errors->get('role_description')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="edit-role_status" :value="__('Role Access Status')" />
                    <select id="edit-role_status" wire:model="role_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('role_status')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="sa-edit-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- End Modal -->
    @if ($showEndModal)
        <x-college.modal name="sa-end" :title="__('End this assignment?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('The assignment will be marked as ended.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeEndModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmEnd" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                    {{ __('End assignment') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Revoke Role Modal -->
    @if ($showRevokeRoleModal)
        <x-college.modal name="sa-revoke-role" :title="__('Revoke Administrative Role?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('This will revoke the system administrative role and permissions from this staff user (marking the role as inactive).') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeRevokeRoleModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmRevokeRole" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Revoke Role') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
