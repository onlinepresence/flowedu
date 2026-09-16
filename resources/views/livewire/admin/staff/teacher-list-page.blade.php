<div
    class="mx-auto max-w-7xl space-y-6"
>
    <x-slot name="headerActions">
        <div class="flex items-center gap-2">
            <x-college.button
                variant="secondary"
                x-on:click="$dispatch('open-modal', 't-import'); $wire.openImportModal()"
            >
                <i class="fa-solid fa-file-import"></i>
                {{ __('Upload teachers') }}
            </x-college.button>
            <x-college.button
                variant="primary"
                x-on:click="$dispatch('open-modal', 't-create'); $wire.openCreateModal()"
            >
                <i class="fa-solid fa-plus"></i>
                {{ __('Add teacher') }}
            </x-college.button>
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
            {{-- Single live boolean filter: styled switch on a real checkbox keeps
                wire:model + keyboard/a11y intact (no JS to break in Livewire). --}}
            <label for="showDeleted" class="inline-flex cursor-pointer select-none items-center gap-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                <input
                    wire:model.live="showDeleted"
                    id="showDeleted"
                    type="checkbox"
                    class="peer sr-only"
                />
                <span aria-hidden="true" class="relative h-5 w-9 shrink-0 rounded-full bg-gray-200 transition-colors duration-200 peer-checked:bg-indigo-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-600 dark:bg-gray-700 dark:peer-checked:bg-indigo-500 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-200 peer-checked:after:translate-x-4"></span>
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
                                <div class="flex items-center gap-3">
                                    <x-college.avatar :src="$t->profile_pic ? asset('storage/' . $t->profile_pic) : null" :name="$t->lastname . ' ' . $t->othernames" size="sm" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $t->lastname }} {{ $t->othernames }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t->user?->email }}</div>
                                    </div>
                                </div>
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
                                        wire:loading.attr="disabled"
                                        title="{{ __('Restore Teacher') }}"
                                        class="text-green-600 hover:text-green-900 disabled:opacity-50 dark:text-green-400 dark:hover:text-green-300"
                                    >
                                        <i class="fa-solid fa-rotate-left fa-lg"></i>
                                    </button>
                                @else
                                    <div class="flex justify-end gap-3">
                                        <button
                                            type="button"
                                            x-on:click="$dispatch('open-modal', 't-edit')"
                                            wire:click="openEditModal({{ $t->id }})"
                                            title="{{ __('Edit') }}"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            <i class="fa-solid fa-pencil fa-lg"></i>
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="$dispatch('open-modal', 't-delete')"
                                            wire:click="openDeleteModal({{ $t->id }})"
                                            title="{{ __('Archive') }}"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
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
                                    <x-college.button variant="secondary" x-on:click="$dispatch('open-modal', 't-import'); $wire.openImportModal()">
                                        {{ __('Upload teachers') }}
                                    </x-college.button>
                                    <x-college.button variant="primary" x-on:click="$dispatch('open-modal', 't-create'); $wire.openCreateModal()">
                                        {{ __('Add teacher') }}
                                    </x-college.button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $teachers->links() }}</div>
    </div>

    <!-- Create Teacher Modal: always rendered, opens instantly via Alpine;
         Livewire resets the form in the background (no server wait to see it). -->
    <x-college.modal name="t-create" :title="__('Add Teacher Account')" :show="$showCreateModal" maxWidth="lg">
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
                <x-college.button variant="secondary" x-on:click="$dispatch('close-modal', 't-create')" wire:click="closeCreateModal">{{ __('Cancel') }}</x-college.button>
                <x-college.button variant="primary" type="submit" form="t-create-form">{{ __('Create') }}</x-college.button>
            </x-slot:footer>
        </x-college.modal>

    <!-- Import Modal: always rendered for instant open. FilePond root is
         wire:ignore + re-bound on morph (see filepond-college.js), and the pond
         is cleared programmatically on close/success so uploads never go stale. -->
    <x-college.modal name="t-import" :title="__('Upload Teachers (Spreadsheet)')" :show="$showImportModal" maxWidth="lg">
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
                <x-college.button variant="secondary" x-on:click="$dispatch('close-modal', 't-import')" wire:click="closeImportModal">{{ __('Close') }}</x-college.button>
                <x-college.button variant="primary" wire:click="runImport" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="runImport"><i class="fa-solid fa-cloud-arrow-up mr-1"></i> {{ __('Import') }}</span>
                    <span wire:loading wire:target="runImport"><i class="fa-solid fa-spinner fa-spin mr-1"></i> {{ __('Processing…') }}</span>
                </x-college.button>
            </x-slot:footer>
        </x-college.modal>

    <!-- Edit Teacher Modal: shell opens instantly; the form area shows a skeleton
         while Livewire loads the teacher (no stale-record flash). -->
    <x-college.modal name="t-edit" :title="__('Edit Teacher Account')" :show="$showEditModal" maxWidth="lg">
        <div wire:loading.delay wire:target="openEditModal" class="grid animate-pulse gap-4 sm:grid-cols-2" aria-hidden="true">
            <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
            <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
            <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-700 sm:col-span-2"></div>
            <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
            <div class="h-10 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
        </div>
        <div wire:loading.remove wire:target="openEditModal">
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
        </div>
            <x-slot:footer>
                <x-college.button variant="secondary" x-on:click="$dispatch('close-modal', 't-edit')" wire:click="closeEditModal">{{ __('Cancel') }}</x-college.button>
                <x-college.button variant="primary" type="submit" form="t-edit-form">{{ __('Save') }}</x-college.button>
            </x-slot:footer>
        </x-college.modal>

    <!-- Delete Confirmation Modal: always rendered; id is set in background. -->
    <x-college.modal name="t-delete" :title="__('Archive Teacher Account?')" :show="$showDeleteModal" maxWidth="md">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to archive this teacher account? The database record will be preserved (soft deleted) but the associated user credentials will be deactivated immediately, preventing them from logging in.') }}
            </p>
            <x-slot:footer>
                <x-college.button variant="secondary" x-on:click="$dispatch('close-modal', 't-delete')" wire:click="closeDeleteModal">
                    {{ __('Cancel') }}
                </x-college.button>
                <x-college.button variant="danger" wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete">
                    <span wire:loading.remove wire:target="confirmDelete">{{ __('Archive Account') }}</span>
                    <span wire:loading wire:target="confirmDelete">{{ __('Archiving...') }}</span>
                </x-college.button>
            </x-slot:footer>
        </x-college.modal>
</div>
