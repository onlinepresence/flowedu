<div class="mx-auto max-w-7xl space-y-8">
    <x-slot name="headerActions">
        <button 
            type="button" 
            x-data
            x-on:click="$dispatch('open-auto-generate')"
            class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/40"
        >
            <i class="fa-solid fa-magic-wand-sparkles"></i>
            {{ __('Auto Generate') }}
        </button>
    </x-slot>

    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Add/Edit form -->
        <div class="lg:col-span-1">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $editingId ? __('Edit grade band') : __('Add grade band') }}
                </h2>
                <form wire:submit="saveRow" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Grade Letter') }}</label>
                        <input wire:model="grade" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900 dark:text-white" placeholder="e.g. A" required />
                        @error('grade') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Min Score') }}</label>
                            <input wire:model="min_score" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-655 dark:bg-gray-900 dark:text-white" placeholder="0" required />
                            @error('min_score') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Max Score') }}</label>
                            <input wire:model="max_score" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-655 dark:bg-gray-900 dark:text-white" placeholder="100" required />
                            @error('max_score') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('GPA Points') }}</label>
                        <input wire:model="points" type="number" step="0.01" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-655 dark:bg-gray-900 dark:text-white" placeholder="4.0" required />
                        @error('points') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-2">
                        @if ($editingId)
                            <button
                                type="button"
                                wire:click="cancelEdit"
                                class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                {{ __('Cancel') }}
                            </button>
                        @endif
                        <x-college-form-submit target="saveRow" class="w-full justify-center">
                            {{ $editingId ? __('Update') : __('Add') }}
                        </x-college-form-submit>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grade bands list -->
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Grade') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Score Range') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('GPA Points') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($rows as $row)
                                <tr wire:key="gp-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2.5 py-0.5 text-sm font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                            {{ $row->grade }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ number_format($row->min_score, 2) }}% - {{ number_format($row->max_score, 2) }}%
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($row->points, 2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <button 
                                            type="button" 
                                            wire:click="edit({{ $row->id }})" 
                                            class="mr-3 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="{{ __('Edit') }}"
                                        >
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="confirmDelete({{ $row->id }})" 
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="{{ __('Delete') }}"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No grade bands setup. Click "Auto Generate" to populate standard bands.') }}
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
        </div>
    </div>

    <!-- Auto Generate Modal -->
    @if ($showAutoGenerateModal)
        <x-college.modal
            name="auto-generate-modal"
            :title="__('Auto Generate Grade Bands')"
            :show="true"
            maxWidth="3xl"
        >
            <form wire:submit="saveAutoGenerated" class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Select and customize the default grade bands for Ghana Colleges of Education before importing them.') }}
                </p>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 text-left"></th>
                                <th class="px-4 py-2 text-left">{{ __('Grade') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Min Score') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Max Score') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('GPA Points') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($defaultGradePoints as $i => $gp)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-2 text-center">
                                        <input wire:model="defaultGradePoints.{{ $i }}.selected" type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900" />
                                    </td>
                                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                        <input wire:model="defaultGradePoints.{{ $i }}.grade" type="text" class="w-16 rounded border-gray-300 px-2 py-1 text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input wire:model="defaultGradePoints.{{ $i }}.min_score" type="number" step="0.01" class="w-24 rounded border-gray-300 px-2 py-1 text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input wire:model="defaultGradePoints.{{ $i }}.max_score" type="number" step="0.01" class="w-24 rounded border-gray-300 px-2 py-1 text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input wire:model="defaultGradePoints.{{ $i }}.points" type="number" step="0.01" class="w-20 rounded border-gray-300 px-2 py-1 text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="$set('showAutoGenerateModal', false)"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <x-college-form-submit target="saveAutoGenerated" class="inline-flex justify-center">
                        {{ __('Generate & Save') }}
                    </x-college-form-submit>
                </div>
            </form>
        </x-college.modal>
    @endif

    <!-- Delete Confirmation Modal -->
    <x-college.confirm-modal
        name="confirm-delete-grade-point-modal"
        type="danger"
        :title="__('Delete Grade Band')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteGradePoint"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this grade band? This can affect calculations of GPA for results matching this score range.') }}
        </p>
    </x-college.confirm-modal>
</div>
