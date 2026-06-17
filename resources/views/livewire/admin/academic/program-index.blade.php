<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-add-program')"
            class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
        >
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
            {{ __('Add Program') }}
        </button>
    </x-slot>

    <x-card class="overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('All Programs') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Courses') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($programs as $program)
                        <tr wire:key="prog-{{ $program->id }}">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $program->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $program->department?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $program->courses_count }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="{{ route('program.classes', ['program_id' => $program->id]) }}" wire:navigate class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('Manage levels') }}">
                                        <i class="fa-solid fa-list-check text-base"></i>
                                    </a>
                                    <button type="button" wire:click="editProgram({{ $program->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('Edit') }}">
                                        <i class="fa-solid fa-pen text-base"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDeleteProgram({{ $program->id }})" class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform" title="{{ __('Delete') }}">
                                        <i class="fa-solid fa-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8">
                                <x-college.empty-state
                                    :title="__('No programs found')"
                                    :description="__('Add a program using the button above.')"
                                    class="border-none bg-transparent py-4"
                                >
                                    <x-slot:icon>
                                        <i class="fa-solid fa-graduation-cap text-4xl text-gray-300 dark:text-gray-600 block"></i>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($programs->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $programs->links() }}
            </div>
        @endif
    </x-card>

    <x-college.modal
        name="program-modal"
        :title="$editingProgramId ? __('Edit program') : __('Add program')"
        maxWidth="2xl"
    >
        <form wire:submit="{{ $editingProgramId ? 'updateProgram' : 'saveProgram' }}" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="prog-name" :value="__('Program name')" />
                    <x-text-input wire:model="name" id="prog-name" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Bachelor of Science in IT') }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="prog-department" :value="__('Department')" />
                    <x-select-input wire:model="department_id" id="prog-department" class="mt-1 block w-full" required>
                        <option value="">{{ __('Select department') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="prog-certificate" :value="__('Certificate')" />
                    <x-text-input wire:model="certificate" id="prog-certificate" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. B.Sc. Information Technology') }}" required />
                    <x-input-error :messages="$errors->get('certificate')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="prog-cost" :value="__('Cost (GHC)')" />
                    <x-text-input wire:model="cost" id="prog-cost" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('cost')" class="mt-1" />
                </div>
            </div>
            <div class="max-w-xs">
                <x-input-label for="prog-length" :value="__('Program length (years)')" />
                <x-text-input wire:model.number="program_length" id="prog-length" type="number" min="1" max="20" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('program_length')" class="mt-1" />
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="button"
                    wire:click="cancelEditProgram"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
                <x-college-form-submit target="{{ $editingProgramId ? 'updateProgram' : 'saveProgram' }}" class="inline-flex justify-center">
                    {{ $editingProgramId ? __('Update') : __('Add') }}
                </x-college-form-submit>
            </div>
        </form>
    </x-college.modal>

    <!-- Delete Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-program-modal"
        type="danger"
        :title="__('Delete Program')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteProgram"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this program? All related courses and classes will be affected.') }}
        </p>
    </x-college.confirm-modal>
</div>
