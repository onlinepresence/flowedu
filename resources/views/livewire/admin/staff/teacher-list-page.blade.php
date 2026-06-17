<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-import-teachers-modal.window="$wire.openImportModal()"
    x-on:open-create-teacher-modal.window="$wire.openCreateModal()"
>
    <x-slot name="headerActions">
        <div class="flex items-center gap-2" x-data>
            <button
                type="button"
                x-on:click="$dispatch('open-import-teachers-modal')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250 dark:hover:bg-gray-700"
            >
                <i class="fa-solid fa-file-import"></i>
                {{ __('Upload teachers') }}
            </button>
            <button
                type="button"
                x-on:click="$dispatch('open-create-teacher-modal')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                <i class="fa-solid fa-plus"></i>
                {{ __('Add teacher') }}
            </button>
        </div>
    </x-slot>

    <!-- Filters Section -->
    <x-college.filter-card cols="4">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name, email, staff ID...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterDepartment" :value="__('Department')" />
            <select id="filterDepartment" wire:model.live="filterDepartment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Departments') }}</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterStatus" :value="__('Status')" />
            <select id="filterStatus" wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active Accounts') }}</option>
                <option value="inactive">{{ __('Inactive Accounts') }}</option>
            </select>
        </div>
        <div class="flex items-end pb-2">
            <label for="showDeleted" class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input
                    wire:model.live="showDeleted"
                    id="showDeleted"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                />
                <span>{{ __('Show Archived') }}</span>
            </label>
        </div>
    </x-college.filter-card>

    <!-- Table content -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Staff ID') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($teachers as $t)
                        <tr wire:key="t-{{ $t->id }}" class="{{ $t->trashed() ? 'bg-amber-50/50 dark:bg-amber-950/20' : '' }}">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $t->lastname }} {{ $t->othernames }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-300">
                                {{ $t->staff_id ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $t->department?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($t->trashed())
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{ __('Archived') }}
                                    </span>
                                @elseif ($t->user?->active)
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-300">
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                @if ($t->trashed())
                                    <button
                                        type="button"
                                        wire:click="restoreTeacher({{ $t->id }})"
                                        title="{{ __('Restore Teacher') }}"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                    >
                                        <i class="fa-solid fa-rotate-left fa-lg"></i>
                                    </button>
                                @else
                                    <div class="flex justify-end gap-3">
                                        <button
                                            type="button"
                                            wire:click="openEditModal({{ $t->id }})"
                                            title="{{ __('Edit') }}"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            <i class="fa-solid fa-pencil fa-lg"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="openDeleteModal({{ $t->id }})"
                                            title="{{ __('Archive') }}"
                                            class="text-red-600 hover:text-red-900 dark:text-red-450 dark:hover:text-red-355"
                                        >
                                            <i class="fa-solid fa-trash-can fa-lg"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No teachers match the filter requirements.') }}</p>
                                <div class="mt-4 flex flex-wrap justify-center gap-2">
                                    <button type="button" wire:click="openImportModal" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                                        {{ __('Upload teachers') }}
                                    </button>
                                    <button type="button" wire:click="openCreateModal" class="inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                        {{ __('Add teacher') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $teachers->links() }}</div>
    </div>

    <!-- Create Teacher Modal -->
    @if ($showCreateModal)
        <x-college.modal name="t-create" :title="__('Add Teacher Account')" :show="true" maxWidth="lg" livewireSynced>
            <form id="t-create-form" wire:submit.prevent="saveCreate" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="lastname" :value="__('Last Name')" />
                    <x-text-input id="lastname" wire:model="lastname" type="text" placeholder="{{ __('e.g. Doe') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="othernames" :value="__('Other Names')" />
                    <x-text-input id="othernames" wire:model="othernames" type="text" placeholder="{{ __('e.g. John') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="name" :value="__('Display Name')" />
                    <x-text-input id="name" wire:model="name" type="text" placeholder="{{ __('e.g. John Doe') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="staff_id" :value="__('Staff ID')" />
                    <x-text-input id="staff_id" wire:model="staff_id" type="text" placeholder="{{ __('e.g. TCH001') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" wire:model="email" type="email" autocomplete="email" placeholder="{{ __('e.g. johndoe@college.edu') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="password" :value="__('Initial Password')" />
                    <x-text-input id="password" wire:model="password" type="password" autocomplete="new-password" placeholder="••••••••" class="mt-1 block w-full text-sm" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Leave blank to use the default password: Password@1') }}
                    </p>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone_number" :value="__('Phone Number')" />
                    <x-text-input id="phone_number" wire:model="phone_number" type="text" placeholder="{{ __('e.g. +1234567890') }}" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">{{ __('Cancel') }}</button>
                <button type="submit" form="t-create-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Create') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Import Modal -->
    @if ($showImportModal)
        <x-college.modal name="t-import" :title="__('Upload Teachers (Spreadsheet)')" :show="true" maxWidth="lg" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Upload a CSV or Excel spreadsheet containing your teaching staff. The system supports upserting: duplicate Staff IDs will update existing profiles.') }}
            </p>
            <a href="{{ route('admin.staff.teachers.import-template') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-sm underline flex items-center gap-1 mt-2">
                <i class="fa-solid fa-download"></i> {{ __('Download Sample CSV Template') }}
            </a>

            <div class="mt-4">
                <x-filepond
                    field="importPath"
                    purpose="teacher_import"
                    :label="__('Spreadsheet file')"
                    accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                />
            </div>
            @if ($importErrors !== [])
                <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    <p class="font-medium">{{ __('Import notes') }}</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach (array_slice($importErrors, 0, 15) as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    @if (count($importErrors) > 15)
                        <p class="mt-2 text-xs">{{ __('Showing first 15 messages.') }}</p>
                    @endif
                </div>
            @endif
            @if ($importCreatedCount > 0)
                <p class="mt-3 text-sm font-medium text-green-700 dark:text-green-400">{{ __('Processed :n accounts.', ['n' => $importCreatedCount]) }}</p>
            @endif
            <x-slot:footer>
                <button type="button" wire:click="closeImportModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">{{ __('Close') }}</button>
                <button type="button" wire:click="runImport" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="fa-solid fa-cloud-arrow-up mr-1"></i> {{ __('Import') }}</span>
                    <span wire:loading><i class="fa-solid fa-spinner fa-spin mr-1"></i> {{ __('Processing…') }}</span>
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Edit Teacher Modal -->
    @if ($showEditModal)
        <x-college.modal name="t-edit" :title="__('Edit Teacher Account')" :show="true" maxWidth="lg" livewireSynced>
            <form id="t-edit-form" wire:submit.prevent="saveEdit" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit_lastname" :value="__('Last Name')" />
                    <x-text-input id="edit_lastname" wire:model="lastname" type="text" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_othernames" :value="__('Other Names')" />
                    <x-text-input id="edit_othernames" wire:model="othernames" type="text" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_name" :value="__('Display Name')" />
                    <x-text-input id="edit_name" wire:model="name" type="text" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_staff_id" :value="__('Staff ID')" />
                    <x-text-input id="edit_staff_id" wire:model="staff_id" type="text" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_email" :value="__('Email')" />
                    <x-text-input id="edit_email" wire:model="email" type="email" autocomplete="email" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_password" :value="__('New Password (optional)')" />
                    <x-text-input id="edit_password" wire:model="password" type="password" autocomplete="new-password" placeholder="••••••••" class="mt-1 block w-full text-sm" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Leave blank to keep the current password.') }}
                    </p>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_department_id" :value="__('Department')" />
                    <select id="edit_department_id" wire:model="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_phone_number" :value="__('Phone Number')" />
                    <x-text-input id="edit_phone_number" wire:model="phone_number" type="text" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </div>
                <div class="flex items-center gap-2 sm:col-span-2 mt-2">
                    <input wire:model="active" id="t-edit-active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900" />
                    <label for="t-edit-active" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">{{ __('Account Active') }}</label>
                    <x-input-error :messages="$errors->get('active')" class="mt-1" />
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">{{ __('Cancel') }}</button>
                <button type="submit" form="t-edit-form" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <x-college.modal name="t-delete" :title="__('Archive Teacher Account?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to archive this teacher account? The database record will be preserved (soft deleted) but the associated user credentials will be deactivated immediately, preventing them from logging in.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeDeleteModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Archive Account') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
