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
                        {{ __('No grade points setup detected in the system. You must configure grade bands under the "Grade Points" settings before importing student results.') }}
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

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Configuration and Upload file -->
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 uppercase tracking-wider text-purple-650">{{ __('1. Choose Cohort') }}</h2>
                <div class="space-y-4">
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

                    <!-- Course Selector -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Course') }}</label>
                        <select 
                            wire:model.live="courseId" 
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            @disabled(! $programId || ! $gradesSetup)
                        >
                            <option value="">{{ __('Select Course') }}</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }} (Semester {{ $c->course_semester }})</option>
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

                    @if ($level && $courseId && $programId && $teacherId)
                        <div class="pt-2">
                            <button 
                                type="button"
                                wire:click="downloadTemplate"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-purple-100 hover:bg-purple-200 text-purple-700 px-4 py-2.5 text-sm font-semibold transition dark:bg-purple-950/40 dark:hover:bg-purple-950/60 dark:text-purple-300"
                            >
                                <i class="fa-solid fa-file-excel text-base"></i>
                                {{ __('Download Class List Template') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Upload spreadsheet -->
            <div class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 uppercase tracking-wider text-purple-650">{{ __('2. Upload Excel File') }}</h2>
                <div class="space-y-4">
                    @if (! $level)
                        <div class="rounded-md bg-blue-50 p-3.5 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800">
                            <div class="flex gap-2">
                                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                    <p class="font-semibold">{{ __('Choose Cohort First') }}</p>
                                    <p class="mt-0.5">{{ __('Please select the Lecturer, Program, Course, and Year Level on the left side before uploading the spreadsheet.') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-filepond
                        field="spreadsheetPond"
                        purpose="results_upload"
                        :label="__('Choose Spreadsheet File')"
                        accept=".xlsx,.xls,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    />

                    <div class="flex gap-3">
                        <button 
                            type="button" 
                            wire:click="analyze" 
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 transition focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                            @disabled(! $spreadsheetPond || ! $level)
                        >
                            <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="analyze"></i>
                            <i class="fa-solid fa-magnifying-glass-chart" wire:loading.remove wire:target="analyze"></i>
                            <span>{{ __('Read & Analyze') }}</span>
                        </button>
                    </div>

                    @if ($detectedRows !== null)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-circle-info mr-1 text-purple-500"></i>
                            {{ __('Detected :n data rows in the spreadsheet.', ['n' => $detectedRows]) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Preview and Confirm -->
        <div class="lg:col-span-2">
            <div class="h-full rounded-lg border border-gray-250 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Parsed Student Results Preview') }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Check parsed data columns, edit inline if needed, and confirm import.') }}</p>
                    </div>
                </div>

                <div class="flex-grow overflow-x-auto min-h-[300px]">
                    @if (empty($previewRows))
                        <div class="flex flex-col items-center justify-center h-full py-20 text-center">
                            <i class="fa-solid fa-file-import text-gray-300 dark:text-gray-600 text-5xl mb-4"></i>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No Preview Data') }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm">
                                {{ __('Download the Class List template, fill continuous assessments & exam marks, upload it, and click "Read & Analyze" to preview data here.') }}
                            </p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Excel Row') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Student Index') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Student Name') }}</th>
                                    <th scope="col" class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Att (10)') }}</th>
                                    <th scope="col" class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Mid (20)') }}</th>
                                    <th scope="col" class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Proj (10)') }}</th>
                                    <th scope="col" class="px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Exam (60)') }}</th>
                                    <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Total') }}</th>
                                    <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Grade') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($previewRows as $i => $row)
                                    <tr 
                                        wire:key="preview-row-{{ $i }}" 
                                        class="hover:bg-gray-50 dark:hover:bg-gray-850"
                                        x-data="{
                                            attendance: @entangle('previewRows.'.$i.'.attendance'),
                                            midsem: @entangle('previewRows.'.$i.'.midsem'),
                                            project: @entangle('previewRows.'.$i.'.project'),
                                            exam: @entangle('previewRows.'.$i.'.exam'),
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
                                                for (let k = 0; k < scale.length; k++) {
                                                    if (tot >= scale[k].min_score && tot <= scale[k].max_score) {
                                                        return scale[k].grade;
                                                    }
                                                }
                                                return 'F';
                                            }
                                        }"
                                    >
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $row['row_number'] }}</td>
                                        <td class="px-4 py-3">
                                            <input wire:model="previewRows.{{ $i }}.student_index" type="text" class="w-32 rounded border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                        </td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300 truncate max-w-[150px]" title="{{ $row['student_name'] ?: '—' }}">
                                            {{ $row['student_name'] ?: '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input x-model="attendance" type="number" step="0.01" min="0" max="10" class="w-16 mx-auto rounded border-gray-300 px-1 py-1 text-center text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input x-model="midsem" type="number" step="0.01" min="0" max="20" class="w-16 mx-auto rounded border-gray-300 px-1 py-1 text-center text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input x-model="project" type="number" step="0.01" min="0" max="10" class="w-16 mx-auto rounded border-gray-300 px-1 py-1 text-center text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input x-model="exam" type="number" step="0.01" min="0" max="60" class="w-16 mx-auto rounded border-gray-300 px-1 py-1 text-center text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-950 dark:text-white bg-purple-50/30 dark:bg-purple-950/10 whitespace-nowrap">
                                            <span x-text="total"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center bg-purple-50/30 dark:bg-purple-950/10">
                                            <span 
                                                class="inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-semibold"
                                                :class="grade !== 'F' ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'"
                                                x-text="grade"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($row['status'] === 'error')
                                                <span class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-950/30 dark:text-red-400">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    {{ $row['message'] }}
                                                </span>
                                            @elseif ($row['status'] === 'warning')
                                                <span class="inline-flex items-center gap-1 rounded bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-950/30 dark:text-yellow-400">
                                                    <i class="fa-solid fa-circle-question"></i>
                                                    {{ $row['message'] }}
                                                </span>
                                            @elseif ($row['status'] === 'imported')
                                                <span class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-950/30 dark:text-green-400">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    {{ __('Imported') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-900/50 dark:text-gray-400">
                                                    <i class="fa-solid fa-clock"></i>
                                                    {{ __('Ready') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @if (! empty($previewRows))
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-end">
                        <button 
                            type="button" 
                            wire:click="attemptUpload" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="attemptUpload, confirmUpload"></i>
                            <i class="fa-solid fa-file-shield text-base" wire:loading.remove wire:target="attemptUpload, confirmUpload"></i>
                            <span>{{ __('Import All Grades') }}</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Overwrite warning modal -->
    <x-college.confirm-modal
        name="confirm-overwrite-modal"
        :title="__('Results Already Exist')"
        type="warning"
        :confirmText="__('Proceed & Update')"
        :cancelText="__('Cancel')"
        wireConfirm="confirmUpload"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Grades for the selected cohort (Academic Session, Lecturer, Program, Course, Level) have already been entered/imported. Proceeding will overwrite/update the existing grades. Do you wish to continue?') }}
        </p>
    </x-college.confirm-modal>
</div>
