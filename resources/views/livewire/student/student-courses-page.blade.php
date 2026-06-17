<div class="space-y-6">
    @if ($courses->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($courses as $course)
                <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 hover:shadow-md transition duration-200 border-t-4 border-t-purple-600 dark:border-t-purple-500" wire:key="co-{{ $course->id }}">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-950/40 px-2.5 py-0.5 text-xs font-mono font-bold text-purple-700 dark:text-purple-300">
                                {{ $course->code }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                {{ __('Lvl :lvl', ['lvl' => $course->year_level * 100]) }}
                            </span>
                        </div>

                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white leading-snug line-clamp-2" title="{{ $course->name }}">
                                {{ $course->name }}
                            </h3>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-300 shrink-0">
                            <i class="fa-solid fa-user-tie text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-semibold tracking-wider">{{ __('Lecturer') }}</p>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                                @if ($course->teacher)
                                    {{ $course->teacher->othernames }} {{ $course->teacher->lastname }}
                                @else
                                    <span class="italic text-gray-400">{{ __('Not Assigned') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-college.empty-state
            :title="__('No Registered Courses')"
            :description="__('You do not have any registered courses for this semester.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-slot:icon>
        </x-college.empty-state>
    @endif
</div>
