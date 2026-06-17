<div class="space-y-6">
    <!-- Filter Row -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-750 dark:bg-gray-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <i class="fa-solid fa-filter text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Filter Performance Statistics') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Refine the charts and data tables below') }}</p>
                </div>
            </div>
            
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <!-- Academic Session Selector -->
                <div>
                    <label for="session-filter" class="sr-only">{{ __('Academic Session') }}</label>
                    <select id="session-filter" wire:model.live="selectedSessionId" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="all">{{ __('All Academic Sessions') }}</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Semester Selector -->
                <div>
                    <label for="semester-filter" class="sr-only">{{ __('Semester') }}</label>
                    <select id="semester-filter" wire:model.live="selectedSemester" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="all">{{ __('All Semesters') }}</option>
                        <option value="1">{{ __('Semester 1') }}</option>
                        <option value="2">{{ __('Semester 2') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Graded Results -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Graded Students') }}</p>
                    <h4 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($resultCount) }}</h4>
                </div>
                <div class="rounded-lg bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400">
                    <i class="fa-solid fa-user-graduate text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-circle-info mr-1"></i>
                <span>{{ __('Total grading entries submitted') }}</span>
            </div>
        </div>

        <!-- Class Average -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Class Average') }}</p>
                    <h4 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $avgScore !== null ? number_format($avgScore, 1) . '%' : '—' }}
                    </h4>
                </div>
                <!-- SVG Radial Indicator -->
                @if($avgScore !== null)
                    <div class="relative flex items-center justify-center">
                        <svg class="h-14 w-14 transform -rotate-90">
                            <circle cx="28" cy="28" r="24" class="text-gray-100 dark:text-gray-700" stroke-width="4" stroke="currentColor" fill="transparent" />
                            <circle cx="28" cy="28" r="24" class="text-indigo-600 dark:text-indigo-400" stroke-width="4" stroke-linecap="round" stroke="currentColor" fill="transparent"
                                    stroke-dasharray="150.79"
                                    stroke-dashoffset="{{ 150.79 - ($avgScore / 100) * 150.79 }}" />
                        </svg>
                        <span class="absolute text-[10px] font-bold text-gray-700 dark:text-gray-300">{{ round($avgScore) }}%</span>
                    </div>
                @else
                    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                        <i class="fa-solid fa-chart-line text-xl"></i>
                    </div>
                @endif
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-circle-info mr-1"></i>
                <span>{{ __('Overall mean score across classes') }}</span>
            </div>
        </div>

        <!-- Top Course -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="overflow-hidden pr-2">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ __('Top Course') }}</p>
                    <h4 class="mt-2 text-xl font-bold text-gray-900 dark:text-white truncate">
                        {{ $topCourse ? $topCourse->code : '—' }}
                    </h4>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                        {{ $topCourseAvg !== null ? __('Avg: ') . number_format($topCourseAvg, 1) . '%' : '' }}
                    </span>
                </div>
                <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 shrink-0">
                    <i class="fa-solid fa-award text-xl"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <span class="truncate">{{ $topCourse ? $topCourse->name : __('No data available') }}</span>
            </div>
        </div>

        <!-- Pass Rate -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Pass Rate') }}</p>
                    <h4 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($passRate, 1) }}%</h4>
                </div>
                <div class="rounded-lg bg-purple-50 p-3 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400">
                    <i class="fa-solid fa-percent text-xl"></i>
                </div>
            </div>
            <!-- Horizontal Progress Bar -->
            <div class="mt-4">
                <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-2 rounded-full bg-purple-600 dark:bg-purple-400 transition-all duration-500" style="width: {{ $passRate }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Charts & Slip Status -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Grade Distribution Chart -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800 lg:col-span-2">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Grade Distribution') }}</h3>
            
            @if($resultCount > 0)
                <div class="space-y-4">
                    @foreach($gradeDistribution as $gradeName => $info)
                        @php
                            $gradeColor = match($gradeName) {
                                'A' => 'bg-emerald-600 dark:bg-emerald-500',
                                'B+' => 'bg-indigo-600 dark:bg-indigo-500',
                                'B' => 'bg-indigo-500/90 dark:bg-indigo-400',
                                'C' => 'bg-blue-500 dark:bg-blue-450',
                                'D' => 'bg-amber-500 dark:bg-amber-400',
                                'F' => 'bg-rose-500 dark:bg-rose-450',
                                default => 'bg-indigo-600 dark:bg-indigo-500'
                            };
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Grade {{ $gradeName }}</span>
                                <span>{{ $info['count'] }} {{ __('students') }} ({{ number_format($info['percentage'], 1) }}%)</span>
                            </div>
                            <div class="relative h-4 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full {{ $gradeColor }} transition-all duration-500" style="width: {{ $info['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="rounded-full bg-gray-50 p-3 dark:bg-gray-900/50">
                        <i class="fa-solid fa-chart-bar text-3xl text-gray-400 dark:text-gray-600"></i>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('No grades recorded for this filter configuration.') }}</p>
                </div>
            @endif
        </div>

        <!-- Slip Submission Stats -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-750 dark:bg-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Grade Verification Status') }}</h3>
            
            <div class="space-y-3">
                <!-- Approved Slips -->
                <div class="flex items-center justify-between rounded-lg border border-emerald-100 bg-emerald-50/30 p-3 dark:border-emerald-950/30 dark:bg-emerald-950/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Approved Slips') }}</span>
                    </div>
                    <span class="text-base font-bold text-emerald-700 dark:text-emerald-400">{{ $slipCounts['approved'] }}</span>
                </div>

                <!-- Pending Verification -->
                <div class="flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50/30 p-3 dark:border-amber-950/30 dark:bg-amber-950/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Pending HOD Review') }}</span>
                    </div>
                    <span class="text-base font-bold text-amber-700 dark:text-amber-400">{{ $slipCounts['pending'] }}</span>
                </div>

                <!-- Draft Slips -->
                <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/30 p-3 dark:border-gray-700/30 dark:bg-gray-800/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-150 text-gray-750 dark:bg-gray-700 dark:text-gray-300">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Draft Saved Slips') }}</span>
                    </div>
                    <span class="text-base font-bold text-gray-700 dark:text-gray-300">{{ $slipCounts['draft'] }}</span>
                </div>

                <!-- Rejected Slips -->
                <div class="flex items-center justify-between rounded-lg border border-rose-100 bg-rose-50/30 p-3 dark:border-rose-950/30 dark:bg-rose-950/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Slips Returned') }}</span>
                    </div>
                    <span class="text-base font-bold text-rose-700 dark:text-rose-400">{{ $slipCounts['rejected'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Performance Table -->
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-750 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-750">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Assigned Courses Breakdown') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Course-by-course analytics for the filtered term') }}</p>
        </div>

        @if(count($courseAnalytics) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-750 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-850">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Course') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Year') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Class Size') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Average Score') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Highest Score') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Lowest Score') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Pass Rate') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-750 dark:bg-gray-800">
                        @foreach($courseAnalytics as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-850 transition-colors">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="rounded-lg bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400">
                                            <i class="fa-solid fa-book-open"></i>
                                        </div>
                                        <div class="ml-4">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['course']->code }}</span>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['course']->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                                        {{ $item['session']->name }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $item['total_students'] }} {{ __('students') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($item['average_score'], 1) }}%</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($item['max_score'], 1) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span class="text-sm font-semibold text-rose-600 dark:text-rose-450">{{ number_format($item['min_score'], 1) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($item['pass_rate'], 1) }}%</span>
                                        <div class="mt-1 h-1.5 w-16 rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-1.5 rounded-full {{ $item['pass_rate'] >= 75 ? 'bg-emerald-500' : ($item['pass_rate'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $item['pass_rate'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end">
                                        @php
                                            $enterParams = [
                                                'programId' => $item['course']->program_id,
                                                'courseId' => $item['course']->id,
                                                'level' => is_numeric($item['course']->year_level) ? (int)$item['course']->year_level * 100 : null,
                                                'academicSessionId' => $item['session']->id,
                                                'semester' => $item['course']->course_semester
                                            ];
                                        @endphp
                                        <a href="{{ route('teacher.results.enter', $enterParams) }}" class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-400 dark:hover:bg-indigo-950/60 transition-colors">
                                            <i class="fa-solid fa-eye mr-1"></i>
                                            {{ __('View Results') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-900/50">
                    <i class="fa-solid fa-inbox text-4xl text-gray-400 dark:text-gray-600"></i>
                </div>
                <h4 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">{{ __('No courses found') }}</h4>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('No grades or results have been logged for any courses in this filter range.') }}</p>
            </div>
        @endif
    </div>
</div>
