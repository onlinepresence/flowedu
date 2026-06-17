<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <a href="{{ route('program.classes', ['program_id' => $program->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            {{ __('Back to classes') }}
        </a>
    </x-slot>

    <!-- Create/Edit Form Card -->
    <x-card>
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-950 dark:text-white flex items-center gap-2">
                <i class="fa-solid {{ $editingCourseId ? 'fa-pen-to-square' : 'fa-plus' }} text-purple-650 dark:text-purple-400"></i>
                {{ $editingCourseId ? __('Edit Course Details') : __('Add New Course') }}
            </h2>
        </div>
        <form wire:submit="{{ $editingCourseId ? 'updateCourse' : 'saveCourse' }}" class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="course-name" :value="__('Course name')" />
                    <x-text-input id="course-name" type="text" wire:model="course_name" class="mt-1 block w-full text-sm" required placeholder="{{ __('e.g. Introduction to Database Systems') }}" />
                    <x-input-error :messages="$errors->get('course_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="course-code" :value="__('Course code')" />
                    <x-text-input id="course-code" type="text" wire:model="course_code" class="mt-1 block w-full text-sm" placeholder="{{ __('Leave blank to auto-generate') }}" />
                    <x-input-error :messages="$errors->get('course_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="course-semester" :value="__('Course semester')" />
                    <select id="course-semester" wire:model="course_semester" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm">
                        <option value="1">{{ __('Semester 1') }}</option>
                        <option value="2">{{ __('Semester 2') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('course_semester')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="course-teacher" :value="__('Assigned Lecturer')" />
                    <select id="course-teacher" wire:model="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm">
                        <option value="">{{ __('Not assigned') }}</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ trim(($teacher->lastname ?? '').' '.($teacher->othernames ?? '')) ?: ($teacher->user?->username ?? $teacher->id) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('teacher_id')" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-college-form-submit target="{{ $editingCourseId ? 'updateCourse' : 'saveCourse' }}">
                    <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                    {{ $editingCourseId ? __('Update course') : __('Add course') }}
                </x-college-form-submit>
                @if ($editingCourseId)
                    <button type="button" wire:click="cancelEditCourse" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-750 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('Cancel') }}
                    </button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Courses Table Card -->
    <x-card class="overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                {{ __('Assigned Courses List') }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/50 dark:bg-gray-900/30">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Code') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Lecturer') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Semester') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse ($courses as $course)
                        <tr wire:key="mc-{{ $course->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-semibold text-gray-900 dark:text-gray-150">
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400">
                                    {{ $course->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ $course->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-655 dark:text-gray-300">
                                @if ($course->teacher)
                                    <i class="fa-solid fa-chalkboard-user mr-1 text-gray-400"></i>
                                    {{ trim(($course->teacher->lastname ?? '').' '.($course->teacher->othernames ?? '')) ?: ($course->teacher->user?->username ?? $course->teacher_id) }}
                                @else
                                    <span class="text-gray-400 italic">{{ __('Not assigned') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-655 dark:text-gray-300">
                                {{ __('Semester :semester', ['semester' => $course->course_semester]) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <button
                                    type="button"
                                    wire:click="editCourse({{ $course->id }})"
                                    class="inline-block text-gray-400 hover:text-purple-600 hover:scale-110 transition-all duration-150"
                                    title="{{ __('Edit Course') }}"
                                >
                                    <i class="fa-solid fa-pen text-base"></i>
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmDeleteCourse({{ $course->id }})"
                                    class="inline-block text-gray-400 hover:text-red-600 hover:scale-110 transition-all duration-150 ml-3.5"
                                    title="{{ __('Delete Course') }}"
                                >
                                    <i class="fa-solid fa-trash text-base"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                <x-college.empty-state
                                    :title="__('No courses assigned')"
                                    :description="__('Add your first course to this year level using the form above.')"
                                    class="border-none bg-transparent py-2"
                                >
                                    <x-slot:icon>
                                        <i class="fa-solid fa-book-open text-3xl text-gray-300 dark:text-gray-600"></i>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Delete Confirmation Modal -->
    <x-college.confirm-modal
        name="confirm-delete-course-modal"
        :title="__('Delete Course')"
        :message="__('Are you sure you want to delete this course? All related time slots and grades associated with this course may be affected. This action cannot be undone.')"
        action="deleteCourse"
    />
</div>
