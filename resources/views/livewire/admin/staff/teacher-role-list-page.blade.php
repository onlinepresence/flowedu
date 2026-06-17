<div class="mx-auto max-w-7xl space-y-6">
    <!-- Tabbed deck header -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
                type="button"
                wire:click="switchTab('assignments')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold {{ $activeTab === 'assignments' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <i class="fa-solid fa-user-shield mr-1.5"></i>
                {{ __('Role Assignments') }}
            </button>
            <button
                type="button"
                wire:click="switchTab('definitions')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold {{ $activeTab === 'definitions' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <i class="fa-solid fa-sliders mr-1.5"></i>
                {{ __('Role Definitions') }}
            </button>
        </nav>
    </div>

    <!-- Assignments Tab Content -->
    @if ($activeTab === 'assignments')
        <div class="space-y-4">
            <div class="flex items-center justify-end">
                <button
                    type="button"
                    wire:click="openAssignModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    <i class="fa-solid fa-plus"></i>
                    {{ __('Assign Role') }}
                </button>
            </div>

            <!-- Table Card -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Teacher') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned Role') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program Scope') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned Date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($rows as $row)
                                <tr wire:key="tr-{{ $row->id }}">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $row->teacher?->lastname }} {{ $row->teacher?->othernames }}
                                        @if($row->teacher?->staff_id)
                                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->teacher->staff_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $matchedDef = $portalRoles->firstWhere('name', $row->role);
                                        @endphp
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $matchedDef?->display_name ?? ucfirst($row->role) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $row->description }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $row->program?->name ?? __('Global Portal') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($row->status === 'active')
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">
                                                {{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-300">
                                                {{ __('Revoked') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300">
                                        {{ $row->assigned_date?->format('Y-m-d') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <button
                                                type="button"
                                                wire:click="openEditModal({{ $row->id }})"
                                                title="{{ __('Edit Assignment') }}"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                <i class="fa-solid fa-pencil fa-lg"></i>
                                            </button>
                                            @if ($row->status === 'active')
                                                <button
                                                    type="button"
                                                    wire:click="openRevokeModal({{ $row->id }})"
                                                    title="{{ __('Revoke Access') }}"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    <i class="fa-solid fa-ban fa-lg"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No roles assigned yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $rows->links() }}</div>
            </div>
        </div>
    @endif

    <!-- Definitions Tab Content -->
    @if ($activeTab === 'definitions')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Panel: List of Custom Role Definitions -->
            <div class="lg:col-span-2 space-y-4">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Role Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Description') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Permissions') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($portalRoles as $roleDef)
                                    <tr wire:key="def-{{ $roleDef->id }}" class="{{ $editingDefId === $roleDef->id ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : '' }}">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $roleDef->display_name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $roleDef->description ?? __('No description provided.') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ((array) $roleDef->permissions as $p)
                                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                        {{ ucfirst($p) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                <button
                                                    type="button"
                                                    wire:click="startEditDefinition({{ $roleDef->id }})"
                                                    title="{{ __('Edit Definition') }}"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                >
                                                    <i class="fa-solid fa-pencil fa-lg"></i>
                                                </button>
                                                @if ($roleDef->name !== 'lecturer')
                                                    <button
                                                        type="button"
                                                        wire:click="deleteDefinition({{ $roleDef->id }})"
                                                        title="{{ __('Delete Definition') }}"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    >
                                                        <i class="fa-solid fa-trash-can fa-lg"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Role Definition Form -->
            <div class="lg:col-span-1">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $isEditingDef ? __('Update Definition') : __('New Role Definition') }}
                    </h2>

                    <form wire:submit.prevent="saveDefinition" class="space-y-4">


                        <!-- Display Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-750 dark:text-gray-305">{{ __('Display Name') }}</label>
                            <input
                                wire:model="defDisplayName"
                                type="text"
                                placeholder="{{ __('e.g. Class Tutor') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                            @error('defDisplayName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <!-- Permissions Checklist -->
                        <div>
                            <label class="block text-sm font-medium text-gray-750 dark:text-gray-305 mb-2">{{ __('Allowed Portal Modules') }}</label>
                            <div class="space-y-2 rounded-lg border border-gray-150 p-3 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                                @foreach (['courses' => __('My Courses & Timetables'), 'students' => __('Student Lists & Attendance'), 'assessments' => __('Grading & Results Entry'), 'communication' => __('Announcements & Messaging')] as $pCode => $pName)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input
                                            wire:model="defPermissions"
                                            value="{{ $pCode }}"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                        {{ $pName }}
                                    </label>
                                @endforeach
                            </div>
                            @error('defPermissions') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-750 dark:text-gray-305">{{ __('Description') }}</label>
                            <textarea
                                wire:model="defDescription"
                                rows="3"
                                placeholder="{{ __('Describe the purpose of this role…') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            ></textarea>
                            @error('defDescription') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2 pt-2">
                            @if ($isEditingDef)
                                <button
                                    type="button"
                                    wire:click="resetDefinitionForm"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    {{ __('Cancel') }}
                                </button>
                            @endif
                            <button
                                type="submit"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 flex items-center gap-1"
                            >
                                <i class="fa-solid fa-check"></i>
                                {{ $isEditingDef ? __('Save Changes') : __('Create Role') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Assign Role Modal -->
    @if ($showAssignModal)
        <x-college.modal name="tr-assign" :title="__('Assign Role to Lecturer')" :show="true" maxWidth="lg" livewireSynced>
            <form id="tr-assign-form" wire:submit.prevent="saveAssign" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Teacher') }}</label>
                    <select wire:model="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('Select Lecturer…') }}</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->lastname }} {{ $t->othernames }} @if($t->staff_id) ({{ $t->staff_id }}) @endif</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Role Profile') }}</label>
                    <select wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('Select Role…') }}</option>
                        @foreach ($portalRoles as $roleDef)
                            <option value="{{ $roleDef->name }}">{{ $roleDef->display_name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Programme Scope (Optional)') }}</label>
                    <select wire:model="program_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('All Programs (Global)') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Scope Details / Custom Description') }}</label>
                    <textarea wire:model="description" rows="2" placeholder="{{ __('e.g. Handling Year 1 coordinations') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Assignment Date') }}</label>
                    <input wire:model="assigned_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                    @error('assigned_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeAssignModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="tr-assign-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Assign') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Edit Assignment Modal -->
    @if ($showEditModal)
        <x-college.modal name="tr-edit" :title="__('Edit Role Assignment')" :show="true" maxWidth="lg" livewireSynced>
            <form id="tr-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Teacher') }}</label>
                    <select wire:model="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('Select Lecturer…') }}</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->lastname }} {{ $t->othernames }} @if($t->staff_id) ({{ $t->staff_id }}) @endif</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Role Profile') }}</label>
                    <select wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('Select Role…') }}</option>
                        @foreach ($portalRoles as $roleDef)
                            <option value="{{ $roleDef->name }}">{{ $roleDef->display_name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Programme Scope (Optional)') }}</label>
                    <select wire:model="program_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('All Programs (Global)') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Scope Details / Custom Description') }}</label>
                    <textarea wire:model="description" rows="2" placeholder="{{ __('e.g. Handling Year 1 coordinations') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Assignment Date') }}</label>
                    <input wire:model="assigned_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                    @error('assigned_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="tr-edit-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Revoke Assignment Modal -->
    @if ($showRevokeModal)
        <x-college.modal name="tr-revoke" :title="__('Revoke Role Assignment?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to deactivate this role assignment? The lecturer will instantly lose permissions granted by this profile.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeRevokeModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmRevoke" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Revoke Role') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
