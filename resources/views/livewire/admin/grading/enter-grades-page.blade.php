<div class="mx-auto max-w-7xl space-y-6">
    <!-- Grade setup validation warning -->
    @if (! $gradesSetup)
        <div class="rounded-lg border-l-4 border-yellow-400 bg-yellow-50 p-4 dark:bg-yellow-950/20 dark:border-yellow-600">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 dark:text-yellow-400 text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                        {{ __('Attention Required') }}
                    </p>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('No grade points setup detected in the system. You must configure grade bands under the "Grade Points" settings before entering or saving student results.') }}
                    </p>
                    <p class="mt-2">
                        @if (! $isTeacherMode)
                            <a href="{{ route('admin.grading.points') }}" class="text-sm font-medium text-purple-700 underline hover:text-purple-600 dark:text-purple-400 dark:hover:text-purple-300">
                                {{ __('Configure Grade Points now') }} &rarr;
                            </a>
                        @else
                            <span class="text-sm text-yellow-600 dark:text-yellow-400">{{ __('Please contact the system administrator to set up grade points.') }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <x-slot name="headerActions">
        <div class="w-48 text-left">
            <label class="block text-2xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Academic Session') }}</label>
            <select 
                wire:model.live="academicSessionId" 
                class="mt-1 block w-full rounded-md border-gray-300 text-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                @disabled(! $gradesSetup)
            >
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <!-- Filters Section -->
    <div class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 uppercase tracking-wider text-purple-650">{{ __('Course Cohort Filters') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Teacher Selector (Admin only) -->
            @if (! $isTeacherMode)
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Lecturer') }}</label>
                    <select 
                        wire:model.live="teacherId" 
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        @disabled(! $gradesSetup)
                    >
                        <option value="">{{ __('Select Lecturer') }}</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ trim(($teacher->lastname ?? '').' '.($teacher->othernames ?? '')) }} ({{ $teacher->staff_id ?? __('Teacher') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Program Selector -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Program') }}</label>
                <select 
                    wire:model.live="programId" 
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    @disabled(! $teacherId || ! $gradesSetup)
                >
                    <option value="">{{ __('Select Program') }}</option>
                    @foreach ($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Semester Selector -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Semester') }}</label>
                <select 
                    wire:model.live="semester" 
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    @disabled(! $programId || ! $gradesSetup)
                >
                    <option value="">{{ __('Select Semester') }}</option>
                    <option value="1">{{ __('Semester 1') }}</option>
                    <option value="2">{{ __('Semester 2') }}</option>
                </select>
            </div>

            <!-- Course Selector -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Course') }}</label>
                <select 
                    wire:model.live="courseId" 
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    @disabled(! $semester || ! $gradesSetup)
                >
                    <option value="">{{ __('Select Course') }}</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Level Selector -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Year Level') }}</label>
                <select 
                    wire:model.live="level" 
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    @disabled(! $courseId || ! $gradesSetup)
                >
                    <option value="">{{ __('Select Level') }}</option>
                    @foreach ($levels as $lvl)
                        <option value="{{ $lvl }}">{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Student scores entry list -->
    @if (! empty($scores))
        <div class="overflow-hidden rounded-lg border border-gray-250 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="w-12 px-6 py-3 text-center">
                                <span class="sr-only">{{ __('Select') }}</span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Index Number') }}</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Student Name') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Attendance (Max 10)') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Midsem (Max 20)') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Project (Max 10)') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Exam (Max 60)') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Total (100)') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Grade') }}</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Status & Reviews') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($scores as $studentId => $data)
                            <tr 
                                wire:key="entry-row-{{ $studentId }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-850"
                                x-data="{
                                    attendance: @entangle('scores.'.$studentId.'.attendance'),
                                    midsem: @entangle('scores.'.$studentId.'.midsem'),
                                    project: @entangle('scores.'.$studentId.'.project'),
                                    exam: @entangle('scores.'.$studentId.'.exam'),
                                    get total() {
                                        let a = parseFloat(this.attendance) || 0;
                                        let m = parseFloat(this.midsem) || 0;
                                        let p = parseFloat(this.project) || 0;
                                        let e = parseFloat(this.exam) || 0;
                                        return (a + m + p + e).toFixed(2);
                                    },
                                    get grade() {
                                        let tot = parseFloat(this.total) || 0;
                                        let scale = {{ json_encode($gradeScale) }};
                                        for (let i = 0; i < scale.length; i++) {
                                            if (tot >= scale[i].min_score && tot <= scale[i].max_score) {
                                                return scale[i].grade;
                                            }
                                        }
                                        return 'F';
                                    }
                                }"
                            >
                                <td class="px-6 py-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="scores.{{ $studentId }}.selected"
                                        @disabled(! ($data['is_editing'] ?? false))
                                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 disabled:opacity-50"
                                    />
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $data['index_number'] ?? '' }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $data['name'] ?? '' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="10"
                                        x-model="attendance"
                                        @disabled(! ($data['is_editing'] ?? false))
                                        class="w-24 mx-auto rounded border-gray-350 px-2.5 py-1 text-center text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed"
                                        placeholder="0-10"
                                    />
                                    @error('scores.'.$studentId.'.attendance') <p class="mt-1 text-2xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="20"
                                        x-model="midsem"
                                        @disabled(! ($data['is_editing'] ?? false))
                                        class="w-24 mx-auto rounded border-gray-350 px-2.5 py-1 text-center text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed"
                                        placeholder="0-20"
                                    />
                                    @error('scores.'.$studentId.'.midsem') <p class="mt-1 text-2xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="10"
                                        x-model="project"
                                        @disabled(! ($data['is_editing'] ?? false))
                                        class="w-24 mx-auto rounded border-gray-350 px-2.5 py-1 text-center text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed"
                                        placeholder="0-10"
                                    />
                                    @error('scores.'.$studentId.'.project') <p class="mt-1 text-2xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="60"
                                        x-model="exam"
                                        @disabled(! ($data['is_editing'] ?? false))
                                        class="w-24 mx-auto rounded border-gray-350 px-2.5 py-1 text-center text-sm dark:border-gray-655 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:text-gray-500 disabled:cursor-not-allowed"
                                        placeholder="0-60"
                                    />
                                    @error('scores.'.$studentId.'.exam') <p class="mt-1 text-2xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-gray-950 dark:text-white bg-purple-50/30 dark:bg-purple-950/10">
                                    <span x-text="total"></span>
                                </td>
                                <td class="px-6 py-4 text-center bg-purple-50/30 dark:bg-purple-950/10">
                                    <span 
                                        class="inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-semibold"
                                        :class="grade !== 'F' ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'"
                                        x-text="grade"
                                    ></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-left">
                                    @if (($data['status'] ?? 'new') === 'draft')
                                        <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            <i class="fa-solid fa-file-signature text-gray-550"></i>
                                            {{ __('Draft') }}
                                        </span>
                                    @elseif (($data['status'] ?? 'new') === 'pending')
                                        <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Pending Approval') }}
                                        </span>
                                    @elseif (($data['status'] ?? 'new') === 'approved')
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                            {{ __('Approved') }}
                                        </span>
                                    @elseif (($data['status'] ?? 'new') === 'rejected')
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-950/30 dark:text-red-300">
                                                <i class="fa-solid fa-circle-xmark text-red-500"></i>
                                                {{ __('Rejected') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                            {{ __('New') }}
                                        </span>
                                    @endif

                                    @if (! empty($data['review_comments']))
                                        <div class="text-2xs text-red-600 dark:text-red-400 font-semibold mt-1 max-w-xs truncate" title="{{ $data['review_comments'] }}">
                                            {{ __('Comment:') }} {{ $data['review_comments'] }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                @if ($cohortStatus === 'new' || $cohortStatus === 'draft')
                    <button
                        type="button"
                        wire:click="saveScores(true)"
                        wire:loading.attr="disabled"
                        class="inline-flex justify-center items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-750 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <i class="fa-solid fa-file-pen mr-2"></i>
                        {{ __('Save as Draft') }}
                    </button>
                    <button
                        type="button"
                        wire:click="saveScores(false)"
                        wire:loading.attr="disabled"
                        class="inline-flex justify-center items-center rounded-lg bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-500 shadow-sm transition"
                    >
                        <i class="fa-solid fa-cloud-arrow-up mr-2"></i>
                        {{ $isTeacherMode ? __('Submit for Approval') : __('Submit & Approve') }}
                    </button>
                @elseif ($cohortStatus === 'rejected')
                    <button
                        type="button"
                        wire:click="convertToDraft"
                        wire:loading.attr="disabled"
                        class="inline-flex justify-center items-center rounded-lg bg-purple-600 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-500 shadow-sm transition"
                    >
                        <i class="fa-solid fa-pen-to-square mr-2"></i>
                        {{ __('Edit Results') }}
                    </button>
                @endif
            </div>
        </div>
    @elseif ($level)
        <div class="rounded-lg border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <i class="fa-solid fa-users text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No Students Found') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('There are no students registered in this Program under Year Level :level.', ['level' => $level]) }}
            </p>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <i class="fa-solid fa-filter text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Select Cohort') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Please select a Program, Semester, Course, and Year Level above to load the students list.') }}
            </p>
        </div>
    @endif
</div>
