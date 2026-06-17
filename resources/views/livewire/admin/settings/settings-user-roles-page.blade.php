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

                <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1 uppercase tracking-wider">{{ __('Permissions Configurator') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('Toggle corresponding permission checkboxes. Use the real-time search box below to filter.') }}</p>
                    
                    <!-- Alpine Search and Checked Filters -->
                    <div x-data="{ search: '' }">
                        <div class="mb-4">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                                </div>
                                <input 
                                    x-model="search" 
                                    type="search" 
                                    placeholder="{{ __('Type permission keywords to filter...') }}" 
                                    class="block w-full rounded-lg border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white placeholder-gray-400"
                                />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 max-h-96 overflow-y-auto pr-1">
                            @foreach ($permissionLabels as $slug => $label)
                                <label 
                                    x-show="search === '' || '{{ strtolower(addslashes($label)) }}'.includes(search.toLowerCase())"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-705 dark:hover:bg-gray-800/40 hover:border-purple-200 dark:hover:border-purple-900 transition" 
                                    wire:key="perm-{{ $slug }}"
                                >
                                    <input 
                                        type="checkbox" 
                                        wire:model="selectedPermissions" 
                                        value="{{ $slug }}" 
                                        class="mt-0.5 rounded border-gray-305 text-purple-650 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900" 
                                    />
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $label }}
                                    </span>
                                </label>
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
