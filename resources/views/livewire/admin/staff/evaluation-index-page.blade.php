<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <button
            type="button"
            x-on:click="$dispatch('open-modal', 'ev-create')"
            wire:click="openCreateModal"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
            <i class="fa-solid fa-plus"></i>
            {{ __('New evaluation form') }}
        </button>
    </x-slot>

    <!-- Filters Section -->
    <x-college.filter-card cols="2">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Search evaluations...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterStatus" :value="__('Status')" />
            <select id="filterStatus" wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
                <option value="closed">{{ __('Closed') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Evaluations Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($forms->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('No evaluation forms match your criteria.') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Code') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Title / Academic Year') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Availability Window') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Responses') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($forms as $form)
                            <tr wire:key="ef-{{ $form->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                <td class="px-6 py-4 text-sm font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ $form->unique_code }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $form->title }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $form->academic_year }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs"><span class="text-gray-400 uppercase tracking-wide mr-1">{{ __('Start:') }}</span> {{ $form->start_time?->format('M d, Y, h:i A') }}</span>
                                        <span class="text-xs"><span class="text-gray-400 uppercase tracking-wide mr-1">{{ __('End:') }}</span> {{ $form->end_time?->format('M d, Y, h:i A') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ((int) $form->is_active === 1)
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">
                                            {{ __('Active') }}
                                        </span>
                                    @elseif ((int) $form->is_active === -1)
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-350">
                                            {{ __('Closed') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-300">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $form->responses_count }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end items-center gap-3">
                                        <a href="{{ route('admin.evaluation', ['form_code' => $form->unique_code]) }}" title="{{ __('Configure & Manage') }}" class="text-indigo-650 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" wire:navigate>
                                            <i class="fa-solid fa-gear fa-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.evaluation.preview', ['form_code' => $form->unique_code]) }}" title="{{ __('Preview Survey') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300" wire:navigate>
                                            <i class="fa-solid fa-eye fa-lg"></i>
                                        </a>
                                        @php
                                            $canDelete = $form->responses_count === 0 && (int) $form->is_active !== 1;
                                        @endphp
                                        @if ($canDelete)
                                            <button
                                                type="button"
                                                wire:click="openDeleteModal('{{ $form->unique_code }}')"
                                                title="{{ __('Delete Form') }}"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <i class="fa-solid fa-trash-can fa-lg"></i>
                                            </button>
                                        @else
                                            <span
                                                class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                                title="{{ __('Cannot delete active forms or forms with responses.') }}"
                                            >
                                                <i class="fa-solid fa-trash-can fa-lg"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <x-college.modal name="ev-delete" :title="__('Delete Evaluation Form?')" maxWidth="md">
        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ __('This action cannot be undone. Only inactive or closed forms with zero student responses can be deleted.') }}</p>
        <x-slot:footer>
            <button type="button" wire:click="closeDeleteModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">{{ __('Cancel') }}</button>
            <button type="button" wire:click="confirmDeleteForm" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">{{ __('Delete Form') }}</button>
        </x-slot:footer>
    </x-college.modal>

    <!-- Create Modal -->
    <x-college.modal name="ev-create" :title="__('New Evaluation Form')" maxWidth="lg" livewireSynced>
        <form wire:submit.prevent="saveNewForm" class="space-y-4">
            <div>
                <x-input-label for="createTitle" :value="__('Title')" />
                <x-text-input id="createTitle" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('e.g., Tutor Appraisal Semester 1') }}" wire:model="createTitle" />
                <x-input-error :messages="$errors->get('createTitle')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="createAcademicYear" :value="__('Academic Year')" />
                <x-text-input id="createAcademicYear" type="text" class="mt-1 block w-full text-sm bg-gray-50 dark:bg-gray-900/50 cursor-not-allowed" placeholder="2025/2026" wire:model="createAcademicYear" readonly />
                <x-input-error :messages="$errors->get('createAcademicYear')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="createStartTime" :value="__('Start Date & Time')" />
                    <x-text-input id="createStartTime" type="datetime-local" class="mt-1 block w-full text-sm" wire:model="createStartTime" />
                    <x-input-error :messages="$errors->get('createStartTime')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="createEndTime" :value="__('End Date & Time')" />
                    <x-text-input id="createEndTime" type="datetime-local" class="mt-1 block w-full text-sm" wire:model="createEndTime" />
                    <x-input-error :messages="$errors->get('createEndTime')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="createControlType" :value="__('Control Type')" />
                <select id="createControlType" wire:model="createControlType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="auto">{{ __('Automatic (active during date window)') }}</option>
                    <option value="manual">{{ __('Manual (active only when toggle is on)') }}</option>
                </select>
                <x-input-error :messages="$errors->get('createControlType')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" x-on:click="$dispatch('close-modal', 'ev-create')" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </x-college.modal>
</div>
