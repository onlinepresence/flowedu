<div class="space-y-6">
    <!-- Timetable Mode Toggle Switcher Header -->
    <div class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="space-y-0.5">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-calendar-week text-purple-600"></i>
                {{ $showTodayOnly ? __('Today\'s Schedule') : __('Weekly Schedule') }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $showTodayOnly ? __('Displaying classes scheduled for today.') : __('Displaying your full weekly class rotation.') }}
            </p>
        </div>
        <button
            wire:click="$toggle('showTodayOnly')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 px-4 py-2 text-xs font-bold transition shadow-sm focus:outline-none"
        >
            @if ($showTodayOnly)
                <i class="fa-solid fa-calendar text-purple-600 text-xs"></i>
                {{ __('Show Full Week') }}
            @else
                <i class="fa-solid fa-calendar-day text-purple-600 text-xs"></i>
                {{ __('Show Today Only') }}
            @endif
        </button>
    </div>

    @if ($slots->isNotEmpty())
        @php
            $groupedByDay = $slots->groupBy(fn($s) => ucfirst(strtolower($s->day)));
        @endphp

        <div class="space-y-8">
            @foreach ($groupedByDay as $day => $daySlots)
                <div class="space-y-4" wire:key="day-{{ $day }}">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-2 border-b border-gray-150 dark:border-gray-700/60 pb-2">
                        <i class="fa-solid fa-calendar-day text-purple-500 text-sm"></i>
                        {{ $day }}
                        <span class="ml-2 text-3xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">({{ trans_choice(':count class|:count classes', $daySlots->count()) }})</span>
                    </h3>

                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($daySlots as $slot)
                            @php
                                $status = $this->getSlotStatus($slot);
                                $cardBorder = match($status) {
                                    'in-progress' => 'border-l-4 border-l-emerald-500 bg-emerald-50/5 dark:bg-emerald-950/5 ring-1 ring-emerald-500/10',
                                    'upcoming' => 'border-l-4 border-l-purple-600',
                                    'past' => 'opacity-55 border-l-4 border-l-gray-300 dark:border-l-gray-700',
                                    default => 'border-l-4 border-l-indigo-500',
                                };
                            @endphp
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 hover:shadow-md transition duration-200 flex flex-col justify-between {{ $cardBorder }}" wire:key="slot-{{ $slot->id }}">
                                <div>
                                    <!-- Card Header: Course Code & Lecturer / Status Badge -->
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/50 pb-2.5 mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-950/40 px-2 py-0.5 text-3xs font-mono font-bold text-purple-750 dark:text-purple-300">
                                                {{ $slot->course?->code }}
                                            </span>
                                            @if ($status === 'in-progress')
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 text-3xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider animate-pulse">
                                                    <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                                    {{ __('In Progress') }}
                                                </span>
                                            @elseif ($status === 'upcoming')
                                                <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 text-3xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">
                                                    {{ __('Upcoming') }}
                                                </span>
                                            @elseif ($status === 'past')
                                                <span class="inline-flex items-center rounded-full bg-gray-50 dark:bg-gray-900/50 px-2 py-0.5 text-3xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    {{ __('Ended') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <i class="fa-solid fa-user-tie text-[10px] text-gray-400 shrink-0"></i>
                                            <span class="text-2xs text-gray-500 dark:text-gray-400 truncate font-medium" title="@if($slot->teacher){{ $slot->teacher->othernames }} {{ $slot->teacher->lastname }}@endif">
                                                @if ($slot->teacher)
                                                    {{ $slot->teacher->othernames }} {{ $slot->teacher->lastname }}
                                                @else
                                                    <span class="italic text-gray-400 text-3xs">{{ __('No Lecturer') }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Card Body: Course Name -->
                                    <div class="min-h-[38px] flex items-center">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm leading-snug line-clamp-2" title="{{ $slot->course?->name }}">
                                            {{ $slot->course?->name }}
                                        </h4>
                                    </div>
                                </div>

                                <!-- Card Footer: Time & Location in inline flow, sized to content -->
                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 text-2xs text-purple-750 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/20 px-2.5 py-1 rounded-md font-semibold shrink-0">
                                        <i class="fa-regular fa-clock text-purple-500"></i>
                                        @if ($slot->start_time && $slot->end_time)
                                            {{ \Illuminate\Support\Str::substr((string) $slot->start_time, 0, 5) }} – {{ \Illuminate\Support\Str::substr((string) $slot->end_time, 0, 5) }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-2xs text-indigo-755 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/20 px-2.5 py-1 rounded-md font-semibold min-w-0" title="{{ $slot->venue }}">
                                        <i class="fa-solid fa-location-dot text-indigo-500 shrink-0"></i>
                                        <span class="truncate">{{ $slot->venue ?? __('TBA') }}</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-college.empty-state
            :title="$showTodayOnly ? __('No classes today') : __('No scheduled classes')"
            :description="$showTodayOnly ? __('You are all clear! There are no classes scheduled on your timetable for today.') : __('There are currently no classes scheduled for your program and level.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" />
                </svg>
            </x-slot:icon>
            @if ($showTodayOnly)
                <div class="mt-4 flex justify-center">
                    <button
                        wire:click="$set('showTodayOnly', false)"
                        type="button"
                        class="rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-bold transition shadow-sm focus:outline-none"
                    >
                        {{ __('Show Full Week') }}
                    </button>
                </div>
            @endif
        </x-college.empty-state>
    @endif
</div>
