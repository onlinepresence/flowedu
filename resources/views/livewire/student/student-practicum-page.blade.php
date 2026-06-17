<div class="mx-auto max-w-7xl space-y-6">

    @if(!$supervision)
        <!-- Empty State -->
        <div class="bg-white p-12 rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-center">
            <x-college.empty-state
                title="{{ __('No supervision assigned') }}"
                description="{{ __('You have not been assigned to a supervisor for teaching practice in this academic session. Please contact the administrator.') }}"
            >
                <x-slot:icon>
                    <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" /></svg>
                </x-slot:icon>
            </x-college.empty-state>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Placement & Supervisor Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-4 lg:col-span-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Placement Details') }}</h3>
                <hr class="border-gray-200 dark:border-gray-700">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Academic Year') }}</label>
                    <div class="mt-1 font-medium text-gray-900 dark:text-white">{{ $supervision->academicSession->name }}</div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Partnership School') }}</label>
                    <div class="mt-1 font-semibold text-indigo-600 dark:text-indigo-400">{{ $supervision->partnership_school }}</div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Assigned Supervisor') }}</label>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $supervision->teacher->user->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $supervision->teacher->user->email }}</div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Status') }}</label>
                    <div class="mt-1">
                        @if($supervision->status === 'evaluated')
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400">
                                {{ __('Evaluated') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400">
                                {{ __('Assigned') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Evaluation Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-4 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Teaching Practice Evaluation') }}</h3>
                <hr class="border-gray-200 dark:border-gray-700">

                @if($supervision->status === 'evaluated')
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-purple-50 p-4 rounded-xl dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/50">
                        <div>
                            <span class="text-sm font-semibold text-purple-700 dark:text-purple-400">{{ __('Your Overall Rubric Score') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Submitted by') }} {{ $supervision->teacher->user->name }} &bull; {{ $supervision->evaluated_at?->format('M d, Y') }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-1.5">
                            <span class="text-4xl font-extrabold tracking-tight text-purple-900 dark:text-purple-300">{{ number_format((float)$supervision->score, 2) }}</span>
                            <span class="text-xl font-bold text-purple-600 dark:text-purple-500">%</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Supervisor Remarks & Feedback') }}</label>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-150 dark:border-gray-800 text-sm leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                            {{ $supervision->evaluation_notes }}
                        </div>
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <h4 class="mt-4 font-bold text-gray-900 dark:text-white">{{ __('Evaluation Pending') }}</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            {{ __('Your supervisor has not recorded your score card yet. Please check back later after classroom assessments are complete.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
