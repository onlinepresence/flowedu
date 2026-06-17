<div class="mx-auto max-w-7xl space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Student Medical Records') }}</h2>
        <button
            type="button"
            wire:click="openLogModal"
            class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            {{ __('Log Medical Info') }}
        </button>
    </div>

    <!-- Medical Records Table Card -->
    <x-card>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-150 pb-4">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Medical Directory') }}</h2>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Period Filter -->
                <div class="flex items-center gap-2">
                    <label for="period-filter" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Period') }}</label>
                    <select wire:model.live="periodFilter" id="period-filter" class="block rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="current_month">{{ __('Current Month') }}</option>
                        <option value="last_30_days">{{ __('Last 30 Days') }}</option>
                        <option value="last_3_months">{{ __('Last 3 Months') }}</option>
                        <option value="last_6_months">{{ __('Last 6 Months') }}</option>
                        <option value="custom">{{ __('Custom Range') }}</option>
                        <option value="all">{{ __('All Time') }}</option>
                    </select>
                </div>

                @if ($periodFilter === 'custom')
                    <div class="flex items-center gap-1.5">
                        <x-text-input
                            wire:model.live="customStartDate"
                            type="date"
                            class="block text-sm"
                            aria-label="{{ __('Start Date') }}"
                        />
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ __('to') }}</span>
                        <x-text-input
                            wire:model.live="customEndDate"
                            type="date"
                            class="block text-sm"
                            aria-label="{{ __('End Date') }}"
                        />
                    </div>
                @endif

                <div class="relative w-full sm:w-64">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                    </div>
                    <x-text-input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="{{ __('Search index or name…') }}"
                        class="block w-full pl-9 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto -mx-6 -my-5 mt-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Index') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Allergies') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Conditions') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Emergency Contact') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($browseRows as $row)
                        <tr wire:key="med-row-{{ $row->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/10">
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ $row->student?->index_number ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row->student ? trim(implode(' ', array_filter([$row->student->firstname, $row->student->lastname]))) : '—' }}
                                @if($row->student?->blood_group)
                                    <span class="ml-2 inline-flex items-center rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-400">
                                        <i class="fa-solid fa-droplet mr-0.5 text-3xs"></i> {{ $row->student->blood_group }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 truncate max-w-[150px]">{{ $row->allergies ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 truncate max-w-[200px]">{{ $row->medical_conditions ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 truncate max-w-[200px]">{{ $row->emergency_contacts ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="viewRecord({{ $row->id }})"
                                        class="text-blue-600 hover:text-blue-500 hover:scale-110 transition-transform"
                                        title="{{ __('View Details') }}"
                                    >
                                        <i class="fa-solid fa-eye text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="viewSummary({{ $row->student_id }})"
                                        class="text-green-600 hover:text-green-500 hover:scale-110 transition-transform"
                                        title="{{ __('View Patient Timeline Summary') }}"
                                    >
                                        <i class="fa-solid fa-notes-medical text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="editRecord({{ $row->id }})"
                                        class="text-amber-600 hover:text-amber-500 hover:scale-110 transition-transform"
                                        title="{{ __('Edit Medical Record') }}"
                                    >
                                        <i class="fa-solid fa-pen text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No student medical records found for the selected period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $browseRows->links() }}
        </div>
    </x-card>

    <!-- View Medical Record Details Modal -->
    <x-college.modal name="view-medical-modal" :title="__('Student Medical Record Details')">
        @if ($selectedRecord)
            <div class="space-y-6">
                <!-- Header Bio Block -->
                <div class="flex items-center gap-4 bg-blue-50/50 p-4 rounded-lg dark:bg-blue-950/20 border border-blue-150 dark:border-blue-900/40">
                    <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">
                            {{ $selectedRecord->student ? trim(implode(' ', array_filter([$selectedRecord->student->firstname, $selectedRecord->student->lastname]))) : __('Unknown Patient') }}
                        </h4>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">
                            {{ __('Index') }}: {{ $selectedRecord->student?->index_number }} | {{ __('Insurance') }}: {{ $selectedRecord->student?->insurance_number ?? __('Not Registered') }}
                        </p>
                    </div>
                </div>

                <!-- Structured Records View -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="border-b border-gray-100 pb-2 sm:col-span-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Primary Allergies') }}</span>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400 font-semibold">{{ $selectedRecord->allergies ?? __('None Declared') }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-2 sm:col-span-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Medical Conditions') }}</span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $selectedRecord->medical_conditions ?? __('No chronic conditions recorded.') }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-2 sm:col-span-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Prescribed Medications') }}</span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $selectedRecord->medications ?? __('No medications listed.') }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Immunizations') }}</span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $selectedRecord->immunization_records ?? __('—') }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Emergency Contact') }}</span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $selectedRecord->emergency_contacts ?? __('—') }}</p>
                    </div>
                    <div class="border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Blood Group') }}</span>
                        <p class="mt-1 text-sm font-bold text-red-600 dark:text-red-400">{{ $selectedRecord->student?->blood_group ?? __('Not Provided') }}</p>
                    </div>
                </div>

                <div class="text-right text-xs text-gray-450 dark:text-gray-500">
                    {{ __('Record Date') }}: {{ $selectedRecord->created_at?->format('F d, Y h:i A') }}
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'view-medical-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                {{ __('Close') }}
            </button>
            @if ($selectedRecord)
                <button
                    type="button"
                    wire:click="editRecord({{ $selectedRecord->id }})"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500"
                >
                    {{ __('Edit Record') }}
                </button>
            @endif
        </x-slot>
    </x-college.modal>

    <!-- View Patient Timeline Summary Modal -->
    <x-college.modal name="view-summary-modal" :title="__('Patient Medical History Timeline')">
        @if ($summaryStudent)
            <div class="space-y-6">
                <!-- Header Bio Block -->
                <div class="flex items-center gap-4 bg-purple-50/50 p-4 rounded-lg dark:bg-purple-950/20 border border-purple-150 dark:border-purple-900/50">
                    <div class="h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-notes-medical"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">
                            {{ trim(implode(' ', array_filter([$summaryStudent->firstname, $summaryStudent->lastname]))) }}
                        </h4>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">
                            {{ __('Index') }}: {{ $summaryStudent->index_number }} | {{ __('Insurance') }}: {{ $summaryStudent->insurance_number ?? __('Not Registered') }}
                        </p>
                    </div>
                </div>

                <!-- Timeline Entries -->
                <div class="flow-root max-h-[350px] overflow-y-auto pr-1">
                    <ul class="-mb-8">
                        @forelse ($summaryRecords as $idx => $record)
                            <li>
                                <div class="relative pb-8">
                                    @if ($idx < count($summaryRecords) - 1)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                                <i class="fa-solid fa-clipboard-user text-xs"></i>
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5">
                                            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ __('Logged by Practitioner') }}
                                                </span>
                                                <span>{{ $record->created_at?->format('M d, Y') }}</span>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 space-y-1 bg-gray-50 dark:bg-gray-800/40 p-3 rounded-md border border-gray-100 dark:border-gray-800">
                                                @if ($record->medical_conditions)
                                                    <div><strong>{{ __('Conditions') }}:</strong> {{ $record->medical_conditions }}</div>
                                                @endif
                                                @if ($record->allergies)
                                                    <div><strong>{{ __('Allergies') }}:</strong> <span class="text-red-500 font-semibold">{{ $record->allergies }}</span></div>
                                                @endif
                                                @if ($record->medications)
                                                    <div><strong>{{ __('Medications') }}:</strong> {{ $record->medications }}</div>
                                                @endif
                                                @if ($record->immunization_records)
                                                    <div><strong>{{ __('Immunizations') }}:</strong> {{ $record->immunization_records }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">{{ __('No encounters recorded.') }}</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'view-summary-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                {{ __('Close') }}
            </button>
        </x-slot>
    </x-college.modal>

    <!-- Log/Edit Medical Records Modal -->
    <x-college.modal
        name="log-medical-modal"
        :title="$editRecordId ? __('Edit Medical Record Entry') : (($selectedStudent && $selectedStudent->insurance_number) ? __('Log New Student Medical Entry') . ' (' . __('Ins') . ': ' . $selectedStudent->insurance_number . ')' : __('Log New Student Medical Entry'))"
        maxWidth="4xl"
    >
        <div class="space-y-4">
            @if (! $editStudentId)
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
                        <ul class="mt-2 max-h-40 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 z-30 shadow-lg">
                            @foreach ($studentPickerHits as $hit)
                                <li class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-2 text-sm dark:border-gray-800">
                                    <span class="text-gray-800 dark:text-gray-100 font-medium">{{ $hit['label'] }}</span>
                                    <button type="button" wire:click="selectMedicalStudent({{ $hit['id'] }})" class="text-xs font-semibold text-purple-600 hover:text-purple-550">{{ __('Select') }}</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @else
                <!-- Selected student status card -->
                <div class="flex items-center justify-between rounded-lg bg-purple-50 p-3.5 dark:bg-purple-950/20 border border-purple-150 dark:border-purple-900/50">
                    <div>
                        <span class="text-xs text-purple-600 dark:text-purple-400 block">{{ __('Selected Patient') }}</span>
                        <span class="font-bold text-purple-800 dark:text-purple-300">
                            {{ $selectedStudent ? trim(implode(' ', array_filter([$selectedStudent->firstname, $selectedStudent->lastname]))) : '' }}
                        </span>
                        <span class="font-mono text-xs text-purple-700 dark:text-purple-400 block">
                            {{ $selectedStudent?->index_number }}
                            @if($selectedStudent?->insurance_number)
                                — {{ __('Ins') }}: {{ $selectedStudent->insurance_number }}
                            @endif
                        </span>
                    </div>
                    @if (! $editRecordId)
                        <button type="button" wire:click="clearMedicalStudent" class="text-xs font-bold text-red-650 hover:text-red-500">{{ __('Change') }}</button>
                    @endif
                </div>

                <!-- Form Fields in clean side-by-side grids -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Left: Profile Data -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800 pb-2">
                            {{ __('Student Profile Data (Persisted)') }}
                        </h3>
                        <div>
                            <label for="blood_group" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Blood Group') }}</label>
                            @if ($selectedStudent && !is_null($selectedStudent->blood_group))
                                <select id="blood_group" disabled class="block w-full text-sm rounded-lg border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-400">
                                    <option value="">{{ $selectedStudent->blood_group }}</option>
                                </select>
                                <p class="text-3xs text-gray-450 mt-1"><i class="fa-solid fa-circle-info"></i> {{ __('Blood group cannot be updated once provided.') }}</p>
                            @else
                                <select wire:model="blood_group" id="blood_group" class="block w-full text-sm rounded-lg border-gray-300 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="">{{ __('Select Blood Group') }}</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            @endif
                            @error('blood_group')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="allergies" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Allergies') }}</label>
                            <x-text-input wire:model="allergies" id="allergies" type="text" class="block w-full text-sm" placeholder="{{ __('e.g., Peanuts, Penicillin') }}" />
                            @error('allergies')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="insurance_number" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('National Insurance Number') }}</label>
                            <x-text-input wire:model="insurance_number" id="insurance_number" type="text" class="block w-full text-sm" />
                            @error('insurance_number')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="emergency_contacts" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Emergency Contact Details') }}</label>
                            <x-textarea-input wire:model="emergency_contacts" id="emergency_contacts" rows="3" class="block w-full text-sm" placeholder="{{ __('Name, relationship, phone number') }}" />
                            @error('emergency_contacts')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <!-- Right: Encounter Details -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800 pb-2">
                            {{ __('Encounter Logs (This Visit)') }}
                        </h3>
                        <div>
                            <label for="medical_conditions" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Medical Conditions') }}</label>
                            <x-textarea-input wire:model="medical_conditions" id="medical_conditions" rows="2" class="block w-full text-sm" placeholder="{{ __('e.g., Asthma, Hypertension') }}" />
                            @error('medical_conditions')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="medications" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Current Medications') }}</label>
                            <x-textarea-input wire:model="medications" id="medications" rows="2" class="block w-full text-sm" />
                            @error('medications')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="immunization_records" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Immunization Records') }}</label>
                            <x-textarea-input wire:model="immunization_records" id="immunization_records" rows="2" class="block w-full text-sm" />
                            @error('immunization_records')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            @endif

            <x-slot name="footer">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'log-medical-modal');"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ __('Cancel') }}
                </button>
                @if ($editStudentId)
                    <button
                        type="button"
                        wire:click="saveMedical"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        <span wire:loading wire:target="saveMedical" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ __('Save Medical Info') }}
                    </button>
                @endif
            </x-slot>
        </div>
    </x-college.modal>

</div>
