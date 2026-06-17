<x-slot name="headerActions">
    <div class="flex flex-wrap items-center gap-2" x-data>
        <button type="button" x-on:click="$dispatch('download-template')" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            {{ __('Template') }}
        </button>
        <button type="button" x-on:click="$dispatch('open-import-modal')" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
            {{ __('Upload Roster') }}
        </button>
        <button type="button" x-on:click="$dispatch('export-roster')" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            {{ __('Export Roster') }}
        </button>
        <button type="button" x-on:click="$dispatch('open-assign-modal')" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Assign Trainee') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-import-modal.window="$wire.openImportModal()"
    x-on:open-assign-modal.window="$wire.openAssignModal()"
    x-on:download-template.window="$wire.downloadTemplate()"
    x-on:export-roster.window="$wire.exportRoster()"
>

    <!-- Filters Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="w-full sm:w-1/3">
            <label for="session-select" class="sr-only">{{ __('Academic Session') }}</label>
            <select id="session-select" wire:model.live="academicSessionId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }} {{ $session->is_current ? __('(Current)') : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-1/2">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search trainee, supervisor, or partnership school...') }}" class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments Grid -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max table-auto text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900/50 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        <th class="px-6 py-4">{{ __('Student Trainee') }}</th>
                        <th class="px-6 py-4">{{ __('Supervisor') }}</th>
                        <th class="px-6 py-4">{{ __('Partnership School') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Status') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Evaluation Score') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-900 dark:text-gray-200">
                    @forelse($supervisions as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $s->student->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->student->index_number }} &bull; {{ $s->student->user->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $s->teacher->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->teacher->user->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $s->partnership_school }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($s->status === 'evaluated')
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400">
                                        {{ __('Evaluated') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400">
                                        {{ __('Assigned') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($s->score !== null)
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format((float)$s->score, 2) }}%</span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" wire:click="deleteAssignment({{ $s->id }})" wire:confirm="{{ __('Are you sure you want to delete this supervision assignment?') }}" class="text-red-600 hover:text-red-900 dark:hover:text-red-400 font-medium">
                                    {{ __('Remove') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <x-college.empty-state
                                    title="{{ __('No assignments found') }}"
                                    description="{{ __('There are no student trainees assigned to supervisors in this academic session.') }}"
                                >
                                    <x-slot:icon>
                                        <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m0 0a5.971 5.971 0 0 0-.08-.83m0 0a3 3 0 0 1 4.683-2.72m-4.683 2.72C3.125 17.545 1.5 15.463 1.5 13c0-1.4.48-2.69 1.28-3.71m0 0a5.962 5.962 0 0 1 3.19-2.099M6.75 8.25l-.014-.09a3.987 3.987 0 0 1 3.563-3.906A3.987 3.987 0 0 1 15.016 8a5.971 5.971 0 0 1-.016.25m-8.25 0a5.986 5.986 0 0 1 1.25 3.75l-.001.031a6.012 6.012 0 0 1-.036.666m7.037-4.447a5.986 5.986 0 0 1 1.25 3.75l-.001.031a6.012 6.012 0 0 1-.036.666M12 18.75a6 6 0 0 0 6-6v-.15a6 6 0 0 0-6-6 6 6 0 0 0-6 6v.15a6 6 0 0 0 6 6Z" /></svg>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($supervisions->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $supervisions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Assign Trainee -->
    @if ($showAssignModal)
        <x-college.modal name="assign-trainee-modal" title="{{ __('Assign Student Trainee') }}" :show="true" livewireSynced="true">
            <form id="assign-trainee-form" wire:submit.prevent="saveAssignment" class="space-y-4">
                
                <!-- Step 1: Student Selection -->
                <div class="space-y-2">
                    <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('1. Search & Select Student Trainee') }} <span class="text-red-500">*</span>
                    </span>
                    @if (!$selectedStudentId)
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="studentSearch" placeholder="{{ __('Type student name or index number...') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" wire:loading wire:target="studentSearch">
                                <svg class="animate-spin h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                        </div>
                        
                        <!-- Search Results list -->
                        <div class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white shadow-sm dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-950 overflow-hidden">
                            @forelse($availableStudents as $std)
                                <button type="button" wire:click="$set('selectedStudentId', {{ $std->id }})" class="w-full px-4 py-2.5 text-left text-sm hover:bg-purple-50 dark:hover:bg-purple-950/30 flex items-center justify-between transition-colors">
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $std->user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $std->index_number }} &bull; {{ $std->user->email }}</div>
                                    </div>
                                    <svg class="h-4 w-4 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </button>
                            @empty
                                <div class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 italic">
                                    {{ $studentSearch ? __('No unassigned student matches found.') : __('Type above to search available students...') }}
                                </div>
                            @endforelse
                        </div>
                    @else
                        @php
                            $selStudent = $availableStudents->firstWhere('id', $selectedStudentId) ?? \App\Models\Student::with('user')->find($selectedStudentId);
                        @endphp
                        @if ($selStudent)
                            <div class="flex items-center justify-between rounded-lg bg-green-50/60 border border-green-200 p-3 dark:bg-green-950/20 dark:border-green-900">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-bold text-green-900 dark:text-green-300">{{ $selStudent->user->name }}</div>
                                        <div class="text-xs text-green-700 dark:text-green-400">{{ $selStudent->index_number }} &bull; {{ $selStudent->user->email }}</div>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('selectedStudentId', null)" class="text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    {{ __('Change') }}
                                </button>
                            </div>
                        @endif
                    @endif
                    @error('selectedStudentId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Step 2: Supervisor Selection -->
                @if ($selectedStudentId)
                    <div class="space-y-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                        <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('2. Search & Select Supervisor (Teacher)') }} <span class="text-red-500">*</span>
                        </span>
                        @if (!$selectedTeacherId)
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="teacherSearch" placeholder="{{ __('Type teacher name or email...') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" wire:loading wire:target="teacherSearch">
                                    <svg class="animate-spin h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                            
                            <!-- Search Results list -->
                            <div class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white shadow-sm dark:divide-gray-800 dark:border-gray-700 dark:bg-gray-950 overflow-hidden">
                                @forelse($teachers as $tch)
                                    <button type="button" wire:click="$set('selectedTeacherId', {{ $tch->id }})" class="w-full px-4 py-2.5 text-left text-sm hover:bg-purple-50 dark:hover:bg-purple-950/30 flex items-center justify-between transition-colors">
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $tch->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tch->user->email }}</div>
                                        </div>
                                        <svg class="h-4 w-4 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    </button>
                                @empty
                                    <div class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 italic">
                                        {{ $teacherSearch ? __('No teacher matches found.') : __('Type above to search teachers...') }}
                                    </div>
                                @endforelse
                            </div>
                        @else
                            @php
                                $selTeacher = $teachers->firstWhere('id', $selectedTeacherId) ?? \App\Models\Teacher::with('user')->find($selectedTeacherId);
                            @endphp
                            @if ($selTeacher)
                                <div class="flex items-center justify-between rounded-lg bg-green-50/60 border border-green-200 p-3 dark:bg-green-950/20 dark:border-green-900">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <div>
                                            <div class="text-sm font-bold text-green-900 dark:text-green-300">{{ $selTeacher->user->name }}</div>
                                            <div class="text-xs text-green-700 dark:text-green-400">{{ $selTeacher->user->email }}</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$set('selectedTeacherId', null)" class="text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        {{ __('Change') }}
                                    </button>
                                </div>
                            @endif
                        @endif
                        @error('selectedTeacherId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- Step 3: Partnership School Placement -->
                @if ($selectedStudentId && $selectedTeacherId)
                    <div class="space-y-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                        <label for="partnership-school" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('3. Enter Partnership School Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="partnership-school" wire:model="partnershipSchool" placeholder="{{ __('e.g. Accra Demonstration School') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        @error('partnershipSchool')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <x-slot:footer>
                    <button type="button" wire:click="closeAssignModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    @if ($selectedStudentId && $selectedTeacherId)
                        <button type="submit" form="assign-trainee-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('Assign Supervisor') }}</span>
                            <span wire:loading>{{ __('Saving...') }}</span>
                        </button>
                    @endif
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

    <!-- Modal: Import Roster -->
    @if ($showImportModal)
        <x-college.modal name="import-roster-modal" title="{{ __('Import Trainee Roster') }}" :show="true" livewireSynced="true">
            <form id="import-roster-form" wire:submit.prevent="importRoster" class="space-y-4">
                <div>
                    <label for="csv-file" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('CSV File') }}</label>
                    <input type="file" id="csv-file" wire:model="rosterFile" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-950 dark:file:text-purple-400">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('The CSV file must match the template. Columns required: student_email, teacher_email, partnership_school.') }}
                    </p>
                    @error('rosterFile')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-slot:footer>
                    <button type="button" wire:click="closeImportModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="import-roster-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Upload & Import') }}</span>
                        <span wire:loading>{{ __('Processing...') }}</span>
                    </button>
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

</div>
