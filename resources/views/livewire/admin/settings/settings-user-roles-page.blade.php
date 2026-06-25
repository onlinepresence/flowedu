@php
    $protectedNames = ['owner', 'system_admin'];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-905/40 dark:bg-green-950/40 dark:text-green-200 shadow-sm" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @error('delete')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-200 shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $message }}
        </div>
    @enderror

    <!-- Filters & Action Bar -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <button 
                    type="button" 
                    wire:click="openCreate" 
                    class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"
                >
                    <i class="fa-solid fa-plus mr-2"></i>{{ __('Add New Role') }}
                </button>
                @if (auth()->user()?->isAdminOwner())
                    <button 
                        type="button" 
                        wire:click="syncRoles" 
                        wire:loading.attr="disabled" 
                        class="inline-flex items-center gap-2 justify-center rounded-lg border border-gray-305 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"
                    >
                        <i class="fa-solid fa-rotate" wire:loading.class="fa-spin" wire:target="syncRoles"></i>
                        <span wire:loading.remove wire:target="syncRoles">{{ __('Sync Roles & Permissions') }}</span>
                        <span wire:loading wire:target="syncRoles">{{ __('Syncing...') }}</span>
                    </button>
                @endif
            </div>
            
            <div class="w-full sm:w-64">
                <label for="role-filter" class="sr-only">{{ __('Filter by type') }}</label>
                <select 
                    wire:model.live="roleFilter" 
                    id="role-filter" 
                    class="block w-full rounded-lg border-gray-300 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                >
                    <option value="all">{{ __('All user role types') }}</option>
                    @foreach ($adminTypes as $type)
                        <option value="{{ $type->name }}">{{ $type->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Roles Table Card -->
    <x-card class="overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Role Name') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('User Type') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Permissions Count') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date Created') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        @php
                            $isProtected = in_array($role->name, $protectedNames, true);
                            $permCount = is_array($role->permissions) ? count($role->permissions) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/40 transition-colors" wire:key="role-row-{{ $role->id }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ $role->display_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300 font-medium capitalize">{{ $roleTypeLabels[$role->role_name] ?? $role->role_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-950/30 dark:text-purple-400">
                                    <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                    {{ $permCount }} {{ __('Slugs') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300 font-mono">{{ $role->created_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button 
                                        type="button" 
                                        wire:click="openEdit({{ $role->id }})" 
                                        class="inline-flex items-center gap-1 font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-305"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>{{ __('Edit') }}
                                    </button>
                                    @if (! $isProtected)
                                        <span class="text-gray-300 dark:text-gray-700">|</span>
                                        <button 
                                            type="button" 
                                            wire:click="confirmDelete({{ $role->id }})" 
                                            class="inline-flex items-center gap-1 font-bold text-red-600 hover:text-red-705"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>{{ __('Delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <i class="fa-regular fa-folder-open text-3xl text-gray-300 dark:text-gray-650 mb-3 block"></i>
                                <p class="font-semibold">{{ __('No roles match the selected filter.') }}</p>
                                <button
                                    type="button"
                                    wire:click="openCreate"
                                    class="mt-4 inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-750"
                                >{{ __('Add New Role') }}</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($showRoleModal)
        <x-college.modal
            name="settings-user-roles-form"
            :title="$isEditing ? __('Edit User Role') : __('Create User Role')"
            :show="true"
            maxWidth="3xl"
            livewireSynced
        >
            <form id="settings-user-roles-form-fields" wire:submit="saveRole" class="space-y-6 p-1">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Display Name')" />
                        <x-text-input wire:model="display_name" id="display_name" type="text" required maxlength="255" class="mt-1.5 block w-full" placeholder="e.g. Finance Assistant" />
                        @error('display_name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    @if (! $isEditing)
                        <div>
                            <x-input-label :value="__('User Role Type')" />
                            <x-select-input wire:model="role_name" id="role_name" class="mt-1.5 block w-full">
                                @foreach ($adminTypes as $type)
                                    <option value="{{ $type->name }}">{{ $type->display_name }}</option>
                                @endforeach
                            </x-select-input>
                            @error('role_name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label :value="__('System Name (Optional)')" />
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Unique identifier generated automatically if left blank. Cannot be modified later.') }}</p>
                            <x-text-input wire:model="name" id="name" type="text" maxlength="255" class="mt-1.5 block w-full font-mono text-sm" placeholder="e.g. finance-assistant" />
                            @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="sm:col-span-2 rounded-lg bg-gray-50 p-4 border border-gray-200/60 dark:bg-gray-900/40 dark:border-gray-700/60">
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-650 dark:text-gray-300">
                                <div>
                                    <span class="font-bold text-gray-500 uppercase tracking-wider text-xs block">{{ __('System Code / Name') }}</span>
                                    <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">{{ $name }}</span>
                                </div>
                                <div>
                                    <span class="font-bold text-gray-500 uppercase tracking-wider text-xs block">{{ __('Role Type Mapping') }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white capitalize">{{ $roleTypeLabels[$role_name] ?? $role_name }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Categorized Permissions & Radio Configurator -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-5" x-data="{
                    search: '',
                    selected: @entangle('selectedPermissions'),
                    hasPerm(p) {
                        return Array.isArray(this.selected) && this.selected.includes(p);
                    },
                    setRadioPerm(viewSlug, writeSlug, mode) {
                        if (!Array.isArray(this.selected)) this.selected = [];
                        this.selected = this.selected.filter(item => item !== viewSlug && item !== writeSlug);
                        if (mode === 'view') {
                            this.selected.push(viewSlug);
                        } else if (mode === 'write') {
                            this.selected.push(writeSlug);
                        }
                    },
                    getRadioPerm(viewSlug, writeSlug) {
                        if (this.hasPerm(writeSlug)) return 'write';
                        if (this.hasPerm(viewSlug)) return 'view';
                        return 'none';
                    }
                }">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1 uppercase tracking-wider">{{ __('Core Capability Settings') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('Define access privilege levels for primary institutional administration modules.') }}</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Student Records Card -->
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-450 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-graduate text-purple-500"></i>
                                {{ __('Student Records Access') }}
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="student_access" value="none" 
                                           :checked="getRadioPerm('student_management_view', 'student_management') === 'none'"
                                           @change="setRadioPerm('student_management_view', 'student_management', 'none')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('No Access') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="student_access" value="view" 
                                           :checked="getRadioPerm('student_management_view', 'student_management') === 'view'"
                                           @change="setRadioPerm('student_management_view', 'student_management', 'view')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('Read-Only (View)') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="student_access" value="write" 
                                           :checked="getRadioPerm('student_management_view', 'student_management') === 'write'"
                                           @change="setRadioPerm('student_management_view', 'student_management', 'write')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ __('Full Access (CRUD)') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Teacher Management Card -->
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-455 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-chalkboard-user text-purple-500"></i>
                                {{ __('Teacher & Staff Access') }}
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="teacher_access" value="none" 
                                           :checked="getRadioPerm('teacher_management_view', 'teacher_management') === 'none'"
                                           @change="setRadioPerm('teacher_management_view', 'teacher_management', 'none')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('No Access') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="teacher_access" value="view" 
                                           :checked="getRadioPerm('teacher_management_view', 'teacher_management') === 'view'"
                                           @change="setRadioPerm('teacher_management_view', 'teacher_management', 'view')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('Read-Only (View)') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="teacher_access" value="write" 
                                           :checked="getRadioPerm('teacher_management_view', 'teacher_management') === 'write'"
                                           @change="setRadioPerm('teacher_management_view', 'teacher_management', 'write')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ __('Full Access (CRUD)') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Academic Structures Card -->
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-455 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-book-open text-purple-500"></i>
                                {{ __('Academic Structure Access') }}
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="academic_access" value="none" 
                                           :checked="getRadioPerm('course_management_view', 'course_management') === 'none'"
                                           @change="setRadioPerm('course_management_view', 'course_management', 'none')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('No Access') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="academic_access" value="view" 
                                           :checked="getRadioPerm('course_management_view', 'course_management') === 'view'"
                                           @change="setRadioPerm('course_management_view', 'course_management', 'view')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('Read-Only (View)') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="academic_access" value="write" 
                                           :checked="getRadioPerm('course_management_view', 'course_management') === 'write'"
                                           @change="setRadioPerm('course_management_view', 'course_management', 'write')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ __('Full Access (CRUD)') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Finance Module Card -->
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-455 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-scale-balanced text-purple-500"></i>
                                {{ __('Financial Management Access') }}
                            </h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="finance_access" value="none" 
                                           :checked="getRadioPerm('view_financial_data', 'manage_financial_data') === 'none'"
                                           @change="setRadioPerm('view_financial_data', 'manage_financial_data', 'none')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('No Access') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="finance_access" value="view" 
                                           :checked="getRadioPerm('view_financial_data', 'manage_financial_data') === 'view'"
                                           @change="setRadioPerm('view_financial_data', 'manage_financial_data', 'view')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('Read-Only (View)') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="finance_access" value="write" 
                                           :checked="getRadioPerm('view_financial_data', 'manage_financial_data') === 'write'"
                                           @change="setRadioPerm('view_financial_data', 'manage_financial_data', 'write')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ __('Full Access (CRUD)') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Staff Leaves Card -->
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 md:col-span-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-455 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-calendar-minus text-purple-500"></i>
                                {{ __('Staff Leaves Access') }}
                            </h4>
                            <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="leave_access" value="none" 
                                           :checked="getRadioPerm('view_staff_leaves', 'manage_staff_leaves') === 'none'"
                                           @change="setRadioPerm('view_staff_leaves', 'manage_staff_leaves', 'none')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('No Access') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="leave_access" value="view" 
                                           :checked="getRadioPerm('view_staff_leaves', 'manage_staff_leaves') === 'view'"
                                           @change="setRadioPerm('view_staff_leaves', 'manage_staff_leaves', 'view')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span>{{ __('Read-Only (View)') }}</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-gray-750 dark:text-gray-300">
                                    <input type="radio" name="leave_access" value="write" 
                                           :checked="getRadioPerm('view_staff_leaves', 'manage_staff_leaves') === 'write'"
                                           @change="setRadioPerm('view_staff_leaves', 'manage_staff_leaves', 'write')"
                                           class="h-4 w-4 border-gray-300 text-purple-650 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900" />
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ __('Full Access (CRUD)') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modular Navigation Checkboxes -->
                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1 uppercase tracking-wider">{{ __('Modular Navigation & Action Permissions') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('Configure page-level navigation and specialized functionality gates.') }}</p>
                        
                        <div class="mb-4">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="fa-solid fa-magnifying-glass text-gray-450"></i>
                                </div>
                                <input 
                                    x-model="search" 
                                    type="search" 
                                    placeholder="{{ __('Type keywords to filter navigation permissions...') }}" 
                                    class="block w-full rounded-lg border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white placeholder-gray-400"
                                />
                            </div>
                        </div>
                        
                        <div class="space-y-6 max-h-96 overflow-y-auto pr-1">
                            @foreach ($permissionCategories as $catName => $perms)
                                <div class="space-y-3" x-show="search === '' || @js(array_values($perms)).some(label => label.toLowerCase().includes(search.toLowerCase()))">
                                    <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800 pb-1.5">{{ $catName }}</h4>
                                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($perms as $slug => $label)
                                            <label 
                                                x-show="search === '' || '{{ strtolower(addslashes($label)) }}'.includes(search.toLowerCase())"
                                                class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-gray-200 p-2.5 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/40 hover:border-purple-200 dark:hover:border-purple-900/60 transition" 
                                                wire:key="perm-{{ $slug }}"
                                            >
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="selectedPermissions" 
                                                    value="{{ $slug }}" 
                                                    class="mt-0.5 rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900" 
                                                />
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-305 leading-tight">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @error('selectedPermissions') <p class="mt-1.5 text-sm text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    @error('selectedPermissions.*') <p class="mt-1.5 text-sm text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeRoleModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                <button type="submit" form="settings-user-roles-form-fields" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition">{{ __('Save Role') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    @if ($showDeleteModal)
        <x-college.modal name="settings-user-roles-delete" :title="__('Delete Role Confirmation')" :show="true" maxWidth="md" livewireSynced>
            <div class="p-1">
                <p class="text-sm text-gray-650 dark:text-gray-400 font-semibold">{{ __('Are you sure you want to delete this custom user role? This action is permanent and cannot be undone. Ensure no administrative accounts are actively assigned to this role before continuing.') }}</p>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="closeDeleteModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="button" wire:click="deleteRole" class="rounded-lg bg-red-650 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">{{ __('Confirm Delete') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
