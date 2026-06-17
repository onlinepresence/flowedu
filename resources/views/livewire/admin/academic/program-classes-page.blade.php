<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <a href="{{ route('admin.academic.program') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            {{ __('All programs') }}
        </a>
    </x-slot>

    <!-- Year Levels Grid -->
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($levels as $level)
            <x-card class="relative flex flex-col justify-between p-6 hover:shadow-md transition-shadow">
                <span class="absolute left-0 top-0 h-1 w-full rounded-t-xl bg-purple-600"></span>
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                            <i class="fa-solid fa-graduation-cap text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level :level', ['level' => $level['label']]) }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Year :year', ['year' => $level['year']]) }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __(':count course(s) assigned.', ['count' => $level['count']]) }}</p>
                </div>
                <a href="{{ route('program.manage', ['program_id' => $program->id, 'form_level' => $level['year']]) }}" wire:navigate class="mt-6 inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 transition-colors">
                    <i class="fa-solid fa-gear mr-1.5 text-xs"></i>
                    {{ __('Manage courses') }}
                </a>
            </x-card>
        @endforeach
    </div>

    <!-- Detailed Courses Listing -->
    <div class="space-y-6">
        <h2 class="text-base font-bold text-gray-950 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-list-check text-purple-600 dark:text-purple-400"></i>
            {{ __('Assigned Courses Directory') }}
        </h2>

        @forelse ($coursesByLevel as $level => $courses)
            <x-card class="overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('Year :level Courses (:count)', ['level' => $level, 'count' => $courses->count()]) }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50/50 dark:bg-gray-900/30">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Course Code') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Semester') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Lecturer') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach ($courses as $course)
                                <tr wire:key="c-{{ $course->id }}">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-semibold text-gray-900 dark:text-gray-150">
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400">
                                            {{ $course->code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-250">
                                        {{ $course->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-655 dark:text-gray-300">
                                        {{ __('Semester :semester', ['semester' => $course->course_semester]) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-655 dark:text-gray-300">
                                        @if ($course->teacher)
                                            <i class="fa-solid fa-chalkboard-user mr-1 text-gray-400"></i>
                                            {{ trim(($course->teacher->lastname ?? '').' '.($course->teacher->othernames ?? '')) ?: ($course->teacher->user?->username ?? '—') }}
                                        @else
                                            <span class="text-gray-400 italic">{{ __('Not assigned') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-card class="p-8 text-center">
                <x-college.empty-state
                    :title="__('No courses assigned yet')"
                    :description="__('Configure course assignments by clicking the Manage courses buttons above.')"
                    class="border-none bg-transparent py-4"
                >
                    <x-slot:icon>
                        <i class="fa-solid fa-book-open-reader text-4xl text-gray-300 dark:text-gray-600 block"></i>
                    </x-slot:icon>
                </x-college.empty-state>
            </x-card>
        @endforelse
    </div>
</div>
