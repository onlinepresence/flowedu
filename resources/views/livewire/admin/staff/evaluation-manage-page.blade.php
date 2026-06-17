<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <a
            href="{{ route('admin.evaluation.preview', ['form_code' => $form_code]) }}"
            wire:navigate
            class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3.5 py-2 text-sm font-bold text-purple-700 hover:bg-purple-100 dark:border-purple-900/30 dark:bg-purple-950/20 dark:text-purple-400 dark:hover:bg-purple-900/40 transition-colors shadow-sm"
        >
            <span>{{ __('Preview Live Form') }}</span>
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
        </a>
    </x-slot>

    <!-- Standard Tabs Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Tabs">
            @php
                $tabs = [
                    'questions' => __('Questions'),
                    'details' => __('Details & schedule'),
                    'reporting' => __('Reporting'),
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <a
                    href="{{ route('admin.evaluation', ['form_code' => $form_code, 'tab' => $key]) }}"
                    wire:navigate
                    @class([
                        'border-b-2 px-1 py-2 text-sm font-bold tracking-wide transition-colors duration-200',
                        'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-300' => $tab === $key,
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    @if ($tab === 'details')
        <!-- Details & Schedule Configuration -->
        <x-card :title="__('Form Details & Schedule')">
            <form wire:submit="saveDetails" class="grid gap-4 sm:grid-cols-2 mt-4 text-sm">
                <div class="sm:col-span-2">
                    <x-input-label :value="__('Form Title')" class="text-sm font-bold" />
                    <x-text-input wire:model="title" type="text" class="mt-1 block w-full text-sm font-semibold" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label :value="__('Academic Year')" class="text-sm font-bold" />
                    <x-text-input wire:model="academic_year" type="text" placeholder="2025/2026" readonly class="mt-1 block w-full bg-gray-100 dark:bg-gray-800 font-mono text-sm text-gray-650 cursor-not-allowed border-gray-200 dark:border-gray-750" />
                </div>
                <div>
                    <x-input-label :value="__('Control Mechanism')" class="text-sm font-bold" />
                    <x-select-input wire:model="control_type" class="mt-1 block w-full text-sm font-semibold">
                        <option value="auto">{{ __('Automatic (time-based)') }}</option>
                        <option value="manual">{{ __('Manual Toggle') }}</option>
                    </x-select-input>
                </div>
                <div>
                    <x-input-label :value="__('Opens')" class="text-sm font-bold" />
                    <x-text-input wire:model="start_time" type="datetime-local" class="mt-1 block w-full font-mono text-sm" required />
                    <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                </div>
                <div>
                    <x-input-label :value="__('Closes')" class="text-sm font-bold" />
                    <x-text-input wire:model="end_time" type="datetime-local" class="mt-1 block w-full font-mono text-sm" required />
                    <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                </div>
                
                <!-- Premium Active Switch Toggle -->
                <div class="sm:col-span-2 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-lg border border-gray-150 dark:border-gray-800 flex items-center justify-between mt-2">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Form Active State') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold leading-relaxed mt-0.5">
                            {{ __('Toggle whether students can access and fill out this evaluation form.') }}
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer" id="ev-active-chk">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="sm:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-end">
                    <x-college-form-submit target="saveDetails" variant="purple" class="rounded-lg font-semibold text-sm">{{ __('Save changes') }}</x-college-form-submit>
                </div>
            </form>
        </x-card>

    @elseif ($tab === 'reporting')
        <!-- Reporting Analytics View -->
        <div class="space-y-6">
            
            <!-- Standard Stats Cards Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-college.stats-card
                    :title="__('Total responses')"
                    :value="$stats['responses']"
                    color="purple"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                    </x-slot:icon>
                </x-college.stats-card>

                <x-college.stats-card
                    :title="__('Submitted responses')"
                    :value="$stats['submitted']"
                    color="green"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </x-slot:icon>
                </x-college.stats-card>

                <x-college.stats-card
                    :title="__('Evaluated Lecturers')"
                    :value="$stats['teachers_evaluated']"
                    color="indigo"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75c-1.058 0-2.04.277-2.89.761m0 3.438a5.98 5.98 0 0 0 2.89-.76" /></svg>
                    </x-slot:icon>
                </x-college.stats-card>

                <x-college.stats-card
                    :title="__('Participation Rate')"
                    :value="$stats['participation_rate'] . '%'"
                    color="sky"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                    </x-slot:icon>
                </x-college.stats-card>
            </div>

            <!-- Two Column Layout: Teacher Directory + Analytics Content -->
            <div class="grid gap-6 md:grid-cols-3">
                
                <!-- Left Column: Directory -->
                <div class="space-y-6 md:col-span-1" wire:key="dir-col">
                    <x-card :title="__('Lecturer Directory')">
                        <!-- Search field -->
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.live="searchTeacher"
                                class="block w-full rounded-md border-gray-300 pr-8 text-sm font-semibold focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm"
                                placeholder="{{ __('Search lecturer…') }}"
                            />
                            @if ($searchTeacher)
                                <button type="button" wire:click="$set('searchTeacher', '')" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-655">
                                    <i class="fa-solid fa-xmark text-sm"></i>
                                </button>
                            @endif
                        </div>

                        <!-- Directory List -->
                        <div class="mt-4 space-y-2 max-h-[500px] overflow-y-auto pr-1">
                            <button
                                wire:click="selectTeacher(null)"
                                @class([
                                    'w-full text-left p-3.5 rounded-lg border text-sm font-bold transition-all flex items-center justify-between',
                                    'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-900/60 dark:bg-purple-950/20 dark:text-purple-400 shadow-sm' => is_null($selectedTeacherId),
                                    'border-gray-200 hover:bg-gray-50 text-gray-700 dark:border-gray-700 dark:hover:bg-gray-900/40 dark:text-gray-300' => !is_null($selectedTeacherId)
                                ])
                            >
                                <span>{{ __('All Evaluated Lecturers') }}</span>
                                <span class="bg-gray-150 dark:bg-gray-800 px-2 py-0.5 rounded text-xs font-semibold border border-gray-205 dark:border-gray-700">{{ $teachers->sum('response_count') }}</span>
                            </button>

                            @forelse ($teachers as $teacher)
                                <button
                                    wire:click="selectTeacher({{ $teacher->user_id }})"
                                    @class([
                                        'w-full text-left p-3.5 rounded-lg border text-sm font-bold transition-all flex items-center justify-between',
                                        'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-900/60 dark:bg-purple-950/20 dark:text-purple-400 shadow-sm' => $selectedTeacherId === $teacher->user_id,
                                        'border-gray-200 hover:bg-gray-50 text-gray-755 dark:border-gray-700 dark:hover:bg-gray-900/40 dark:text-gray-300' => $selectedTeacherId !== $teacher->user_id
                                    ])
                                >
                                    <div class="min-w-0 pr-2">
                                        <p class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $teacher->user->name }}</p>
                                        <p class="text-xs text-gray-450 dark:text-gray-500 font-semibold mt-1 truncate">{{ $teacher->department->name ?? __('N/A') }}</p>
                                    </div>
                                    <span class="bg-gray-150 dark:bg-gray-800 px-2 py-0.5 rounded text-xs font-semibold border border-gray-205 dark:border-gray-700 flex-shrink-0">
                                        {{ $teacher->response_count ?? 0 }}
                                    </span>
                                </button>
                            @empty
                                <div class="text-center py-8 text-sm text-gray-450 dark:text-gray-500 italic font-semibold">
                                    {{ __('No lecturers matching directory filters.') }}
                                </div>
                            @endforelse
                        </div>
                    </x-card>
                </div>

                <!-- Right Column: Analytics & Details -->
                <div class="space-y-6 md:col-span-2 relative" wire:key="ans-col">
                    
                    <!-- Updating Loader screen -->
                    <div wire:loading.delay.flex style="display: none;" class="absolute inset-0 z-35 bg-white/70 backdrop-blur-[1px] items-center justify-center dark:bg-gray-900/70 rounded-lg">
                        <div class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 px-6 py-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">
                            <i class="fa-solid fa-spinner fa-spin text-3xl text-purple-600 dark:text-purple-450"></i>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 animate-pulse">{{ __('Updating report data…') }}</span>
                        </div>
                    </div>

                    <!-- Toolbar + Filter Card Section -->
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-lg border border-gray-200 dark:border-gray-755 shadow-sm">
                            <h3 class="text-sm font-extrabold text-gray-855 dark:text-gray-300 uppercase tracking-wide">
                                @if ($selectedTeacherId)
                                    @php $selTeacher = $teachers->firstWhere('user_id', $selectedTeacherId); @endphp
                                    {{ __('Performance: :name', ['name' => $selTeacher?->user->name ?? '']) }}
                                @else
                                    {{ __('Aggregated Performance (All)') }}
                                @endif
                            </h3>
                            
                            <div class="flex items-center gap-2">
                                <!-- View Mode Switcher -->
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-bold text-gray-755 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-305 dark:hover:bg-gray-700 transition-colors shadow-sm"
                                    >
                                        <i class="fa-solid fa-sliders text-gray-400"></i>
                                        <span>{{ $reportView === 'summarized' ? __('Summarized') : __('Detailed') }}</span>
                                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-transition
                                        class="absolute right-0 mt-1 w-40 origin-top-right rounded-lg bg-white p-1 shadow-md ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700 z-40 border border-gray-150 dark:border-gray-700"
                                        style="display: none;"
                                    >
                                        <button
                                            type="button"
                                            wire:click="$set('reportView', 'summarized')"
                                            @click="open = false"
                                            class="flex w-full items-center px-4 py-2 text-sm font-semibold rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-750 dark:text-gray-300"
                                        >
                                            {{ __('Summarized') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="$set('reportView', 'detailed')"
                                            @click="open = false"
                                            class="flex w-full items-center px-4 py-2 text-sm font-semibold rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-750 dark:text-gray-300"
                                        >
                                            {{ __('Detailed') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Exporter Dropdown Menu -->
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3.5 py-2 text-sm font-bold text-purple-750 hover:bg-purple-100 dark:border-purple-900/30 dark:bg-purple-950/20 dark:text-purple-400 dark:hover:bg-purple-900/40 transition-colors shadow-sm"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        <span>{{ __('Export') }}</span>
                                        <i class="fa-solid fa-chevron-down text-[10px] text-purple-500"></i>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-transition
                                        class="absolute right-0 mt-1 w-48 origin-top-right rounded-lg bg-white p-1 shadow-md ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700 z-40 border border-gray-150 dark:border-gray-700"
                                        style="display: none;"
                                    >
                                        <button
                                            type="button"
                                            wire:click="downloadReport('xlsx', {{ $selectedTeacherId ?? 'null' }})"
                                            @click="open = false"
                                            class="flex w-full items-center px-4 py-2.5 text-sm font-semibold rounded text-left text-gray-700 dark:text-gray-350 hover:bg-purple-50 hover:text-purple-750 dark:hover:bg-purple-950/40 dark:hover:text-purple-300"
                                        >
                                            <i class="fa-regular fa-file-excel mr-2 text-green-600 text-base"></i>
                                            {{ __('Excel Spreadsheet') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="downloadReport('csv', {{ $selectedTeacherId ?? 'null' }})"
                                            @click="open = false"
                                            class="flex w-full items-center px-4 py-2.5 text-sm font-semibold rounded text-left text-gray-700 dark:text-gray-350 hover:bg-purple-50 hover:text-purple-750 dark:hover:bg-purple-950/40 dark:hover:text-purple-300"
                                        >
                                            <i class="fa-solid fa-file-csv mr-2 text-blue-500 text-base"></i>
                                            {{ __('CSV File') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top level demographic filter card -->
                        <div class="grid gap-4 sm:grid-cols-3 bg-white dark:bg-gray-850 p-4 rounded-lg border border-gray-200 dark:border-gray-750 shadow-sm text-sm">
                            <div>
                                <x-input-label :value="__('Department')" class="text-xs font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500" />
                                <select
                                    wire:model.live="filterDepartmentId"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm font-semibold dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                >
                                    <option value="">{{ __('All Departments') }}</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label :value="__('Program (Class)')" class="text-xs font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500" />
                                <select
                                    wire:model.live="filterProgramId"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm font-semibold dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                >
                                    <option value="">{{ __('All Programs') }}</option>
                                    @foreach ($programs as $prog)
                                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label :value="__('Year Level')" class="text-xs font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500" />
                                <select
                                    wire:model.live="filterYearLevel"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm font-semibold dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                >
                                    <option value="">{{ __('All Levels') }}</option>
                                    <option value="100">{{ __('Level 100') }}</option>
                                    <option value="200">{{ __('Level 200') }}</option>
                                    <option value="300">{{ __('Level 300') }}</option>
                                    <option value="400">{{ __('Level 400') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if ($reportView === 'summarized')
                        <x-card :title="__('Question-by-Question Analytics')">
                            <div class="divide-y divide-gray-200 dark:divide-gray-700 mt-4 space-y-6">
                                @forelse ($questions as $q)
                                    @php
                                        $qStat = $questionStats[$q->id] ?? [
                                            'total' => 0,
                                            'avg' => null,
                                            'distribution' => [],
                                            'text_samples' => [],
                                        ];
                                    @endphp
                                    <div class="pt-6 first:pt-2 space-y-4" wire:key="rep-q-{{ $q->id }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="text-base font-bold text-gray-900 dark:text-white leading-snug">
                                                    {{ $q->question_order }}. {{ $q->question_text }}
                                                </h4>
                                                <p class="text-xs text-gray-450 dark:text-gray-500 font-bold mt-1">
                                                    {{ __('Type: :type', ['type' => str_replace('_', ' ', $q->rating_type)]) }} · 
                                                    {{ trans_choice('{0} No answers yet|{1} 1 response recorded|[2,*] :count responses recorded', $qStat['total'], ['count' => $qStat['total']]) }}
                                                </p>
                                            </div>
                                            @if (!is_null($qStat['avg']))
                                                <div class="text-right flex-shrink-0">
                                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-3 py-1.5 text-sm font-bold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-500/10 dark:text-purple-400 dark:ring-purple-500/20">
                                                        ★ {{ $qStat['avg'] }}/{{ $q->rating_type === 'scale_5' ? 5 : 10 }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($qStat['total'] > 0)
                                            @if (in_array($q->rating_type, ['scale_5', 'scale_10'], true) && !empty($qStat['distribution']))
                                                <div class="space-y-2 max-w-xl">
                                                    @foreach (array_reverse($qStat['distribution'], true) as $score => $count)
                                                        @php $pct = round(($count / $qStat['total']) * 100); @endphp
                                                        <div class="flex items-center text-sm font-bold">
                                                            <span class="w-10 text-gray-600 dark:text-gray-450 font-mono">{{ $score }} ★</span>
                                                            <div class="mx-3 flex-1 h-2.5 bg-gray-100 rounded dark:bg-gray-800 overflow-hidden">
                                                                <div class="h-full bg-purple-600 rounded" style="width: {{ $pct }}%"></div>
                                                            </div>
                                                            <span class="w-20 text-right text-gray-500 dark:text-gray-450 font-mono">{{ $count }} ({{ $pct }}%)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif ($q->rating_type === 'boolean' && !empty($qStat['distribution']))
                                                <div class="space-y-2 max-w-xl text-sm font-bold">
                                                    @php
                                                        $yesCount = $qStat['distribution']['yes'] ?? 0;
                                                        $noCount = $qStat['distribution']['no'] ?? 0;
                                                        $yesPct = $qStat['distribution']['pct_yes'] ?? 0;
                                                        $noPct = 100 - $yesPct;
                                                    @endphp
                                                    <div class="flex items-center">
                                                        <span class="w-12 text-gray-600 dark:text-gray-450 capitalize">{{ __('Yes') }}</span>
                                                        <div class="mx-3 flex-1 h-2.5 bg-gray-100 rounded dark:bg-gray-800 overflow-hidden">
                                                            <div class="h-full bg-green-500 rounded" style="width: {{ $yesPct }}%"></div>
                                                        </div>
                                                        <span class="w-20 text-right text-gray-500 dark:text-gray-450 font-mono">{{ $yesCount }} ({{ $yesPct }}%)</span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="w-12 text-gray-600 dark:text-gray-450 capitalize">{{ __('No') }}</span>
                                                        <div class="mx-3 flex-1 h-2.5 bg-gray-100 rounded dark:bg-gray-800 overflow-hidden">
                                                            <div class="h-full bg-red-500 rounded" style="width: {{ $noPct }}%"></div>
                                                        </div>
                                                        <span class="w-20 text-right text-gray-500 dark:text-gray-450 font-mono">{{ $noCount }} ({{ $noPct }}%)</span>
                                                    </div>
                                                </div>
                                            @elseif (in_array($q->rating_type, ['select_single', 'select_multiple'], true) && !empty($qStat['distribution']))
                                                <div class="space-y-2 max-w-xl text-sm font-bold">
                                                    @foreach ($qStat['distribution'] as $opt => $count)
                                                        @php $pct = round(($count / $qStat['total']) * 100); @endphp
                                                        <div class="flex items-center">
                                                            <span class="w-32 truncate text-gray-600 dark:text-gray-450 font-semibold" title="{{ $opt }}">{{ $opt }}</span>
                                                            <div class="mx-3 flex-1 h-2.5 bg-gray-100 rounded dark:bg-gray-800 overflow-hidden">
                                                                <div class="h-full bg-purple-600 rounded" style="width: {{ $pct }}%"></div>
                                                            </div>
                                                            <span class="w-20 text-right text-gray-500 dark:text-gray-450 font-mono">{{ $count }} ({{ $pct }}%)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif (!empty($qStat['text_samples']))
                                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 p-4 space-y-3">
                                                    @foreach ($qStat['text_samples'] as $sample)
                                                        <div class="text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200/50 dark:border-gray-800 pb-3 last:border-0 last:pb-0 leading-relaxed font-semibold">
                                                            “ {{ $sample }} ”
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($qStat['total'] > 5)
                                                    <div class="mt-3 text-right">
                                                        <button
                                                            type="button"
                                                            wire:click="viewAllTextResponses({{ $q->id }})"
                                                            class="text-sm font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 inline-flex items-center gap-1.5"
                                                        >
                                                            <span>{{ __('View all responses') }} ({{ $qStat['total'] }})</span>
                                                            <i class="fa-solid fa-arrow-right-long text-xs"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-xs text-gray-450 dark:text-gray-500 font-bold italic">{{ __('No text feedback submitted.') }}</p>
                                            @endif
                                        @else
                                            <p class="text-xs text-gray-455 dark:text-gray-500 font-bold italic">{{ __('Waiting for student submissions.') }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <div class="py-6">
                                        <x-college.empty-state
                                            :title="__('No Questions Defined')"
                                            :description="__('This evaluation form does not have any questions yet.')"
                                        >
                                            <x-slot:icon>
                                                <i class="fa-solid fa-circle-question text-4xl text-gray-300 dark:text-gray-600 block flex justify-center mb-2"></i>
                                            </x-slot:icon>
                                        </x-college.empty-state>
                                    </div>
                                @endforelse
                            </div>
                        </x-card>
                    @else
                        <!-- Detailed List View Card -->
                        <x-card :title="__('Individual Submissions (Anonymized)')">
                            <div class="overflow-x-auto mt-4">
                                <table class="w-full text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-5 py-4">{{ __('Response ID') }}</th>
                                            <th class="px-5 py-4">{{ __('Submitted Date') }}</th>
                                            <th class="px-5 py-4">{{ __('Lecturer') }}</th>
                                            <th class="px-5 py-4">{{ __('Student Demographics') }}</th>
                                            <th class="px-5 py-4 text-right">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-150 dark:divide-gray-800">
                                        @forelse ($detailedResponses as $resp)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                                                <td class="px-5 py-4 font-mono text-sm text-purple-600 dark:text-purple-400">
                                                    {{ substr($resp->response_code, 0, 8) }}
                                                </td>
                                                <td class="px-5 py-4 text-sm text-gray-550 dark:text-gray-400 font-mono">
                                                    {{ $resp->submitted_at?->format('Y-m-d H:i') }}
                                                </td>
                                                <td class="px-5 py-4 text-sm font-extrabold text-gray-900 dark:text-white">
                                                    {{ $resp->teacherUser?->name ?? __('N/A') }}
                                                </td>
                                                <td class="px-5 py-4 space-y-1">
                                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-200">{{ $resp->studentUser?->student?->program?->name ?? __('N/A') }}</p>
                                                    <p class="text-xs text-gray-450 dark:text-gray-500">
                                                        {{ $resp->studentDepartment?->name ?? __('N/A') }} · {{ __('Level :level', ['level' => $resp->studentUser?->student?->current_year ?? '']) }}
                                                    </p>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <button
                                                        type="button"
                                                        wire:click="viewResponseDetails({{ $resp->id }})"
                                                        class="rounded-lg bg-purple-50 px-3.5 py-2 text-sm font-bold text-purple-750 hover:bg-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:hover:bg-purple-900/40 transition-colors shadow-sm"
                                                    >
                                                        {{ __('View Answers') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-5 py-10 text-center text-gray-450 dark:text-gray-500 italic">
                                                    {{ __('No responses match selected filters.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($detailedTotal > $detailedPerPage)
                                <div class="flex items-center justify-between border-t border-gray-150 dark:border-gray-800 pt-4 mt-4 text-sm">
                                    <span class="text-gray-550 dark:text-gray-400 font-semibold">
                                        {{ __('Showing :start to :end of :total', [
                                            'start' => ($detailedPage - 1) * $detailedPerPage + 1,
                                            'end' => min($detailedPage * $detailedPerPage, $detailedTotal),
                                            'total' => $detailedTotal
                                        ]) }}
                                    </span>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            wire:click="prevDetailedPage"
                                            @disabled($detailedPage <= 1)
                                            class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-750 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 shadow-sm"
                                        >
                                            &larr; {{ __('Prev') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="nextDetailedPage"
                                            @disabled($detailedPage >= $detailedMaxPage)
                                            class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-750 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 shadow-sm"
                                        >
                                            {{ __('Next') }} &rarr;
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </x-card>
                    @endif
                </div>

            </div>
        </div>

    @else
        <!-- Questions & Preview Builder tab -->
        <div class="grid gap-6 md:grid-cols-5">
            <!-- Left Column: Add Question form -->
            <div class="md:col-span-2">
                <x-card :title="$editingQuestionId ? __('Edit Question') : __('Add Question')">
                    <form wire:submit="saveQuestion" class="space-y-4 mt-4 text-sm">
                        <div>
                            <x-input-label :value="__('Question text')" class="text-sm font-bold" />
                            <x-textarea-input wire:model="new_question_text" rows="3" class="mt-1 block w-full text-sm font-medium" placeholder="{{ __('e.g., Rate the teaching methodology...') }}" required />
                            <x-input-error :messages="$errors->get('new_question_text')" class="mt-1" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label :value="__('Response type')" class="text-sm font-bold" />
                                <x-select-input wire:model.live="new_rating_type" class="mt-1 block w-full text-sm font-semibold">
                                    <option value="scale_5">{{ __('Scale 1–5') }}</option>
                                    <option value="scale_10">{{ __('Scale 1–10') }}</option>
                                    <option value="boolean">{{ __('Yes / No') }}</option>
                                    <option value="text_short">{{ __('Short text') }}</option>
                                    <option value="text_long">{{ __('Long text') }}</option>
                                    <option value="select_single">{{ __('Single choice') }}</option>
                                    <option value="select_multiple">{{ __('Multiple choice') }}</option>
                                </x-select-input>
                            </div>
                            <div>
                                <x-input-label :value="__('Display order')" class="text-sm font-bold" />
                                <x-text-input type="number" min="1" wire:model.number="new_question_order" class="mt-1 block w-full font-mono text-sm font-bold" required />
                                <x-input-error :messages="$errors->get('new_question_order')" class="mt-1" />
                            </div>
                        </div>

                        <!-- Premium custom switch toggle for requirement -->
                        <div class="bg-gray-50 dark:bg-gray-900/30 p-3 rounded-lg border border-gray-150 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Question is required') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="new_is_required" class="sr-only peer" id="q-req-chk">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        @if (in_array($new_rating_type, ['select_single', 'select_multiple'], true))
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-750 space-y-3 bg-gray-50/50 dark:bg-gray-900/20">
                                <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-800 pb-2">
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ __('Choice options') }}</p>
                                    <button type="button" wire:click="addOptionField" class="text-sm font-extrabold text-purple-650 hover:text-purple-700">+ {{ __('Add option') }}</button>
                                </div>
                                <div class="space-y-2">
                                    @foreach ($new_options as $i => $option)
                                        <div class="flex items-center gap-2">
                                            <x-text-input type="text" wire:model="new_options.{{ $i }}" class="block w-full font-semibold text-sm" placeholder="{{ __('Option label') }}" required />
                                            <button type="button" wire:click="removeOptionField({{ $i }})" class="text-gray-400 hover:text-red-655 transition-colors p-1.5" title="{{ __('Remove option') }}">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('new_options')" class="mt-1" />
                            </div>
                        @endif

                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <x-college-form-submit target="saveQuestion" variant="purple" class="rounded-lg font-bold text-sm">
                                {{ $editingQuestionId ? __('Update question') : __('Add question') }}
                            </x-college-form-submit>
                            @if ($editingQuestionId)
                                <button type="button" wire:click="cancelQuestionEdit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-755 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">{{ __('Cancel edit') }}</button>
                            @endif
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Right Column: Question list setup & preview -->
            <div class="md:col-span-3">
                <x-card :title="__('Evaluation Form Preview & Setup')">
                    @if ($questions->isEmpty())
                        <div class="text-center py-10">
                            <p class="text-sm text-gray-450 dark:text-gray-550 font-bold italic">{{ __('No questions defined.') }}</p>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-gray-750 mt-4">
                            @foreach ($questions as $q)
                                <li class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0" wire:key="q-{{ $q->id }}">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white leading-relaxed">
                                            {{ $q->question_order }}. {{ $q->question_text }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold">
                                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2.5 py-0.5 text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-500/10 dark:text-purple-400 dark:ring-purple-500/20 capitalize">
                                                {{ str_replace('_', ' ', $q->rating_type) }}
                                            </span>
                                            @if ($q->is_required)
                                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-0.5 text-rose-700 ring-1 ring-inset ring-rose-600/25 dark:bg-rose-500/10 dark:text-rose-450 dark:ring-rose-500/20">
                                                    {{ __('Required') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-0.5 text-gray-550 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20">
                                                    {{ __('Optional') }}
                                                </span>
                                            @endif
                                            
                                            @if (in_array($q->rating_type, ['select_single', 'select_multiple'], true) && is_array($q->options_json) && $q->options_json !== [])
                                                <span class="text-gray-450 dark:text-gray-550 font-mono truncate max-w-[250px]" title="{{ implode(', ', $q->options_json) }}">
                                                    {{ __('Options') }}: {{ implode(', ', $q->options_json) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <!-- Sort order buttons -->
                                        <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
                                            <button
                                                type="button"
                                                wire:click="moveQuestionUp({{ $q->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center text-xs text-gray-550 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition-colors"
                                                title="{{ __('Move up') }}"
                                            >
                                                ▲
                                            </button>
                                            <div class="h-4 w-px bg-gray-200 dark:bg-gray-700"></div>
                                            <button
                                                type="button"
                                                wire:click="moveQuestionDown({{ $q->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center text-xs text-gray-555 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white transition-colors"
                                                title="{{ __('Move down') }}"
                                            >
                                                ▼
                                            </button>
                                        </div>

                                        <!-- Edit question -->
                                        <button
                                            type="button"
                                            wire:click="editQuestion({{ $q->id }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 hover:text-purple-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-purple-400 shadow-sm transition-colors"
                                            title="{{ __('Edit question') }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Remove question -->
                                        <button
                                            type="button"
                                            wire:click="removeQuestion({{ $q->id }})"
                                            wire:confirm="{{ __('Remove this question?') }}"
                                            wire:loading.attr="disabled"
                                            wire:target="removeQuestion({{ $q->id }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-550 hover:bg-gray-50 hover:text-red-655 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-red-400 shadow-sm transition-colors disabled:opacity-50"
                                            title="{{ __('Remove question') }}"
                                        >
                                            <span wire:loading.remove wire:target="removeQuestion({{ $q->id }})">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </span>
                                            <span wire:loading.delay.200ms wire:target="removeQuestion({{ $q->id }})" wire:loading.class.remove="hidden" class="hidden inline-flex items-center justify-center">
                                                <i class="fa-solid fa-spinner fa-spin text-xs" aria-hidden="true"></i>
                                            </span>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </div>
    @endif

    <!-- Text Responses Pagination Modal -->
    @if ($viewingTextQuestionId !== null && $textQuestion)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4" wire:key="text-modal">
            <div class="relative w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-150 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('All Responses: Question :num', ['num' => $textQuestion->question_order]) }}
                    </h3>
                    <button wire:click="closeTextModal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-105 hover:text-gray-650 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm font-bold text-purple-650 dark:text-purple-400 italic">"{{ $textQuestion->question_text }}"</p>
                    
                    <div class="divide-y divide-gray-100 dark:divide-gray-750 max-h-[350px] overflow-y-auto space-y-3 pr-2 scrollbar-thin">
                        @forelse ($paginatedTextResponses as $row)
                            <div class="pt-3 first:pt-0 space-y-1" wire:key="modal-resp-{{ $row->id }}">
                                <div class="flex justify-between items-center text-xs text-gray-450 dark:text-gray-500 font-semibold font-mono">
                                    <span>{{ __('Response ID') }}: {{ substr($row->response?->response_code, 0, 8) ?? __('Masked') }}</span>
                                    <span>{{ $row->created_at?->format('Y-m-d H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold leading-relaxed">
                                    “ {{ $row->answer_text }} ”
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-450 dark:text-gray-500 font-semibold italic text-center py-6">{{ __('No responses recorded.') }}</p>
                        @endforelse
                    </div>

                    @if ($textTotal > $textPerPage)
                        <div class="flex items-center justify-between border-t border-gray-150 dark:border-gray-700 pt-4 text-sm font-semibold">
                            <span class="text-gray-550 dark:text-gray-400">
                                {{ __('Showing :start to :end of :total', [
                                    'start' => ($textPage - 1) * $textPerPage + 1,
                                    'end' => min($textPage * $textPerPage, $textTotal),
                                    'total' => $textTotal
                                ]) }}
                            </span>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    wire:click="prevTextPage"
                                    @disabled($textPage <= 1)
                                    class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-750 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-750 dark:bg-gray-800 dark:text-gray-250 dark:hover:bg-gray-700 shadow-sm"
                                >
                                    &larr; {{ __('Prev') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="nextTextPage"
                                    @disabled($textPage >= $textMaxPage)
                                    class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-750 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-750 dark:bg-gray-800 dark:text-gray-250 dark:hover:bg-gray-700 shadow-sm"
                                >
                                    {{ __('Next') }} &rarr;
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Response Detail Modal -->
    @if ($viewingResponseId !== null && $viewingResponse)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4" wire:key="resp-details-modal">
            <div class="relative w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-150 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('Submission Details: :code', ['code' => substr($viewingResponse->response_code, 0, 8)]) }}
                    </h3>
                    <button wire:click="closeResponseDetailsModal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-105 hover:text-gray-650 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2 text-sm font-semibold bg-gray-55 dark:bg-gray-900/30 p-3 rounded-lg border border-gray-150 dark:border-gray-800">
                        <div>
                            <span class="text-xs text-gray-450 dark:text-gray-500 font-bold uppercase">{{ __('Evaluated Lecturer') }}</span>
                            <p class="font-extrabold text-gray-900 dark:text-white mt-1">{{ $viewingResponse->teacherUser?->name ?? __('N/A') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-450 dark:text-gray-500 font-bold uppercase">{{ __('Submitted At') }}</span>
                            <p class="font-bold text-gray-900 dark:text-white font-mono mt-1">{{ $viewingResponse->submitted_at?->format('Y-m-d H:i') ?? __('N/A') }}</p>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-150 dark:divide-gray-750 max-h-[350px] overflow-y-auto space-y-3.5 pr-2 scrollbar-thin">
                        @foreach ($viewingResponse->details as $idx => $detail)
                            <div class="pt-3.5 first:pt-0 space-y-2" wire:key="detail-ans-{{ $detail->id }}">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-relaxed">
                                    {{ $idx + 1 }}. {{ $detail->question?->question_text ?? $detail->question_text_snapshot }}
                                </h4>
                                <div class="pl-3">
                                    @if (in_array($detail->question?->rating_type, ['scale_5', 'scale_10'], true))
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold text-purple-655 dark:text-purple-400 font-mono">{{ $detail->answer_value }} ★</span>
                                            <span class="text-xs text-gray-455 font-semibold">({{ __('Scale :max', ['max' => $detail->question?->rating_type === 'scale_5' ? 5 : 10]) }})</span>
                                        </div>
                                    @elseif ($detail->question?->rating_type === 'boolean')
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset',
                                            'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-455 dark:ring-green-500/30' => $detail->answer_value === 1,
                                            'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-455 dark:ring-red-500/30' => $detail->answer_value === 0,
                                        ])>
                                            {{ $detail->answer_value === 1 ? __('Yes') : __('No') }}
                                        </span>
                                    @elseif ($detail->question?->rating_type === 'select_multiple')
                                        @php
                                            $decoded = json_decode($detail->answer_text ?? '', true);
                                        @endphp
                                        <div class="flex flex-wrap gap-1">
                                            @forelse (is_array($decoded) ? $decoded : [] as $opt)
                                                <span class="inline-flex items-center rounded bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-755 ring-1 ring-inset ring-purple-500/10 dark:bg-purple-950/20 dark:text-purple-400 dark:ring-purple-400/25">
                                                    {{ $opt }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-450 italic font-semibold">{{ __('None selected') }}</span>
                                            @endforelse
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-700 dark:text-gray-300 font-semibold bg-gray-50/50 dark:bg-gray-900/60 p-3 rounded border border-gray-150 dark:border-gray-800 leading-relaxed italic">
                                            “ {{ $detail->answer_text }} ”
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
