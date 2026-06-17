<div class="mx-auto max-w-7xl space-y-6">

    @if (count(array_filter($selectedIds)) > 0)
        <div class="flex items-center justify-between rounded-lg bg-purple-50 p-4 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-900/50 shadow-sm animate-pulse">
            <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">
                {{ __(':count student(s) selected', ['count' => count(array_filter($selectedIds))]) }}
            </span>
            <button
                type="button"
                x-on:click="$dispatch('open-modal', 'bulk-delete-confirm-modal')"
                class="inline-flex items-center gap-2 rounded bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-500 transition-colors"
            >
                <i class="fa-solid fa-trash"></i>
                {{ __('Delete Selected') }}
            </button>
        </div>
    @endif

    <x-college.filter-card cols="4">
        @if ($faculties->isNotEmpty())
            <div>
                <label for="filter-faculty" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Faculty') }}</label>
                <select wire:model.live="facultyFilter" id="filter-faculty" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                    <option value="">{{ __('All Faculties') }}</option>
                    @foreach ($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="filter-department" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Department') }}</label>
            <select wire:model.live="departmentFilter" id="filter-department" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                <option value="">{{ __('All Departments') }}</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-program" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</label>
            <select wire:model.live="programFilter" id="filter-program" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                <option value="">{{ __('All Programs') }}</option>
                @foreach ($programs as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-level" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level') }}</label>
            <select wire:model.live="levelFilter" id="filter-level" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                <option value="">{{ __('All Levels') }}</option>
                <option value="100">{{ __('Level 100') }}</option>
                <option value="200">{{ __('Level 200') }}</option>
                <option value="300">{{ __('Level 300') }}</option>
                <option value="400">{{ __('Level 400') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <x-card>
        <div class="space-y-4 -mx-6 -my-5 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end justify-between">
                <!-- Search & Status -->
                <div class="flex flex-1 flex-wrap gap-4 items-end">
                    <div class="min-w-0 flex-1 sm:max-w-md relative">
                        <label for="student-search" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search keyword') }}</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                            </div>
                            <x-text-input
                                wire:model.live.debounce.300ms="search"
                                id="student-search"
                                type="search"
                                class="block w-full pl-9 text-sm"
                                placeholder="{{ __('Search index, registration or name…') }}"
                            />
                        </div>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Approval status') }}</span>
                        <div class="inline-flex rounded-lg border border-gray-250 p-0.5 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                            <button type="button" wire:click="$set('approval', 'all')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-150 {{ $approvalFilter === 'all' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' }}">{{ __('All') }}</button>
                            <button type="button" wire:click="$set('approval', 'pending')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-150 {{ $approvalFilter === 'pending' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' }}">{{ __('Pending') }}</button>
                            <button type="button" wire:click="$set('approval', 'approved')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-150 {{ $approvalFilter === 'approved' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' }}">{{ __('Approved') }}</button>
                        </div>
                    </div>
                </div>
                <div class="shrink-0">
                    <button
                        type="button"
                        disabled
                        aria-disabled="true"
                        title="{{ __('Not implemented yet — import will be available in a future update.') }}"
                        class="inline-flex w-full cursor-not-allowed justify-center rounded-md border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-500 opacity-70 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 sm:w-auto"
                    >{{ __('Import Students') }}</button>
                </div>
            </div>
        </div>
    </x-card>

    <x-card class="overflow-hidden relative">
        {{-- Targeted Loading Overlay --}}
        <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, search, approval, facultyFilter, departmentFilter, programFilter, levelFilter" class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
            <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                <i class="fa-solid fa-circle-notch fa-spin text-purple-600 dark:text-purple-400"></i>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading students...') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto -mx-6 -my-5">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="w-10 px-6 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900" />
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Index') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Year') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($students as $student)
                        <tr wire:key="student-row-{{ $student->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <input type="checkbox" wire:model.live="selectedIds.{{ $student->id }}" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900" />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $student->index_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ trim(implode(' ', array_filter([$student->firstname, $student->othernames, $student->lastname]))) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $student->program?->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $student->current_year }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($student->approved)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('Approved') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3.5">
                                    @if (! $student->approved)
                                        <a
                                            href="{{ route('admin.approve-student', ['index_number' => $student->index_number, 'guardian' => $student->parent_guardians_count > 0 ? 1 : 0, 'id' => $student->user_id]) }}"
                                            class="text-purple-600 hover:text-purple-500 dark:text-purple-400 hover:scale-110 transition-transform"
                                            wire:navigate
                                            title="{{ __('Approve Student') }}"
                                        >
                                            <i class="fa-solid fa-user-check text-base"></i>
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('admin.students.print', ['index_number' => $student->index_number]) }}"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 hover:scale-110 transition-transform"
                                            wire:navigate
                                            title="{{ __('Print Record') }}"
                                        >
                                            <i class="fa-solid fa-print text-base"></i>
                                        </a>
                                    @endif

                                    <a
                                        href="{{ route('admin.students.show', ['index_number' => $student->index_number]) }}"
                                        class="text-blue-600 hover:text-blue-500 dark:text-blue-400 hover:scale-110 transition-transform"
                                        wire:navigate
                                        title="{{ __('View Profile') }}"
                                    >
                                        <i class="fa-solid fa-eye text-base"></i>
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="editStudent({{ $student->id }})"
                                        class="text-amber-600 hover:text-amber-500 dark:text-amber-400 hover:scale-110 transition-transform"
                                        title="{{ __('Edit Student') }}"
                                    >
                                        <i class="fa-solid fa-pen text-base"></i>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="confirmDeleteStudent({{ $student->id }})"
                                        class="text-red-655 hover:text-red-500 dark:text-red-400 hover:scale-110 transition-transform"
                                        title="{{ __('Delete Student') }}"
                                    >
                                        <i class="fa-solid fa-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <p>{{ __('No students match your criteria.') }}</p>
                                @if (trim($search) !== '' || $facultyFilter || $departmentFilter || $programFilter || $levelFilter)
                                    <x-secondary-button
                                        type="button"
                                        wire:click="$set('search', ''); $set('facultyFilter', ''); $set('departmentFilter', ''); $set('programFilter', ''); $set('levelFilter', '');"
                                        class="mt-4 px-4 py-2"
                                    >{{ __('Clear all filters') }}</x-secondary-button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $students->links() }}
        </div>
    </x-card>

    <!-- Modals -->

    <!-- Edit Student Modal -->
    <x-college.modal name="edit-student-modal" :title="__('Edit Student Information')">
        <form wire:submit.prevent="saveEdit" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 max-h-96 overflow-y-auto pr-1">
                <div>
                    <label for="edit-lastname" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Last Name') }}</label>
                    <x-text-input wire:model="editLastname" id="edit-lastname" type="text" class="block w-full" required />
                    @error('editLastname') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="edit-firstname" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('First Name') }}</label>
                    <x-text-input wire:model="editFirstname" id="edit-firstname" type="text" class="block w-full" required />
                    @error('editFirstname') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="edit-othernames" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Other Names') }}</label>
                    <x-text-input wire:model="editOthernames" id="edit-othernames" type="text" class="block w-full" />
                    @error('editOthernames') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="edit-gender" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Gender') }}</label>
                    <select wire:model="editGender" id="edit-gender" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="Male">{{ __('Male') }}</option>
                        <option value="Female">{{ __('Female') }}</option>
                    </select>
                    @error('editGender') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="edit-phone" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Phone Number') }}</label>
                    <x-text-input wire:model="editPhone" id="edit-phone" type="text" class="block w-full" required />
                    @error('editPhone') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="edit-level" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Academic Level') }}</label>
                    <select wire:model="editLevel" id="edit-level" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="100">{{ __('Level 100') }}</option>
                        <option value="200">{{ __('Level 200') }}</option>
                        <option value="300">{{ __('Level 300') }}</option>
                        <option value="400">{{ __('Level 400') }}</option>
                    </select>
                    @error('editLevel') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="edit-program" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Program') }}</label>
                    <select wire:model="editProgramId" id="edit-program" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <option value="">{{ __('Select Program') }}</option>
                        @foreach ($allPrograms as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('editProgramId') <span class="text-xs text-red-600 dark:text-red-450">{{ $message }}</span> @enderror
                </div>
            </div>

            <x-slot name="footer">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'edit-student-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                >
                    {{ __('Save Changes') }}
                </button>
            </x-slot>
        </form>
    </x-college.modal>

    <!-- Individual Delete Confirm Modal -->
    <x-college.confirm-modal
        name="delete-student-confirm-modal"
        type="danger"
        :title="__('Delete Student')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteStudent"
    >
        <p class="text-sm text-gray-500">
            {{ __('Are you sure you want to delete this student? All academic, registration, health, and user records associated with this index will be permanently removed.') }}
        </p>
    </x-college.confirm-modal>

    <!-- Bulk Delete Confirm Modal -->
    <x-college.confirm-modal
        name="bulk-delete-confirm-modal"
        type="danger"
        :title="__('Delete Selected Students')"
        confirmText="{{ __('Delete All') }}"
        wireConfirm="deleteSelected"
    >
        <p class="text-sm text-gray-500">
            {{ __('Are you sure you want to delete the selected students? This action is permanent and will cascade to erase all related records.') }}
        </p>
    </x-college.confirm-modal>

</div>
