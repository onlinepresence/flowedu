<div class="space-y-6">
    @if ($redirectEnabled)
        <!-- External Redirect Landing Card -->
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800 text-center max-w-2xl mx-auto space-y-6">
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
                <i class="fa-solid fa-square-poll-vertical text-3xl"></i>
            </div>
            <div class="space-y-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('External Grading Software Redirect') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                    {{ __('Your institution uses an external grading software to manage, publish, and review academic results. Please click below to access your grades directly on the external portal.') }}
                </p>
            </div>
            <div>
                <a 
                    href="{{ $externalGradingUrl ?: '#' }}" 
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 text-sm font-semibold transition shadow-sm focus:outline-none"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    {{ __('Access External Grading Portal') }}
                </a>
            </div>
        </div>
    @else
        <!-- Filter pills at the top -->
        @if (count($availableLevels) > 0)
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <button
                    wire:click="$set('selectedLevel', 'all')"
                    type="button"
                    class="px-4 py-2 rounded-full text-xs font-semibold transition-all {{ $selectedLevel === 'all' ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    {{ __('All Levels') }}
                </button>
                @foreach ($availableLevels as $lvl)
                    <button
                        wire:click="$set('selectedLevel', '{{ $lvl }}')"
                        type="button"
                        class="px-4 py-2 rounded-full text-xs font-semibold transition-all {{ $selectedLevel == $lvl ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        {{ __('Level :lvl', ['lvl' => $lvl]) }}
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Semester Iteration -->
        @forelse ($filteredData as $semKey => $semData)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden" wire:key="sem-table-{{ md5($semKey) }}">
                <div class="bg-gray-50/50 dark:bg-gray-900/30 px-6 py-4 border-b border-gray-150 dark:border-gray-700/50">
                    <h3 class="text-sm font-bold text-purple-700 dark:text-purple-400">
                        {{ __('Semester :sem', ['sem' => $semData['semester']]) }} 
                        @if ($semData['academic_year'])
                            [{{ $semData['academic_year'] }}]
                        @endif
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-750 text-xs">
                        <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Course Code') }}</th>
                                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Course Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">{{ __('Credit Hours') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">{{ __('Points') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">{{ __('Grade') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-gray-750">
                            @foreach ($semData['results'] as $res)
                                <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-850/20">
                                    <td class="px-6 py-3.5 font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $res['code'] }}</td>
                                    <td class="px-6 py-3.5 text-gray-700 dark:text-gray-300">{{ $res['name'] }}</td>
                                    <td class="px-6 py-3.5 text-center text-gray-600 dark:text-gray-400 font-mono">{{ $res['credit_hours'] }}</td>
                                    <td class="px-6 py-3.5 text-center text-gray-650 dark:text-gray-400 font-mono">{{ number_format($res['points'], 2) }}</td>
                                    <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-950/40 px-2.5 py-0.5 text-xs font-bold text-purple-700 dark:text-purple-300">
                                            {{ $res['grade'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer details for the table -->
                <div class="bg-gray-50/40 dark:bg-gray-900/20 p-4 border-t border-gray-150 dark:border-gray-700/50">
                    <div class="flex justify-between items-center text-xs font-semibold text-gray-650 dark:text-gray-400">
                        <div>
                            {{ __('Total Credit Hours:') }} 
                            <span class="text-gray-900 dark:text-white font-bold ml-1">{{ $semData['semester_count'] }}</span>
                        </div>
                        <div>
                            {{ __('Total Points Gotten:') }} 
                            <span class="text-gray-900 dark:text-white font-bold ml-1">{{ number_format($semData['semester_points'], 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-between items-center text-sm font-bold border-t border-dashed border-gray-200 dark:border-gray-700/60 pt-3">
                        <div class="text-purple-600 dark:text-purple-400">
                            {{ __('Cumulative CGPA:') }} 
                            <span class="text-gray-900 dark:text-white text-base ml-1.5 font-extrabold">{{ $semData['cgpa'] }}</span>
                        </div>
                        <div class="text-indigo-600 dark:text-indigo-400">
                            {{ __('GPA:') }} 
                            <span class="text-gray-900 dark:text-white text-base ml-1.5 font-extrabold">{{ $semData['gpa'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <x-college.empty-state
                :title="__('No published results')"
                :description="__('Your academic grades have not been published for any semesters yet.')"
            >
                <x-slot:icon>
                    <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </x-slot:icon>
            </x-college.empty-state>
        @endforelse
    @endif
</div>
