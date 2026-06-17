<div class="mx-auto max-w-7xl space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Disciplinary Incident Logs') }}</h2>
        <button
            type="button"
            wire:click="openLogModal"
            class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            {{ __('Log Incident') }}
        </button>
    </div>

    <!-- Case Directory Card -->
    <x-card>
        <!-- Filters Header -->
        <div class="flex flex-col gap-4 border-b border-gray-200 pb-4">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Disciplinary Cases') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                    </div>
                    <x-text-input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="{{ __('Search index, name, offense…') }}"
                        class="block w-full pl-9 text-sm"
                    />
                </div>
                <div>
                    <select wire:model.live="programFilter" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('All Programs') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select wire:model.live="returnStatus" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                        <option value="all">{{ __('All Cases') }}</option>
                        <option value="open">{{ __('Open Cases Only') }}</option>
                        <option value="closed">{{ __('Closed Cases Only') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Directory -->
        <div class="overflow-x-auto -mx-6 -my-5 mt-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Index') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Offense') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Action Taken') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="discipline-row-{{ $row->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/10">
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ $row->index_number }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->fullname }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->program?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-[160px] truncate" title="{{ $row->offense }}">{{ $row->offense }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-[160px] truncate" title="{{ $row->action_taken }}">{{ $row->action_taken }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->date_of_action?->format('Y-m-d') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($row->return_status)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('Closed') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ __('Active') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="viewCase({{ $row->id }})"
                                        class="text-blue-600 hover:text-blue-500 hover:scale-110 transition-transform"
                                        title="{{ __('View Details') }}"
                                    >
                                        <i class="fa-solid fa-eye text-base"></i>
                                    </button>
                                    @if (! $row->return_status)
                                        <button
                                            type="button"
                                            wire:click="confirmCloseRecord({{ $row->id }})"
                                            class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform"
                                            title="{{ __('Close Case') }}"
                                        >
                                            <i class="fa-solid fa-gavel text-base"></i>
                                        </button>
                                    @else
                                        <span class="text-gray-400" title="{{ __('Resolved') }}">
                                            <i class="fa-solid fa-circle-check text-base text-gray-300 dark:text-gray-600"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No disciplinary incidents recorded.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $rows->links() }}
        </div>
    </x-card>

    <!-- Modals -->

    <!-- View Case Details Modal -->
    <x-college.modal name="view-case-modal" :title="__('Disciplinary Incident Case File')">
        @if ($selectedCase)
            <div class="space-y-6">
                <!-- Header Bio Block -->
                <div class="flex items-center gap-4 bg-red-550/10 p-4 rounded-lg dark:bg-red-950/10 border border-red-200/50 dark:border-red-900/40">
                    <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-scale-unbalanced-flipped"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">
                            {{ $selectedCase->fullname }}
                        </h4>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">
                            {{ __('Index') }}: {{ $selectedCase->index_number }} | {{ __('Program') }}: {{ $selectedCase->program?->name ?? '—' }}
                        </p>
                    </div>
                </div>

                <!-- Incident details -->
                <div class="space-y-4">
                    <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Offense details') }}</span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line font-medium">{{ $selectedCase->offense }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Action Taken / Sentence') }}</span>
                        <p class="mt-1 text-sm text-purple-600 dark:text-purple-400 font-semibold whitespace-pre-line">{{ $selectedCase->action_taken }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Additional comments / internal notes') }}</span>
                            <button
                                type="button"
                                wire:click="startEditComments({{ $selectedCase->id }})"
                                class="text-xs font-semibold text-amber-600 hover:text-amber-500 flex items-center gap-1"
                            >
                                <i class="fa-solid fa-pen text-[10px]"></i>
                                {{ __('Update Comments') }}
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line bg-gray-50 dark:bg-gray-900/50 p-3 rounded-md border border-gray-100 dark:border-gray-800">
                            {{ $selectedCase->comments ?? __('No commentary logged.') }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 pt-2">
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Incident Date') }}</span>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedCase->date_of_action?->format('F d, Y') }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Suspension Return Date') }}</span>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $selectedCase->return_date?->format('F d, Y') ?? __('N/A') }}
                            </p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Case File Status') }}</span>
                            <p class="mt-1">
                                @if ($selectedCase->return_status)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('Closed & Resolved') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ __('Active Case') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'view-case-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                {{ __('Close') }}
            </button>
            @if ($selectedCase && ! $selectedCase->return_status)
                <button
                    type="button"
                    wire:click="confirmCloseFromView"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none"
                >
                    <i class="fa-solid fa-gavel"></i>
                    {{ __('Close Disciplinary Case') }}
                </button>
            @endif
        </x-slot>
    </x-college.modal>

    <!-- Log Incident Modal -->
    <x-college.modal name="log-disciplinary-modal" :title="__('Log Disciplinary Incident')">
        <div class="space-y-4">
            @if (! $disciplineStudentId)
                <div>
                    <label for="search-student" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Search Student') }}</label>
                    <x-text-input
                        wire:model.live.debounce.300ms="studentPickerSearch"
                        id="search-student"
                        type="search"
                        placeholder="{{ __('Type index number or student name…') }}"
                        class="block w-full text-sm"
                        autocomplete="off"
                    />
                    @if (count($studentPickerHits) > 0)
                        <ul class="mt-2 max-h-40 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 z-30 shadow-lg" role="listbox">
                            @foreach ($studentPickerHits as $hit)
                                <li class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-2 text-sm dark:border-gray-800">
                                    @if ($hit['has_program'])
                                        <span class="text-gray-800 dark:text-gray-100 font-medium">{{ $hit['label'] }}</span>
                                        <button type="button" wire:click="selectDisciplineStudent({{ $hit['id'] }})" class="text-xs font-semibold text-purple-600 hover:text-purple-700">{{ __('Select') }}</button>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">{{ $hit['label'] }} ({{ __('No Program Assigned') }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @else
                <!-- Selected student card -->
                <div class="flex items-center justify-between rounded-lg bg-purple-50 p-3.5 dark:bg-purple-950/20 border border-purple-150 dark:border-purple-900/50">
                    <div>
                        <span class="text-xs text-purple-600 dark:text-purple-400 block">{{ __('Selected Student') }}</span>
                        <span class="font-bold text-purple-800 dark:text-purple-300">
                            {{ $selectedStudent ? trim(implode(' ', array_filter([$selectedStudent->firstname, $selectedStudent->lastname]))) : '' }}
                        </span>
                        <span class="font-mono text-xs text-purple-700 dark:text-purple-400 block">{{ $selectedStudent?->index_number }}</span>
                    </div>
                    <button type="button" wire:click="clearDisciplineStudent" class="text-xs font-bold text-red-600 hover:text-red-500">{{ __('Change') }}</button>
                </div>

                <!-- Form fields -->
                <div class="grid gap-4 sm:grid-cols-2 pr-1">
                    <div class="sm:col-span-2">
                        <label for="offense" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Offense Details') }}</label>
                        <x-textarea-input wire:model="offense" id="offense" rows="2" class="block w-full text-sm" required />
                        @error('offense')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="action_taken" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Action Taken') }}</label>
                        <x-textarea-input wire:model="action_taken" id="action_taken" rows="2" class="block w-full text-sm" placeholder="{{ __('e.g., Suspension, warning letter, parent conference') }}" required />
                        @error('action_taken')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="comments" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Comments / Notes') }}</label>
                        <x-textarea-input wire:model="comments" id="comments" rows="2" class="block w-full text-sm" />
                        @error('comments')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label for="date_of_action" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Incident Date') }}</label>
                        <x-text-input wire:model="date_of_action" id="date_of_action" type="date" class="block w-full text-sm" required />
                        @error('date_of_action')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label for="return_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('End Date / Suspension Return') }} <span class="font-normal text-gray-400">({{ __('optional') }})</span></label>
                        <x-text-input wire:model="return_date" id="return_date" type="date" class="block w-full text-sm" />
                        @error('return_date')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                </div>
            @endif

            <x-slot name="footer">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'log-disciplinary-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ __('Cancel') }}
                </button>
                @if ($disciplineStudentId)
                    <button
                        type="button"
                        wire:click="addRecord"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        <span wire:loading wire:target="addRecord" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ __('Save Incident') }}
                    </button>
                @endif
            </x-slot>
        </div>
    </x-college.modal>

    <!-- Update Comments Modal -->
    <x-college.modal name="edit-comments-modal" :title="__('Update Case File Comments')">
        <div class="space-y-4">
            <div>
                <label for="editComments" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Comments / Notes') }}</label>
                <x-textarea-input wire:model="editComments" id="editComments" rows="5" class="block w-full text-sm" />
                @error('editComments')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
            </div>
        </div>

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'edit-comments-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250"
            >
                {{ __('Cancel') }}
            </button>
            <button
                type="button"
                wire:click="saveComments"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700"
            >
                <span wire:loading wire:target="saveComments" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                {{ __('Save Comments') }}
            </button>
        </x-slot>
    </x-college.modal>

    <!-- Close Case Confirm Modal -->
    <x-college.confirm-modal
        name="close-case-confirm-modal"
        type="warning"
        :title="__('Close Disciplinary Case')"
        confirmText="{{ __('Close Case') }}"
        wireConfirm="closeRecord"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to close this disciplinary case? This marks the suspension as completed and resolves the active status of the case file.') }}
        </p>
    </x-college.confirm-modal>

</div>
