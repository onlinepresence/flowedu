<div class="mx-auto max-w-7xl space-y-6">
    <!-- Tabs Header -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
                type="button"
                wire:click="$set('activeTab', 'existing')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'existing' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-gray-500 hover:text-gray-750 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <i class="fa-solid fa-table-list mr-1.5 text-xs"></i>
                {{ __('Existing Timetables') }}
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'create')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'create' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-gray-500 hover:text-gray-750 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <i class="fa-solid fa-plus-circle mr-1.5 text-xs"></i>
                {{ __('Create / Open Timetable') }}
            </button>
        </nav>
    </div>

    <!-- Active Panels -->
    @if ($activeTab === 'create')
        <x-card class="p-6">
            <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white mb-2">
                {{ __('Create or open timetable') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Pick program, level, and session. An existing timetable for that combination opens; otherwise one is created.') }}</p>
            <form wire:submit="loadOrCreateTimetable" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="tt-program" :value="__('Program')" />
                    <x-select-input
                        id="tt-program"
                        wire:model="createProgramId"
                        class="mt-1 block w-full"
                    >
                        <option value="">{{ __('Select program') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('createProgramId')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="tt-level" :value="__('Level')" />
                    <x-select-input
                        id="tt-level"
                        wire:model="createLevel"
                        class="mt-1 block w-full"
                    >
                        <option value="">{{ __('Select level') }}</option>
                        <option value="100">{{ __('Level 100') }}</option>
                        <option value="200">{{ __('Level 200') }}</option>
                        <option value="300">{{ __('Level 300') }}</option>
                        <option value="400">{{ __('Level 400') }}</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('createLevel')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="tt-session" :value="__('Academic session')" />
                    <x-select-input
                        id="tt-session"
                        wire:model="createSessionId"
                        class="mt-1 block w-full"
                    >
                        <option value="">{{ __('Select session') }}</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}">{{ $s->name ?? __('Session #:id', ['id' => $s->id]) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('createSessionId')" class="mt-1" />
                </div>
                <div class="md:col-span-3 pt-2">
                    <x-college-form-submit target="loadOrCreateTimetable">
                        {{ __('Load or Create Timetable') }}
                    </x-college-form-submit>
                </div>
            </form>
        </x-card>
    @else
        <!-- Existing Timetables Panel -->
        @if ($selectedTimetableId !== null)
            @php
                $selectedTimetable = \App\Models\Timetable::query()
                    ->with(['program', 'academicSession', 'classes.course', 'classes.teacher.user'])
                    ->findOrFail($selectedTimetableId);
                $isCurrentSession = (bool) $selectedTimetable->academicSession?->is_current;
            @endphp
            <!-- Weekly Schedule Editor/Viewer -->
            <div class="space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" wire:click="selectTimetable(null)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-purple-600 hover:text-purple-500">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        {{ __('Back to all timetables') }}
                    </button>
                    @if ($isCurrentSession)
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="confirmDeleteTimetable({{ $selectedTimetable->id }})"
                                class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-trash mr-1.5 text-xs"></i>
                                {{ __('Delete Timetable') }}
                            </button>
                            <button
                                type="button"
                                wire:click="openSlotCreate({{ $selectedTimetable->id }})"
                                class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
                                {{ __('Add Time Slot') }}
                            </button>
                        </div>
                    @endif
                </div>

                <x-card class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $selectedTimetable->program?->name }}
                                @if ($selectedTimetable->level)
                                    · {{ __('Level') }} {{ $selectedTimetable->level }}
                                @endif
                            </h2>
                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $selectedTimetable->academicSession?->name }}
                            </p>
                        </div>
                        <div>
                            @if ($isCurrentSession)
                                <span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                                    <i class="fa-solid fa-circle-check text-xs"></i>
                                    {{ __('Active (Editable)') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 ring-1 ring-inset ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-400 dark:ring-orange-500/20">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                    {{ __('Historical (Read-Only)') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 mt-6">
                        @foreach ($weekDays as $day)
                            @php
                                $dayKey = strtolower($day);
                                $daySlots = $selectedTimetable->classes
                                    ->filter(fn ($slot) => strtolower((string) $slot->day) === $dayKey)
                                    ->sortBy('start_time');
                            @endphp
                            <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <h3 class="text-sm font-bold text-gray-950 dark:text-white mb-3 flex items-center gap-2">
                                    <i class="fa-regular fa-calendar-day text-purple-600 dark:text-purple-400"></i>
                                    {{ $day }}
                                </h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    @forelse ($daySlots as $slot)
                                        <div wire:key="slot-card-{{ $slot->id }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400">
                                                        {{ $slot->course?->code ?? '—' }}
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono font-bold">
                                                        @if ($slot->start_time && $slot->end_time)
                                                            {{ \Illuminate\Support\Str::substr((string) $slot->start_time, 0, 5) }} - {{ \Illuminate\Support\Str::substr((string) $slot->end_time, 0, 5) }}
                                                        @else
                                                            —
                                                        @endif
                                                    </span>
                                                </div>
                                                <h4 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $slot->course?->name ?? __('No course title') }}</h4>
                                                <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                                    <p>
                                                        <i class="fa-solid fa-chalkboard-user mr-1 text-gray-400"></i>
                                                        {{ __('Lecturer') }}: <span class="font-medium text-gray-900 dark:text-gray-200">{{ $slot->teacher ? (trim(($slot->teacher->lastname ?? '').' '.($slot->teacher->othernames ?? '')) ?: ($slot->teacher->user?->username ?? '—')) : '—' }}</span>
                                                    </p>
                                                    <p>
                                                        <i class="fa-solid fa-location-dot mr-1.5 text-gray-400"></i>
                                                        {{ __('Venue') }}: <span class="font-medium text-gray-900 dark:text-gray-200">{{ $slot->venue ?? '—' }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                            @if ($isCurrentSession)
                                                <div class="mt-4 pt-3 border-t border-gray-150 dark:border-gray-700 flex justify-end gap-3 text-sm">
                                                    <button type="button" wire:click="openSlotEdit({{ $slot->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-105 transition-transform" title="{{ __('Edit Slot') }}">
                                                        <i class="fa-solid fa-pen mr-1"></i>{{ __('Edit') }}
                                                    </button>
                                                    <button type="button" wire:click="confirmDeleteSlot({{ $slot->id }})" class="text-red-650 hover:text-red-500 hover:scale-105 transition-transform" title="{{ __('Delete Slot') }}">
                                                        <i class="fa-solid fa-trash mr-1"></i>{{ __('Delete') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-md border border-dashed border-gray-300 bg-white px-4 py-4 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-450 md:col-span-2">
                                            {{ __('No classes added for this day.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        @else
            <!-- Directory List View -->
            <div class="space-y-6">
                <!-- Filters Grid -->
                <x-card class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4"><i class="fa-solid fa-filter mr-1.5 text-xs text-purple-600"></i>{{ __('Filter Timetables') }}</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="filter-program" :value="__('Program')" />
                            <x-select-input id="filter-program" wire:model.live="filterProgramId" class="mt-1 block w-full text-sm">
                                <option value="">{{ __('All programs') }}</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label for="filter-level" :value="__('Level')" />
                            <x-select-input id="filter-level" wire:model.live="filterLevel" class="mt-1 block w-full text-sm">
                                <option value="">{{ __('All levels') }}</option>
                                <option value="100">{{ __('Level 100') }}</option>
                                <option value="200">{{ __('Level 200') }}</option>
                                <option value="300">{{ __('Level 300') }}</option>
                                <option value="400">{{ __('Level 400') }}</option>
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label for="filter-session" :value="__('Academic Session')" />
                            <x-select-input id="filter-session" wire:model.live="filterSessionId" class="mt-1 block w-full text-sm">
                                <option value="">{{ __('All sessions') }}</option>
                                @foreach ($sessions as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                    </div>
                </x-card>

                <!-- Bulk Actions Toolbar -->
                @if (!empty($selectedIds))
                    @php
                        $canDeleteSelected = true;
                        $canDeleteSelected = ! \App\Models\Timetable::query()
                            ->whereIn('id', $selectedIds)
                            ->whereHas('academicSession', fn($q) => $q->where('is_current', false))
                            ->exists();
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-purple-50 dark:bg-purple-950/20 px-6 py-3 rounded-lg border border-purple-200 dark:border-purple-800">
                        <span class="text-sm font-semibold text-purple-900 dark:text-purple-200">
                            {{ __(':count item(s) selected', ['count' => count($selectedIds)]) }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="openBulkDuplicateModal"
                                class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-500"
                            >
                                <i class="fa-solid fa-copy"></i>
                                {{ __('Duplicate Selected') }}
                            </button>
                            @if ($canDeleteSelected)
                                <button
                                    type="button"
                                    x-on:click="$dispatch('open-modal', 'confirm-bulk-delete-timetables-modal')"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                    {{ __('Delete Selected') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Timetables List -->
                <x-card class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="w-12 px-6 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900"
                                            @if($timetables->isEmpty()) disabled @endif
                                            @if($timetables->isNotEmpty() && count($selectedIds) === $timetables->count()) checked @endif
                                            wire:click="$set('selectedIds', count($selectedIds) === {{ $timetables->count() }} ? [] : [{{ implode(',', $timetables->pluck('id')->toArray()) }}])"
                                        />
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Program / Class') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Level') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Academic Session') }}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($timetables as $tt)
                                    @php
                                        $isCurrent = (bool) $tt->academicSession?->is_current;
                                    @endphp
                                    <tr wire:key="tt-row-{{ $tt->id }}">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                value="{{ $tt->id }}"
                                                wire:model.live="selectedIds"
                                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900"
                                            />
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $tt->program?->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300">
                                            {{ __('Level') }} {{ $tt->level }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300 font-mono">
                                            {{ $tt->academicSession?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <div class="flex items-center justify-end gap-3.5">
                                                @if ($isCurrent)
                                                    <button type="button" wire:click="selectTimetable({{ $tt->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('Manage Slots') }}">
                                                        <i class="fa-solid fa-calendar-day text-base"></i>
                                                    </button>
                                                    <button type="button" wire:click="confirmDeleteTimetable({{ $tt->id }})" class="text-red-600 hover:text-red-500 hover:scale-110 transition-transform" title="{{ __('Delete') }}">
                                                        <i class="fa-solid fa-trash text-base"></i>
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="selectTimetable({{ $tt->id }})" class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform" title="{{ __('View Schedule (Read-Only)') }}">
                                                        <i class="fa-solid fa-eye text-base"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8">
                                            <x-college.empty-state
                                                :title="__('No timetables found')"
                                                :description="__('Switch to the \'Create\' tab to add a new schedule.')"
                                                class="border-none bg-transparent py-4"
                                            >
                                                <x-slot:icon>
                                                    <i class="fa-solid fa-table-list text-4xl text-gray-300 dark:text-gray-600 block"></i>
                                                </x-slot:icon>
                                            </x-college.empty-state>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($timetables->hasPages())
                        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            {{ $timetables->links() }}
                        </div>
                    @endif
                </x-card>
            </div>
        @endif
    @endif

    <!-- Time Slot Editor Modal -->
    @php
        $modalCourses = $slotTimetableId && isset($courseLists[$slotTimetableId]) ? $courseLists[$slotTimetableId] : collect();
    @endphp
    <x-modal name="timetable-slot" focusable maxWidth="xl">
        <form wire:submit.prevent="saveSlot(true)" class="space-y-4 p-6">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $editingSlotId ? __('Edit Time Slot') : __('Add Time Slot') }}
                </h2>
                <button type="button" wire:click="cancelSlotModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="{{ __('Close') }}">
                    <i class="fa-solid fa-times text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="slot-day" :value="__('Day')" />
                    <x-select-input id="slot-day" wire:model="slotDay" class="mt-1 block w-full" required>
                        <option value="">{{ __('Select day') }}</option>
                        @foreach ($weekDays as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('slotDay')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="slot-venue" :value="__('Venue')" />
                    <x-text-input id="slot-venue" type="text" wire:model="slotVenue" class="mt-1 block w-full" placeholder="e.g. Room 204" required />
                    <x-input-error :messages="$errors->get('slotVenue')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="slot-start" :value="__('Start')" />
                    <x-text-input id="slot-start" type="time" wire:model="slotStart" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('slotStart')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="slot-end" :value="__('End')" />
                    <x-text-input id="slot-end" type="time" wire:model="slotEnd" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('slotEnd')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="slot-course" :value="__('Course')" />
                    <x-select-input id="slot-course" wire:model="slotCourseId" class="mt-1 block w-full" required>
                        <option value="">{{ __('Select course') }}</option>
                        @foreach ($modalCourses as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('slotCourseId')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="slot-teacher" :value="__('Teacher')" />
                    <x-select-input id="slot-teacher" wire:model="slotTeacherId" class="mt-1 block w-full" required>
                        <option value="">{{ __('Select teacher') }}</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ trim(($t->othernames ?? '').' '.($t->lastname ?? '')) ?: ($t->user?->username ?? $t->id) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('slotTeacherId')" class="mt-1" />
                </div>
                <div class="sm:col-span-2 py-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="slotConfirmTeacherReassign" id="confirm-reassign" class="sr-only peer" />
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        <span class="ms-3 text-xs font-semibold text-gray-750 dark:text-gray-400">{{ __('Confirm teacher reassignment if course has another teacher') }}</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button type="button" wire:click="cancelSlotModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                <button type="button" wire:click="saveSlot(false)" class="rounded-md bg-white border border-purple-600 px-4 py-2 text-sm font-semibold text-purple-600 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">{{ __('Save & Add Another') }}</button>
                <x-college-form-submit target="saveSlot(true)">{{ __('Save & Exit') }}</x-college-form-submit>
            </div>
        </form>
    </x-modal>

    <!-- Bulk Duplication Modal -->
    <x-modal name="bulk-duplicate-timetable-modal" focusable maxWidth="lg">
        <form wire:submit.prevent="duplicateSelectedTimetables" class="space-y-4 p-6">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ __('Duplicate Timetable(s)') }}
                </h2>
                <button type="button" wire:click="$dispatch('close-modal', 'bulk-duplicate-timetable-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="{{ __('Close') }}">
                    <i class="fa-solid fa-times text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Select the target academic session to clone the selected weekly timetable schedule(s) into. Existing programs/levels in the target session will be skipped.') }}
            </p>

            <div>
                <x-input-label for="dup-target-session" :value="__('Target Academic Session')" />
                <x-select-input id="dup-target-session" wire:model="duplicateTargetSessionId" class="mt-1 block w-full" required>
                    <option value="">{{ __('Select session') }}</option>
                    @foreach ($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('duplicateTargetSessionId')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button type="button" wire:click="$dispatch('close-modal', 'bulk-duplicate-timetable-modal')" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                <x-college-form-submit target="duplicateSelectedTimetables">{{ __('Clone Timetable(s)') }}</x-college-form-submit>
            </div>
        </form>
    </x-modal>

    <!-- Delete Slot Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-slot-modal"
        type="danger"
        :title="__('Delete Time Slot')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteSlot"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to remove this time slot from the schedule?') }}
        </p>
    </x-college.confirm-modal>

    <!-- Delete Timetable Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-timetable-modal"
        type="danger"
        :title="__('Delete Timetable')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteTimetable"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this timetable and all its scheduled class slots?') }}
        </p>
    </x-college.confirm-modal>

    <!-- Bulk Delete Timetable Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-bulk-delete-timetables-modal"
        type="danger"
        :title="__('Delete Selected Timetables')"
        confirmText="{{ __('Delete All') }}"
        wireConfirm="deleteSelectedTimetables"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete the selected timetables and all their class slots? This action is permanent.') }}
        </p>
    </x-college.confirm-modal>
</div>
