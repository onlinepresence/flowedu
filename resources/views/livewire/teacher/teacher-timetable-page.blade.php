<div class="mx-auto max-w-7xl">

    @if (! $hasSlots)
        <x-college.empty-state
            :title="__('No timetable available')"
            :description="__('You don\'t have any scheduled classes yet. Timetable will be updated when course assignments are made.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" /></svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        @foreach ($slotsByDay as $day => $daySlots)
            <div class="mb-6 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-700 dark:text-gray-200">
                    <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" /></svg>
                    {{ $day }}
                </h3>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($daySlots as $slot)
                        @php
                            $levelLabel = $slot->course ? ((is_numeric($slot->course->year_level) ? ((int) $slot->course->year_level * 100) : $slot->course->year_level)) : '—';
                            $start = $slot->start_time ? \Illuminate\Support\Str::substr((string) $slot->start_time, 0, 5) : '—';
                            $end = $slot->end_time ? \Illuminate\Support\Str::substr((string) $slot->end_time, 0, 5) : '—';
                            $code = $slot->course?->code;
                        @endphp
                        <div class="rounded-lg border border-gray-200 p-4 transition-shadow hover:shadow-md dark:border-gray-700" wire:key="tt-{{ $slot->id }}">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-100">{{ $slot->course?->name ?? '—' }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $code ?? '—' }}</p>
                                </div>
                                <span class="shrink-0 rounded bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">{{ __('Level :level', ['level' => $levelLabel]) }}</span>
                            </div>
                            <div class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    <span>{{ $start }} – {{ $end }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                    <span>{{ $slot->venue ?? '—' }}</span>
                                </div>
                            </div>
                            @if ($code)
                                <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                    <a href="{{ route('teacher.students') }}?course={{ urlencode($code) }}" wire:navigate class="text-sm font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400">{{ __('View students') }}</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
