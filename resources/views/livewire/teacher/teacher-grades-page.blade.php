<div class="mx-auto max-w-7xl space-y-6">

    <!-- Premium Filters Card -->
    <x-college.filter-card cols="5" class="mb-6">
        <!-- Academic Year -->
        <div>
            <label for="session-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Year') }}</label>
            <select wire:model.live="selectedSessionId" id="session-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                <option value="">{{ __('All Years') }}</option>
                @foreach ($sessions as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Class (Course) -->
        <div>
            <label for="course-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Class (Course)') }}</label>
            <select wire:model.live="selectedCourseId" id="course-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                <option value="">{{ __('All Courses') }}</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Level -->
        <div>
            <label for="level-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level') }}</label>
            <select wire:model.live="selectedLevel" id="level-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                <option value="">{{ __('All Levels') }}</option>
                @foreach ($levels as $l)
                    <option value="{{ $l }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div>
            <label for="grade-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search Student') }}</label>
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" id="grade-search" type="search" placeholder="{{ __('Name or Index Number…') }}" class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500" />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637Z" /></svg>
                </div>
            </div>
        </div>

        <!-- Reset Button -->
        <div class="flex items-end">
            <button type="button" wire:click="resetFilters" class="w-full rounded-lg border border-gray-350 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                {{ __('Reset') }}
            </button>
        </div>
    </x-college.filter-card>

    <!-- Content Table Section -->
    @if ($rows->isEmpty())
        <x-college.empty-state
            :title="__('No grade entries found')"
            :description="__('No grade rows match the filter criteria or there are no student grade lists recorded for your classes.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A5.905 5.905 0 0 1 8 3.447M4.26 10.147a49.647 49.647 0 0 1 15.482 0m0 0a50.58 50.58 0 0 1 2.658-.813A5.906 5.906 0 0 0 16 3.447m0 0A5.89 5.89 0 0 1 13.5 1.5h-3A5.89 5.89 0 0 1 8 3.447M16 3.447a5.89 5.89 0 0 1-8 0M8 10.5V19" /></svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-750 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-850">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Class Cohort') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assessment Breakdown') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total / Grade') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-750 dark:bg-gray-800">
                        @foreach ($rows as $row)
                            <tr wire:key="grade-row-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-855 transition-colors">
                                <!-- Student Info -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        @if($row->student)
                                            <x-college.avatar :name="$row->student->firstname . ' ' . $row->student->lastname" size="sm" />
                                            <div class="ml-3">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $row->student->lastname }}, {{ $row->student->firstname }}
                                                </div>
                                                <div class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                                    {{ $row->student->index_number }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Course Details -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($row->resultSlip?->course)
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $row->resultSlip->course->code }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ $row->resultSlip->course->name }}
                                        </div>
                                        <div class="mt-1 flex items-center space-x-1">
                                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                L{{ $row->resultSlip->level }}
                                            </span>
                                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                Sem {{ $row->resultSlip->semester }}
                                            </span>
                                            @if($row->resultSlip->academicSession)
                                                <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ $row->resultSlip->academicSession->name }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>

                                <!-- Assessment Breakdowns -->
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <div class="inline-flex flex-col space-y-1 text-xs">
                                        <div class="flex items-center justify-between space-x-4">
                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Class Score') }}:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ number_format((float)$row->class_score, 1) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between space-x-4">
                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Exam Score') }}:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ number_format((float)$row->exam_score, 1) }}</span>
                                        </div>
                                        @if($row->attendance_score !== null || $row->midsem_score !== null || $row->project_score !== null)
                                            <div class="border-t border-gray-150 pt-1 text-[10px] text-gray-400 dark:border-gray-700 dark:text-gray-500">
                                                Att: {{ number_format((float)$row->attendance_score, 1) }} |
                                                Mid: {{ number_format((float)$row->midsem_score, 1) }} |
                                                Proj: {{ number_format((float)$row->project_score, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Total Score & Grade Badge -->
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-1">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ number_format((float)$row->total_score, 1) }}%
                                        </span>
                                        @php
                                            $gradeColor = match($row->letter_grade) {
                                                'A' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40',
                                                'B+', 'B' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40',
                                                'C', 'D' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-100 dark:border-amber-900/40',
                                                default => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-450 border border-rose-100 dark:border-rose-900/40'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $gradeColor }}">
                                            {{ $row->letter_grade }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Submission Status -->
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    @if($row->resultSlip)
                                        @php
                                            $statusColor = match($row->resultSlip->status) {
                                                'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40',
                                                'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-450 border border-rose-100 dark:border-rose-900/40',
                                                'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-100 dark:border-amber-900/40',
                                                default => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $statusColor }}">
                                            {{ ucfirst($row->resultSlip->status) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Styled Pagination Controls -->
            @if ($rows->hasPages())
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-750">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
