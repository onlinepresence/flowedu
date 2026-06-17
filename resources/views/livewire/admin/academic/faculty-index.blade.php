<div class="mx-auto max-w-7xl space-y-6">
    <x-card class="p-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white mb-4">
            {{ $editingFacultyId ? __('Edit faculty') : __('Add faculty') }}
        </h2>
        <form wire:submit="{{ $editingFacultyId ? 'updateFaculty' : 'saveFaculty' }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-input-label for="new-faculty-name" :value="__('Faculty name')" />
                <x-text-input wire:model="newName" id="new-faculty-name" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Faculty of Science') }}" required />
                <x-input-error :messages="$errors->get('newName')" class="mt-1" />
            </div>
            <div class="flex gap-2">
                <x-college-form-submit target="{{ $editingFacultyId ? 'updateFaculty' : 'saveFaculty' }}" class="inline-flex shrink-0 justify-center">
                    {{ $editingFacultyId ? __('Update') : __('Add') }}
                </x-college-form-submit>
                @if ($editingFacultyId)
                    <button
                        type="button"
                        wire:click="cancelEditFaculty"
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
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('All faculties') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Dean') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($faculties as $faculty)
                        <tr wire:key="faculty-{{ $faculty->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $faculty->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $faculty->dean?->username ?? $faculty->dean?->email ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" wire:click="editFaculty({{ $faculty->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen text-base"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDeleteFaculty({{ $faculty->id }})" class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform" title="{{ __('Delete') }}">
                                        <i class="fa-solid fa-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8">
                                <x-college.empty-state
                                    :title="__('No faculties found')"
                                    :description="__('Add a faculty using the form above.')"
                                    class="border-none bg-transparent py-4"
                                >
                                    <x-slot:icon>
                                        <i class="fa-solid fa-building-columns text-4xl text-gray-300 dark:text-gray-600 block"></i>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($faculties->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $faculties->links() }}
            </div>
        @endif
    </x-card>

    <!-- Delete Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-faculty-modal"
        type="danger"
        :title="__('Delete Faculty')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteFaculty"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this faculty and all of its associated departments? Related records may be deleted by the database.') }}
        </p>
    </x-college.confirm-modal>
</div>
