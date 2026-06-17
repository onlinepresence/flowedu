<x-slot name="headerActions">
    <a href="{{ route('teacher.attendance') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
        <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
        {{ __('Take attendance') }}
    </a>
</x-slot>

<div class="mx-auto max-w-7xl">

    <!-- Premium Filter Card Container -->
    <x-college.filter-card cols="4" class="mb-6">
        <div>
            <label for="stu-course-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Filter by Course') }}</label>
            <select wire:model.live="filterCourseCode" id="stu-course-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                <option value="">{{ __('All courses') }}</option>
                @foreach ($courseOptions as $c)
                    <option value="{{ $c->code }}">{{ $c->code }} — {{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="stu-semester-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Filter by Semester') }}</label>
            <select wire:model.live="selectedSemester" id="stu-semester-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                <option value="all">{{ __('All Semesters') }}</option>
                <option value="1">{{ __('First Semester') }}</option>
                <option value="2">{{ __('Second Semester') }}</option>
            </select>
        </div>
        <div>
            <label for="stu-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search Student') }}</label>
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" id="stu-search" type="search" placeholder="{{ __('Name or Index Number…') }}" class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500" />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637Z" /></svg>
                </div>
            </div>
        </div>
        <div class="flex items-end">
            <button type="button" wire:click="resetFilters" class="w-full rounded-lg border border-gray-350 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                {{ __('Reset filters') }}
            </button>
        </div>
    </x-college.filter-card>

    @if ($rows->isEmpty())
        <x-college.empty-state
            :title="__('No students found')"
            :description="__('No students are currently enrolled in your courses or match your filter criteria for this active session.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Index Number') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Course') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Semester') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-750 dark:bg-gray-800">
                        @foreach ($rows as $row)
                            @php
                                $levelNum = isset($row->course_year_level) && is_numeric($row->course_year_level) ? (int) $row->course_year_level * 100 : null;
                                $semLabel = match ((string) $row->course_semester) {
                                    '1' => __('1st Semester'),
                                    '2' => __('2nd Semester'),
                                    default => '—',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-850 transition-colors" wire:key="stu-row-{{ $row->id }}-{{ $row->enrolled_course_code ?? '' }}">
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row->index_number }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <x-college.avatar :name="$row->firstname . ' ' . $row->lastname" size="sm" class="mr-3" />
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $row->lastname }}, {{ $row->firstname }} {{ $row->othernames }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-mono font-semibold">{{ $row->enrolled_course_code ?? '—' }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 block max-w-xs truncate">{{ $row->enrolled_course_name ?? '' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $semLabel }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $levelNum !== null ? __('Level :level', ['level' => $levelNum]) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold">
                                    <button type="button" wire:click="showStudentModal({{ $row->id }}, 'performance')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors mr-3">
                                        <i class="fa-solid fa-chart-line mr-1"></i>
                                        {{ __('Performance') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <button type="button" wire:click="showStudentModal({{ $row->id }}, 'profile')" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 transition-colors ml-3">
                                        <i class="fa-solid fa-user mr-1"></i>
                                        {{ __('Profile') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Profile Detail Modal -->
    @if ($selectedStudentId !== null && $modalType === 'profile' && $selectedStudent !== null)
        <x-college.modal name="student-profile-modal" show="true" livewireSynced="true" title="{{ __('Student Profile Details') }}" maxWidth="2xl">
            <div class="space-y-6">
                <!-- Card Header Avatar -->
                <div class="flex items-center space-x-4 border-b border-gray-100 pb-4 dark:border-gray-750">
                    <x-college.avatar :name="$selectedStudent->firstname . ' ' . $selectedStudent->lastname" size="lg" />
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $selectedStudent->lastname }}, {{ $selectedStudent->firstname }} {{ $selectedStudent->othernames }}</h3>
                        <p class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ __('Index: :index', ['index' => $selectedStudent->index_number]) }}</p>
                    </div>
                </div>

                <!-- Basic Profile Grid -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('Academic Information') }}</h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/30">
                            <span class="text-xs text-gray-400 block">{{ __('Program') }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedStudent->program?->name ?? '—' }}</span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/30">
                            <span class="text-xs text-gray-400 block">{{ __('Current Level') }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Level {{ $selectedStudent->current_year ?? '—' }}</span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/30">
                            <span class="text-xs text-gray-400 block">{{ __('Admission Date') }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $selectedStudent->admission_date ? $selectedStudent->admission_date->format('M d, Y') : '—' }}</span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/30">
                            <span class="text-xs text-gray-400 block">{{ __('Gender & DOB') }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ ucfirst($selectedStudent->gender ?? '—') }} / {{ $selectedStudent->date_of_birth ? $selectedStudent->date_of_birth->format('M d, Y') : '—' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Contact & Guardian Grid -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('Contact & Guardian Information') }}</h4>
                    <div class="space-y-4">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/30">
                            <span class="text-xs text-gray-400 block">{{ __('Student Email & Phone') }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 block">{{ $selectedStudent->user?->email ?? '—' }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedStudent->phone_number ?? '—' }}</span>
                        </div>

                        @if ($parentGuardian)
                            <div class="rounded-lg border border-indigo-100 bg-indigo-50/20 p-4 dark:border-indigo-900/30 dark:bg-indigo-950/10">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400">{{ __('Emergency / Guardian Details') }}</span>
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">{{ ucfirst($parentGuardian->relationship) }}</span>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                                    <div>
                                        <span class="text-xs text-gray-400 block">{{ __('Guardian Name') }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $parentGuardian->name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">{{ __('Guardian Phone') }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $parentGuardian->phone_number }}</span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-xs text-gray-400 block">{{ __('Guardian Address') }}</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $parentGuardian->address ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-950/20 dark:text-yellow-200">
                                {{ __('No parent/guardian information recorded for this student.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <x-slot name="footer">
                <button type="button" wire:click="closeStudentModal" class="rounded-lg border border-gray-350 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                    {{ __('Close') }}
                </button>
            </x-slot>
        </x-college.modal>
    @endif

    <!-- Performance Detail Modal -->
    @if ($selectedStudentId !== null && $modalType === 'performance' && $selectedStudent !== null)
        <x-college.modal name="student-performance-modal" show="true" livewireSynced="true" title="{{ __('Student Performance Metrics') }}" maxWidth="3xl">
            <div class="space-y-6">
                <!-- Header with GPA Info -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-750">
                    <div class="flex items-center space-x-3">
                        <x-college.avatar :name="$selectedStudent->firstname . ' ' . $selectedStudent->lastname" size="md" />
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $selectedStudent->lastname }}, {{ $selectedStudent->firstname }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Index: :index', ['index' => $selectedStudent->index_number]) }}</p>
                        </div>
                    </div>
                    <!-- GPA Badge widget -->
                    <div class="flex items-center space-x-3 rounded-xl bg-indigo-50/50 p-3 border border-indigo-100 dark:bg-indigo-950/20 dark:border-indigo-900/40">
                        <div class="flex items-center justify-center rounded-lg bg-indigo-600 p-2 text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-indigo-600 dark:text-indigo-400 block tracking-wider">{{ __('Cumulative GPA') }}</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ $cgpa !== null ? number_format($cgpa, 2) : '—' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Academic Transcripts Table -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('Grades & Results History') }}</h4>
                    @if($studentResults->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-gray-400 dark:border-gray-700">
                            <i class="fa-solid fa-folder-open text-2xl mb-2"></i>
                            <p class="text-sm">{{ __('No result entries recorded yet for this student.') }}</p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-750">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Course Code') }}</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Course Name') }}</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">{{ __('Session') }}</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">{{ __('Score') }}</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">{{ __('Grade') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-150 bg-white dark:divide-gray-750 dark:bg-gray-800">
                                    @foreach($studentResults as $res)
                                        @php
                                            $gradeColor = match(strtoupper($res->grade ?? '')) {
                                                'A' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30',
                                                'B+', 'B' => 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/20 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/30',
                                                'C' => 'text-blue-600 bg-blue-50 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30',
                                                'D' => 'text-amber-600 bg-amber-50 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30',
                                                'F' => 'text-rose-600 bg-rose-50 dark:bg-rose-950/20 dark:text-rose-450 border border-rose-100 dark:border-rose-900/30',
                                                default => 'text-gray-600 bg-gray-50 dark:bg-gray-750 dark:text-gray-300'
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750/30">
                                            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">{{ $res->course?->code ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $res->course?->name ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $res->academicSession?->name ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format($res->score, 1) }}%</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $gradeColor }}">
                                                    {{ $res->grade ?? '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            <x-slot name="footer">
                <button type="button" wire:click="closeStudentModal" class="rounded-lg border border-gray-350 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                    {{ __('Close') }}
                </button>
            </x-slot>
        </x-college.modal>
    @endif
</div>
