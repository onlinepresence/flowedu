<div class="mx-auto max-w-5xl space-y-8 print:p-0 print:text-black print:bg-white">
    <!-- PAGE HEADER (SCREEN ONLY) -->
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 print:hidden">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('My Attendance') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Monitor your semester attendance, track track-rates, and maintain eligibility status for examinations.') }}</p>
        </div>
        <button 
            type="button"
            onclick="window.print()"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-650"
        >
            <i class="fa-solid fa-print"></i>
            {{ __('Print Statement') }}
        </button>
    </div>

    <!-- STATS GRID (SCREEN ONLY) -->
    @if ($rows->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 print:hidden">
            <!-- Card 1: Attendance Rate -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-3xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Overall Attendance Rate') }}</span>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                            {{ number_format($cumulativeRate, 1) }}%
                        </p>
                    </div>
                    <!-- Radial Progress Indicator -->
                    <div class="relative flex items-center justify-center">
                        <svg class="h-14 w-14 transform -rotate-90" viewBox="0 0 36 36">
                            <!-- Background Circle -->
                            <path class="text-gray-100 dark:text-gray-700" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <!-- Progress Circle -->
                            <path class="{{ $standing === 'Good' ? 'text-emerald-500' : ($standing === 'Warning' ? 'text-amber-500' : 'text-rose-500') }}" stroke-width="3" stroke-dasharray="{{ $cumulativeRate }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="absolute text-3xs font-bold font-mono text-gray-600 dark:text-gray-300">
                            {{ round($cumulativeRate) }}%
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-3xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-calculator text-gray-400"></i>
                    <span>{{ __('Weighted across all semesters') }}</span>
                </div>
            </div>

            <!-- Card 2: Total Days Present -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-3xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Total Days Present') }}</span>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                            {{ $totalDaysAttended }} <span class="text-xs text-gray-400 font-normal">/ {{ $totalMaxDays }}</span>
                        </p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-3xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-clock-history text-gray-400"></i>
                    <span>{{ __('Cumulative lecture participation') }}</span>
                </div>
            </div>

            <!-- Card 3: Academic Standing Status -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-3xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Exam Eligibility Standing') }}</span>
                        <div>
                            @if ($standing === 'Good')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ __('Good Standing') }}
                                </span>
                            @elseif ($standing === 'Warning')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/20 dark:text-amber-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    {{ __('Low Attendance') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-800 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    {{ __('Ineligible Alert') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $standing === 'Good' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400' : ($standing === 'Warning' ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400') }}">
                        <i class="fa-solid {{ $standing === 'Good' ? 'fa-circle-check' : ($standing === 'Warning' ? 'fa-circle-exclamation' : 'fa-circle-xmark') }} text-lg"></i>
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-3xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-shield-halved text-gray-400"></i>
                    <span>
                        @if ($standing === 'Good')
                            {{ __('Cleared to write final examinations') }}
                        @elseif ($standing === 'Warning')
                            {{ __('Eligible, but close to minimum :min% limit', ['min' => $minThreshold]) }}
                        @else
                            {{ __('Ineligible for examinations (under :min%)', ['min' => $minThreshold]) }}
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- MAIN TIMELINE SECTION (SCREEN ONLY) -->
    <div class="space-y-4 print:hidden">
        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-250 uppercase tracking-wider">{{ __('Semester Breakdown') }}</h3>
        
        <div class="relative border-l border-gray-200 dark:border-gray-700 ml-4 pl-6 space-y-8">
            @forelse ($rows as $row)
                @php
                    $rate = min(100.0, ($row->attendance_record / 120) * 100);
                    $colorClass = $rate >= ($minThreshold + 10) ? 'bg-emerald-500' : ($rate >= $minThreshold ? 'bg-amber-500' : 'bg-rose-500');
                    $dotColor = $rate >= ($minThreshold + 10) ? 'bg-emerald-500 ring-emerald-100 dark:ring-emerald-900/40' : ($rate >= $minThreshold ? 'bg-amber-500 ring-amber-100 dark:ring-amber-900/40' : 'bg-rose-500 ring-rose-100 dark:ring-rose-900/40');
                @endphp
                <div class="relative" wire:key="sem-{{ $row->id }}">
                    <!-- Timeline Dot -->
                    <span class="absolute -left-9 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-white dark:bg-gray-800 ring-4 {{ $dotColor }}">
                        <i class="fa-solid {{ $rate >= $minThreshold ? 'fa-check text-[9px] text-white' : 'fa-exclamation text-[9px] text-white' }}"></i>
                    </span>
                    
                    <!-- Content Box -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ __('Level') }} {{ $row->class_level }}
                                </span>
                                <h4 class="mt-1.5 text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $row->academic_session }} {{ __('Academic Session') }}
                                </h4>
                                <p class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $row->program?->name ?? __('Not Assigned') }}
                                </p>
                            </div>

                            <div class="text-left sm:text-right shrink-0">
                                <span class="text-lg font-extrabold text-gray-900 dark:text-white font-mono">
                                    {{ number_format($rate, 1) }}%
                                </span>
                                <p class="text-3xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    {{ $row->attendance_record }} {{ __('Days Present') }} / 120
                                </p>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full {{ $colorClass }} transition-all duration-500" style="width: {{ $rate }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="-ml-10 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center space-y-2">
                        <span class="text-2xl"><i class="fa-solid fa-calendar-times text-gray-400"></i></span>
                        <span>{{ __('No academic attendance records are available at the moment.') }}</span>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @if ($showPolicy)
    <!-- ATTENDANCE POLICY STATEMENT (SCREEN ONLY) -->
    <div class="rounded-xl border border-indigo-100 bg-indigo-50/20 p-5 dark:border-indigo-900/30 dark:bg-indigo-950/10 print:hidden">
        <div class="flex items-start gap-4">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </span>
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">{{ __('Attendance Policy & Exam Eligibility') }}</h4>
                <p class="text-xs text-indigo-950/80 dark:text-indigo-300/85 leading-relaxed">
                    {{ __('In accordance with university statutes and academic regulations, all students must maintain a minimum of :min% attendance in lectures, tutorials, and practical laboratory sessions per semester to remain eligible to write the end-of-semester final examinations.', ['min' => $minThreshold]) }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- PRINT-ONLY ATTENDANCE SHEET LAYOUT (HIDDEN ON SCREEN, VISIBLE ON PRINT) -->
    <div class="hidden print:block w-full text-black bg-white">
        <!-- University Branding Header -->
        <div class="text-center border-b border-black pb-4 mb-6">
            <h1 class="text-xl font-bold uppercase tracking-wide">{{ __('METROPOLITAN UNIVERSITY COLLEGE') }}</h1>
            <p class="text-xs italic uppercase tracking-wider mt-0.5">{{ __('Commitment to Academic Excellence & Discipline') }}</p>
            <p class="text-2xs font-mono text-gray-500 mt-1">{{ __('Registrar\'s Office · Academic Records Division') }}</p>
        </div>

        <div class="text-center mb-6">
            <h2 class="text-md font-bold uppercase underline">{{ __('OFFICIAL STUDENT ATTENDANCE STATEMENT') }}</h2>
        </div>

        <!-- Student Identification Info -->
        <div class="grid grid-cols-2 gap-4 border border-black p-4 mb-6 text-xs">
            <div>
                <p class="mb-1"><span class="font-bold">{{ __('Student Name') }}:</span> {{ $student ? ($student->lastname . ', ' . $student->othernames) : '—' }}</p>
                <p class="mb-1"><span class="font-bold">{{ __('Index / ID Number') }}:</span> {{ $student->index_number ?? '—' }}</p>
                <p class="mb-1"><span class="font-bold">{{ __('Department') }}:</span> {{ $student->department?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="mb-1"><span class="font-bold">{{ __('Program') }}:</span> {{ $student->program?->name ?? '—' }}</p>
                <p class="mb-1"><span class="font-bold">{{ __('Academic Standing') }}:</span> 
                    @if ($standing === 'Good')
                        <span class="font-bold text-green-700">{{ __('GOOD STANDING (Cleared for Exams)') }}</span>
                    @elseif ($standing === 'Warning')
                        <span class="font-bold text-amber-700">{{ __('LOW ATTENDANCE WARNING') }}</span>
                    @else
                        <span class="font-bold text-red-700">{{ __('INELIGIBILITY ALERT (Exams Blocked)') }}</span>
                    @endif
                </p>
                <p class="mb-1"><span class="font-bold">{{ __('Date Generated') }}:</span> {{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>

        <!-- Attendance Logs Table -->
        <div class="mb-8">
            <table class="w-full border-collapse border border-black text-left text-xs">
                <thead>
                    <tr class="bg-gray-100 border-b border-black">
                        <th class="border border-black p-2 font-bold">{{ __('Academic Session') }}</th>
                        <th class="border border-black p-2 font-bold">{{ __('Class Level') }}</th>
                        <th class="border border-black p-2 font-bold text-center">{{ __('Days Present') }}</th>
                        <th class="border border-black p-2 font-bold text-right">{{ __('Attendance Rate') }}</th>
                        <th class="border border-black p-2 font-bold text-center">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $rate = min(100.0, ($row->attendance_record / 120) * 100);
                        @endphp
                        <tr class="border-b border-black">
                            <td class="border border-black p-2 font-semibold">{{ $row->academic_session }}</td>
                            <td class="border border-black p-2">Level {{ $row->class_level }}</td>
                            <td class="border border-black p-2 text-center font-mono">{{ $row->attendance_record }} / 120</td>
                            <td class="border border-black p-2 text-right font-mono font-bold">{{ number_format($rate, 1) }}%</td>
                            <td class="border border-black p-2 text-center font-semibold">
                                @if ($rate >= ($minThreshold + 15))
                                    {{ __('Excellent') }}
                                @elseif ($rate >= $minThreshold)
                                    {{ __('Satisfactory') }}
                                @else
                                    {{ __('Ineligible') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-black p-4 text-center text-gray-500">
                                {{ __('No academic records or attendance records found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Statistics Section -->
        <div class="grid grid-cols-2 gap-4 border border-black p-4 mb-12 text-xs">
            <div>
                <p><span class="font-bold">{{ __('Cumulative Days Present') }}:</span> {{ $totalDaysAttended }} / {{ $totalMaxDays }} Days</p>
                <p><span class="font-bold">{{ __('Average Attendance Rate') }}:</span> {{ number_format($cumulativeRate, 1) }}%</p>
            </div>
            <div>
                <p class="italic text-gray-600">
                    {{ __('Note: Students are required to maintain a minimum of :min% class attendance rate per semester to sit for examinations. Attendance under :min% defaults to academic probation or exam ineligibility.', ['min' => $minThreshold]) }}
                </p>
            </div>
        </div>

        <!-- Validation and Signatures -->
        <div class="grid grid-cols-2 gap-8 text-xs pt-10">
            <div class="text-center">
                <div class="h-16 border-b border-black w-48 mx-auto mb-2"></div>
                <p class="font-bold">{{ __('Generated By') }}</p>
                <p class="text-2xs text-gray-600">{{ __('Student Registry Self-Service') }}</p>
            </div>
            <div class="text-center">
                <div class="h-16 border-b border-black w-48 mx-auto mb-2"></div>
                <p class="font-bold">{{ __('Registrar Signature & Official Stamp') }}</p>
                <p class="text-2xs text-gray-600">{{ __('Academic Records Division') }}</p>
            </div>
        </div>
    </div>
</div>
