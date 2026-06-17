<div class="mx-auto max-w-7xl space-y-6">


    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-college.stats-card
            :title="__('Index number')"
            :value="$student->index_number ?: '—'"
            color="purple"
            icon="fa-solid fa-id-card"
        />
        <x-college.stats-card
            :title="__('Current level')"
            :value="$levelLabel"
            color="blue"
            icon="fa-solid fa-graduation-cap"
        />
        <x-college.stats-card
            :title="__('CGPA')"
            :value="$gpa['cgpa']"
            color="green"
            icon="fa-solid fa-chart-line"
        />
        <x-college.stats-card
            :title="__('Outstanding fees')"
            :value="number_format($outstanding, 2)"
            :color="$outstanding > 0 ? 'red' : 'green'"
            icon="fa-solid fa-receipt"
            :href="$canFinance ? route('student.fees.index') : null"
        />
    </div>

    <x-college.quick-links :title="__('Quick links')">
        <a href="{{ route('student.profile') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-user-circle text-purple-500 dark:text-purple-400"></i>
            {{ __('My profile') }}
        </a>
        @if ($canFinance)
            <a href="{{ route('student.fees.index') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-wallet text-purple-500 dark:text-purple-400"></i>
                {{ __('Fees') }}
            </a>
        @endif
        <a href="{{ route('student.courses') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-book text-purple-500 dark:text-purple-400"></i>
            {{ __('Courses') }}
        </a>
        <a href="{{ route('student.transcript') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-file-invoice text-purple-500 dark:text-purple-400"></i>
            {{ __('Transcript') }}
        </a>
        @if ($canClearance)
            <a href="{{ route('student.clearance') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-unlock-keyhole text-purple-500 dark:text-purple-400"></i>
                {{ __('Clearance') }}
            </a>
        @endif
        <a href="{{ route('student.results') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-square-poll-vertical text-purple-500 dark:text-purple-400"></i>
            {{ __('Results') }}
        </a>
        <a href="{{ route('student.timetable') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-calendar-days text-purple-500 dark:text-purple-400"></i>
            {{ __('Timetable') }}
        </a>
    </x-college.quick-links>

    <!-- Today's Schedule -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-calendar-day text-purple-500"></i>
            {{ __("Today's Schedule") }}
            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ now()->format('l, F j, Y') }})</span>
        </h3>

        @if ($todaySlots->isEmpty())
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <i class="fa-solid fa-bed text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __("No classes scheduled for today.") }}</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($todaySlots as $slot)
                    <div class="relative flex flex-col justify-between rounded-lg border border-gray-100 bg-gray-50 p-4 transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-900/50">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-500/10 dark:text-purple-400 dark:ring-purple-500/20">
                                    {{ $slot->course?->code }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fa-solid fa-location-dot text-gray-400"></i>
                                    {{ $slot->venue ?? '—' }}
                                </span>
                            </div>
                            <h4 class="mt-2 text-sm font-bold text-gray-900 dark:text-white line-clamp-1">
                                {{ $slot->course?->name ?? __('Slot') }}
                            </h4>
                            @if ($slot->teacher)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fa-solid fa-user-tie mr-1 text-gray-400"></i>
                                    {{ $slot->teacher->user?->name ?? $slot->teacher->lastname }}
                                </p>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 dark:border-gray-800">
                            <span class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400">
                                <i class="fa-regular fa-clock mr-1"></i>
                                {{ \Illuminate\Support\Str::substr((string) $slot->start_time, 0, 5) }} – {{ \Illuminate\Support\Str::substr((string) $slot->end_time, 0, 5) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($canClearance)
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-4">
                <i class="fa-solid fa-unlock mt-0.5 text-2xl text-purple-500" aria-hidden="true"></i>
                <div class="text-sm text-gray-800 dark:text-gray-200">
                    @if ($clearanceFinalYear === null)
                        <span>{{ __('Graduation clearance') }}: —</span>
                    @elseif ($clearanceEligible)
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ __('Eligible for graduation clearance') }}</span>
                    @else
                        <span class="font-medium text-gray-900 dark:text-white">{{ __('Graduation clearance') }}:</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Eligible in Level :level', ['level' => $clearanceFinalYear]) }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
