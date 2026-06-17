<x-slot name="headerActions">
    <div class="flex items-center space-x-3" x-data>
        <button type="button" x-on:click="$dispatch('open-mark-attendance-manually')" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
            {{ __('Mark Attendance Manually') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-mark-attendance-manually.window="$wire.openMarkAttendanceModal()"
>

    @if ($courses->isEmpty())
        <x-college.empty-state
            :title="__('No assigned courses')"
            :description="__('You need at least one assigned course before you can submit attendance sheets.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <!-- Filter Card Container -->
        <x-college.filter-card cols="3">
            <div>
                <x-input-label for="att-course" :value="__('Select course')" class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500" />
                <x-select-input wire:model.live="courseId" id="att-course" class="w-full mt-1">
                    <option value="">{{ __('— Select course —') }}</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('courseId')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="att-date" :value="__('Date')" class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500" />
                <x-text-input wire:model.live="classDate" id="att-date" type="date" class="w-full mt-1" />
                <x-input-error :messages="$errors->get('classDate')" class="mt-1" />
            </div>
            <div class="flex items-end justify-end">
                @if ($courseId)
                    <button type="button" wire:click="downloadTemplate" class="w-full inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition-colors">
                        <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        {{ __('Download Roster Template') }}
                    </button>
                @else
                    <span class="w-full inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-400 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800/40">
                        {{ __('Select Course for Template') }}
                    </span>
                @endif
            </div>
        </x-college.filter-card>

        @if (! $courseId)
            <x-college.empty-state
                :title="__('Select a course')"
                :description="__('Choose a course and date, then upload your attendance document. You can download the pre-populated student roster template above.')"
            >
                <x-slot:icon>
                    <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                </x-slot:icon>
            </x-college.empty-state>
        @else
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-card class="p-6">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Upload Attendance Document') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Upload a filled spreadsheet (CSV/Excel) or PDF roster. Verify the file before submission.') }}</p>
                        <x-filepond
                            field="sheetPond"
                            purpose="teacher_attendance_sheet"
                            :label="__('Attendance file')"
                            accept="text/csv,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.csv,.xlsx,.xls"
                        />
                        <x-input-error :messages="$errors->get('sheetPond')" class="mt-2" />
                    </x-card>
                </div>

                <div>
                    @if ($recentSheets->isNotEmpty())
                        <div class="space-y-3">
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Recent submissions') }}</h2>
                            <x-card class="overflow-hidden">
                                <ul class="divide-y divide-gray-250 dark:divide-gray-700">
                                    @foreach ($recentSheets as $sheet)
                                        <li class="flex items-center justify-between gap-2 px-4 py-3 text-sm" wire:key="att-sh-{{ $sheet->id }}">
                                            <div class="min-w-0 flex-1">
                                                <span class="text-gray-800 dark:text-gray-200 font-semibold block truncate">
                                                    {{ $sheet->course?->code ?? '—' }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $sheet->class_date?->format('M j, Y') }}
                                                </span>
                                            </div>
                                            <a href="{{ route('teacher.attendance.sheets.download', $sheet) }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">{{ __('Download') }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-card>
                        </div>
                    @endif
                </div>

                @if ($pendingValid)
                    <div class="lg:col-span-3 rounded-xl border border-indigo-200 bg-indigo-50/20 p-6 dark:border-indigo-900/50 dark:bg-indigo-950/10 space-y-3">
                        <h3 class="text-base font-bold text-indigo-950 dark:text-indigo-200">{{ __('Review your upload') }}</h3>
                        <p class="text-sm font-semibold text-indigo-800 dark:text-indigo-400 font-mono">
                            {{ __('File: :name', ['name' => $pendingBasename]) }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Please view the file to ensure the student grid is properly filled, then submit.') }}</p>
                        <div class="flex items-center space-x-3">
                            <a
                                href="{{ route('teacher.attendance.preview') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-200 hover:bg-indigo-50 dark:bg-indigo-900 dark:text-indigo-200 dark:ring-indigo-700 dark:hover:bg-indigo-800"
                            >
                                <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                {{ __('Preview File') }}
                            </a>
                            <form wire:submit="submitSheet" class="inline-block">
                                <x-college-form-submit target="submitSheet" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 font-semibold shadow-sm">
                                    {{ __('Submit Attendance Sheet') }}
                                </x-college-form-submit>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif

    <!-- Manual Attendance Marking Modal -->
    @if ($showMarkAttendanceModal)
        <x-college.modal name="manual-attendance-modal" show="true" livewireSynced="true" title="{{ __('Mark Class Attendance Manually') }}" maxWidth="3xl">
            <form wire:submit.prevent="submitManualAttendance" class="space-y-6">
                <!-- Select Course and Date inside modal -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="modal-att-course" :value="__('Select Class Course')" class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400" />
                        <x-select-input wire:model.live="markCourseId" id="modal-att-course" class="w-full mt-1">
                            <option value="">{{ __('— Select assigned course —') }}</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('markCourseId')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="modal-att-date" :value="__('Class Session Date')" class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400" />
                        <x-text-input wire:model="markClassDate" id="modal-att-date" type="date" class="w-full mt-1" />
                        <x-input-error :messages="$errors->get('markClassDate')" class="mt-1" />
                    </div>
                </div>

                @if ($markCourseId && !empty($attendanceData))
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('Student Cohort List') }}</h4>
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-750">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Index Number') }}</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Student Name') }}</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-150 bg-white dark:divide-gray-750 dark:bg-gray-800">
                                    @foreach ($attendanceData as $studentId => $data)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750/30">
                                            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">{{ $data['index_number'] }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $data['name'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                                <label class="relative inline-flex cursor-pointer items-center justify-center">
                                                    <input type="checkbox" wire:model="attendanceData.{{ $studentId }}.present" class="peer sr-only" />
                                                    <div class="peer h-6 w-11 rounded-full bg-rose-500 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-focus:outline-none dark:border-gray-600"></div>
                                                    <span class="ml-2 text-xs font-semibold text-gray-600 dark:text-gray-400 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">
                                                        {{ $attendanceData[$studentId]['present'] ? __('Present') : __('Absent') }}
                                                    </span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($markCourseId)
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-gray-400 dark:border-gray-750">
                        <i class="fa-solid fa-users-slash text-2xl mb-2"></i>
                        <p class="text-sm">{{ __('No students enrolled in this course class assigned for this active session.') }}</p>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-gray-400 dark:border-gray-750">
                        <i class="fa-solid fa-book text-2xl mb-2"></i>
                        <p class="text-sm">{{ __('Please select a course above to display the student cohort roster.') }}</p>
                    </div>
                @endif

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-750">
                    <button type="button" wire:click="$set('showMarkAttendanceModal', false)" class="rounded-lg border border-gray-350 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    @if ($markCourseId && !empty($attendanceData))
                        <x-college-form-submit target="submitManualAttendance" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 font-semibold shadow-sm">
                            {{ __('Save & Compile Sheet') }}
                        </x-college-form-submit>
                    @endif
                </div>
            </form>
        </x-college.modal>
    @endif
</div>
