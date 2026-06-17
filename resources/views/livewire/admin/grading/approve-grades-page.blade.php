<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <div class="flex flex-wrap items-end gap-3">
            <a 
                href="{{ route('admin.grading.enter') }}" 
                class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 hover:bg-purple-500 text-white px-3.5 py-2 text-xs font-semibold shadow-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-keyboard"></i>
                {{ __('Enter results') }}
            </a>
            <a 
                href="{{ route('admin.grading.upload') }}" 
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-3.5 py-2 text-xs font-semibold shadow-sm transition focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                <i class="fa-solid fa-file-arrow-up"></i>
                {{ __('Upload results') }}
            </a>
            <div class="w-48 text-left">
                <label class="block text-2xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Academic Session') }}</label>
                <select 
                    wire:model.live="academicSessionId" 
                    class="mt-1 block w-full rounded-md border-gray-300 text-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    <!-- Filters Section -->
    <div x-data="{ openFilters: false }" class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-950 dark:text-white uppercase tracking-wider text-purple-650">{{ __('Cascade Approval Filters') }}</h2>
            <button 
                type="button" 
                x-on:click="openFilters = !openFilters" 
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 sm:hidden"
            >
                <i class="fa-solid fa-filter"></i>
                <span x-text="openFilters ? '{{ __('Hide Filters') }}' : '{{ __('Show Filters') }}'"></span>
                <i class="fa-solid text-2xs" :class="openFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <div :class="openFilters ? 'grid' : 'hidden sm:grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Faculty -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Faculty') }}</label>
                <select wire:model.live="facultyId" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ __('All Faculties') }}</option>
                    @foreach ($faculties as $fac)
                        <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Department') }}</label>
                <select wire:model.live="departmentId" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" @disabled(! $facultyId)>
                    <option value="">{{ __('All Departments') }}</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Program -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Program') }}</label>
                <select wire:model.live="programId" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" @disabled(! $departmentId)>
                    <option value="">{{ __('All Programs') }}</option>
                    @foreach ($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Course -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Course') }}</label>
                <select wire:model.live="courseId" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" @disabled(! $programId)>
                    <option value="">{{ __('All Courses') }}</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Year Level -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Year Level') }}</label>
                <select wire:model.live="level" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ __('All Levels') }}</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="400">400</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Approval Status') }}</label>
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="pending">{{ __('Pending Approval') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                </select>
            </div>

            <!-- Search Lecturer -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Search Lecturer Name') }}</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-user-tie text-gray-400"></i>
                    </div>
                    <input 
                        wire:model.live.debounce.300ms="searchLecturer" 
                        type="text" 
                        class="block w-full rounded-md border-gray-300 pl-10 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="{{ __('Type teacher last name or first name to filter...') }}"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Cohort Pending List -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Program & Level') }}</th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Course') }}</th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Submitted By') }}</th>
                        <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">
                            @if ($filterStatus === 'pending')
                                {{ __('Pending Records') }}
                            @elseif ($filterStatus === 'approved')
                                {{ __('Approved Records') }}
                            @else
                                {{ __('Rejected Records') }}
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($cohorts as $cohort)
                        <tr wire:key="cohort-{{ $cohort->teacher_id }}-{{ $cohort->course_id }}" class="hover:bg-gray-50 dark:hover:bg-gray-850">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $cohort->program_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Year Level:') }} {{ $cohort->level }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 mb-1">
                                    {{ $cohort->course_code }}
                                </span>
                                <div class="text-gray-700 dark:text-gray-300 font-medium">{{ $cohort->course_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ $cohort->teacher_name ?: __('Anonymous Teacher') }}
                            </td>
                            <td class="px-6 py-4 text-center bg-purple-50/30 dark:bg-purple-950/10">
                                @if ($filterStatus === 'pending')
                                    <span class="rounded bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                        {{ $cohort->pending_count }} {{ __('Students') }}
                                    </span>
                                @elseif ($filterStatus === 'approved')
                                    <span class="rounded bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        {{ $cohort->pending_count }} {{ __('Students') }}
                                    </span>
                                @else
                                    <span class="rounded bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                        {{ $cohort->pending_count }} {{ __('Students') }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <button 
                                    type="button"
                                    wire:click="viewCohort({{ $cohort->teacher_id }}, {{ $cohort->course_id }}, {{ $cohort->academic_session_id }}, {{ $cohort->program_id }}, '{{ $cohort->level }}')"
                                    class="mr-2 inline-flex items-center gap-1.5 rounded bg-purple-600 hover:bg-purple-500 px-3 py-1.5 text-xs font-semibold text-white transition focus:outline-none"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    {{ __('View') }}
                                </button>
                                @if ($filterStatus === 'pending')
                                    <button 
                                        type="button"
                                        wire:click="approveCohort({{ $cohort->teacher_id }}, {{ $cohort->course_id }}, {{ $cohort->academic_session_id }}, {{ $cohort->program_id }}, '{{ $cohort->level }}')"
                                        class="inline-flex items-center gap-1.5 rounded bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white transition focus:outline-none"
                                    >
                                        <i class="fa-solid fa-square-check"></i>
                                        {{ __('Approve All') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-circle-check text-green-500 text-4xl mb-4"></i>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('All Clear!') }}</h3>
                                <p class="mt-1 text-xs">{{ __('There are no results matching the chosen filters.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cohorts->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                {{ $cohorts->links() }}
            </div>
        @endif
    </div>

    <!-- Cohort details modal -->
    <x-college.modal
        name="cohort-details-modal"
        :title="__('Cohort Student Results Details')"
        maxWidth="5xl"
    >
        @if ($cohortGrades->isNotEmpty())
            <div class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 grid gap-2 sm:grid-cols-2 text-xs">
                    <div>
                        <span class="font-bold text-gray-500 uppercase">{{ __('Course:') }}</span>
                        <span class="text-gray-900 dark:text-white ml-1 font-semibold">
                            {{ $cohortGrades->first()->result->course->code }} - {{ $cohortGrades->first()->result->course->name }}
                        </span>
                    </div>
                    <div>
                        <span class="font-bold text-gray-500 uppercase">{{ __('Cohort Level:') }}</span>
                        <span class="text-gray-900 dark:text-white ml-1 font-semibold">{{ $selectedLevel }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Index Number') }}</th>
                                <th scope="col" class="px-4 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Student Name') }}</th>
                                <th scope="col" class="px-2 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Attendance') }}</th>
                                <th scope="col" class="px-2 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Midsem') }}</th>
                                <th scope="col" class="px-2 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Project') }}</th>
                                <th scope="col" class="px-2 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Exam') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Total') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Grade') }}</th>
                                @if ($filterStatus === 'pending')
                                    <th scope="col" class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-400">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($cohortGrades as $cg)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $cg->student->index_number }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ trim(($cg->student->lastname ?? '').' '.($cg->student->firstname ?? '')) }}
                                    </td>
                                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">
                                        {{ $cg->attendance_score !== null ? floatval($cg->attendance_score) : '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">
                                        {{ $cg->midsem_score !== null ? floatval($cg->midsem_score) : '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">
                                        {{ $cg->project_score !== null ? floatval($cg->project_score) : '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">
                                        {{ $cg->exam_score !== null ? floatval($cg->exam_score) : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-bold text-gray-950 dark:text-white bg-purple-50/30 dark:bg-purple-950/10 whitespace-nowrap">
                                        {{ floatval($cg->result->score) }}
                                    </td>
                                    <td class="px-3 py-3 text-center bg-purple-50/30 dark:bg-purple-950/10">
                                        <span class="inline-flex items-center rounded bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                            {{ $cg->result->grade }}
                                        </span>
                                    </td>
                                    @if ($filterStatus === 'pending')
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <button 
                                                type="button"
                                                wire:click="approveIndividual({{ $cg->id }})"
                                                class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 mr-2"
                                                title="{{ __('Approve') }}"
                                            >
                                                <i class="fa-solid fa-circle-check text-base"></i>
                                            </button>
                                            <button 
                                                type="button"
                                                wire:click="rejectIndividual({{ $cg->id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="{{ __('Reject') }}"
                                            >
                                                <i class="fa-solid fa-circle-xmark text-base"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center border-t border-gray-200 pt-4 dark:border-gray-700">
                    @if ($filterStatus === 'pending')
                        <button
                            type="button"
                            wire:click="rejectCohort({{ $selectedTeacherId }}, {{ $selectedCourseId }}, {{ $selectedSessionId }}, {{ $selectedProgramId }}, '{{ $selectedLevel }}')"
                            class="rounded-md border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 px-4 py-2 text-sm font-semibold transition dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-950/40"
                        >
                            {{ __('Reject Entire Cohort') }}
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div class="flex gap-2">
                        <button
                            type="button"
                            x-on:click="$dispatch('close-modal', 'cohort-details-modal')"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            @if ($filterStatus === 'pending')
                                {{ __('Cancel') }}
                            @else
                                {{ __('Close') }}
                            @endif
                        </button>
                        @if ($filterStatus === 'pending')
                            <button
                                type="button"
                                wire:click="approveCohort({{ $selectedTeacherId }}, {{ $selectedCourseId }}, {{ $selectedSessionId }}, {{ $selectedProgramId }}, '{{ $selectedLevel }}')"
                                class="rounded-md bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 text-sm font-semibold transition shadow-sm focus:outline-none"
                            >
                                {{ __('Approve Entire Cohort') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </x-college.modal>
</div>
