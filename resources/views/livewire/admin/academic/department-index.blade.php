<div class="mx-auto max-w-7xl space-y-6">
    <x-card class="p-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white mb-4">
            {{ $editingDepartmentId ? __('Edit department') : __('Add department') }}
        </h2>
        <form wire:submit="{{ $editingDepartmentId ? 'updateDepartment' : 'saveDepartment' }}" class="grid gap-4 md:grid-cols-3 items-end">
            <div>
                <x-input-label for="dept-name" :value="__('Department name')" />
                <x-text-input wire:model="name" id="dept-name" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Computer Science') }}" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="dept-faculty" :value="__('Faculty')" />
                <x-select-input wire:model="faculty_id" id="dept-faculty" class="mt-1 block w-full">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('faculty_id')" class="mt-1" />
            </div>
            <div class="flex gap-2">
                <x-college-form-submit target="{{ $editingDepartmentId ? 'updateDepartment' : 'saveDepartment' }}" class="inline-flex justify-center flex-1">
                    {{ $editingDepartmentId ? __('Update') : __('Add') }}
                </x-college-form-submit>
                @if ($editingDepartmentId)
                    <button
                        type="button"
                        wire:click="cancelEditDepartment"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        {{ __('Cancel') }}
                    </button>
                @endif
            </div>
        </form>
    </x-card>

    <x-card class="overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('All Departments') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Faculty') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Programs') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($departments as $dept)
                        <tr wire:key="dept-{{ $dept->id }}">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $dept->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $dept->faculty?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $dept->programs_count }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" wire:click="editDepartment({{ $dept->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen text-base"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDeleteDepartment({{ $dept->id }})" class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform" title="{{ __('Delete') }}">
                                        <i class="fa-solid fa-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8">
                                <x-college.empty-state
                                    :title="__('No departments found')"
                                    :description="__('Add a department using the form above.')"
                                    class="border-none bg-transparent py-4"
                                >
                                    <x-slot:icon>
                                        <i class="fa-solid fa-folder-open text-4xl text-gray-300 dark:text-gray-600 block"></i>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $departments->links() }}
            </div>
        @endif
    </x-card>

    <!-- Delete Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-department-modal"
        type="danger"
        :title="__('Delete Department')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteDepartment"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this department? Related academic programs and courses may be affected.') }}
        </p>
    </x-college.confirm-modal>
</div>
