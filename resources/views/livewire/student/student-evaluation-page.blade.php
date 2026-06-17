<div class="space-y-6">
    <!-- Tab Pill Selectors -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-150 dark:border-gray-700/60 pb-4 mb-6">
        <a 
            href="{{ route('student.evaluation', ['tab' => 'ongoing']) }}" 
            wire:navigate
            class="px-4 py-2 rounded-full text-xs font-bold transition-all {{ $tab === 'ongoing' ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-650 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
        >
            <i class="fa-solid fa-hourglass-half mr-1.5 {{ $tab === 'ongoing' ? 'animate-pulse' : '' }}"></i>
            {{ __('Ongoing Evaluations') }}
        </a>
        <a 
            href="{{ route('student.evaluation', ['tab' => 'completed']) }}" 
            wire:navigate
            class="px-4 py-2 rounded-full text-xs font-bold transition-all {{ $tab === 'completed' ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-650 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
        >
            <i class="fa-solid fa-circle-check mr-1.5"></i>
            {{ __('Completed Evaluations') }}
        </a>
    </div>

    <!-- Evaluations Grid/List -->
    @if ($forms->isEmpty())
        <x-college.empty-state
            :title="$tab === 'completed' ? __('No completed evaluations') : __('No ongoing evaluations')"
            :description="$tab === 'completed' ? __('You have not completed any evaluations for this semester yet.') : __('You have no active or pending evaluations to fill out.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($forms as $form)
                <div 
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 hover:shadow-md transition duration-200 flex flex-col justify-between" 
                    wire:key="form-card-{{ $form->id }}"
                >
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                <i class="fa-solid fa-clipboard-question text-base"></i>
                            </span>
                            <span class="text-3xs font-mono font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                #{{ $form->unique_code }}
                            </span>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-2 leading-snug" title="{{ $form->title }}">
                            {{ $form->title }}
                        </h3>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-1.5 text-2xs font-semibold text-gray-500 dark:text-gray-400">
                            <i class="fa-regular fa-clock text-gray-400"></i>
                            <span>
                                @if ($tab === 'ongoing')
                                    {{ __('Due:') }} {{ $form->end_time?->timezone(config('app.timezone'))->format('M d, Y H:i') }}
                                @else
                                    {{ __('Ended:') }} {{ $form->end_time?->timezone(config('app.timezone'))->format('M d, Y') }}
                                @endif
                            </span>
                        </div>

                        @if ($tab === 'ongoing')
                            <a
                                href="{{ route('student.evaluation.perform', ['code' => $form->unique_code]) }}"
                                wire:navigate
                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-2xs font-bold transition shadow-sm focus:outline-none"
                            >
                                {{ __('Start') }}
                                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-2xs font-bold text-emerald-700 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                {{ __('Completed') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
