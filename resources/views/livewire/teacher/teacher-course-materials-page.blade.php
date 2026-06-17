<x-slot name="headerActions">
    @if ($canUpload)
        <div x-data>
            <button type="button" x-on:click="$dispatch('open-upload-material-modal')" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">
                {{ __('Upload material') }}
            </button>
        </div>
    @else
        <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-lg bg-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400 sm:w-auto">
            {{ __('Upload material') }}
        </button>
    @endif
</x-slot>

<div
    class="mx-auto max-w-7xl"
    x-data
    x-on:open-upload-material-modal.window="$wire.openUploadModal()"
>

    @if (! $canUpload)
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
            {{ __('Uploads are available only when you have at least one class on your timetable. Once the administration assigns you to scheduled classes, you can upload materials and choose the course for each file.') }}
        </div>
    @endif

    @if ($filterCourses->isNotEmpty())
        <div class="mb-6 rounded-lg bg-white p-4 shadow-md dark:bg-gray-800">
            <label for="material-course-filter" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Filter by course') }}</label>
            <select wire:model.live="course" id="material-course-filter" class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                <option value="">{{ __('All courses') }}</option>
                @foreach ($filterCourses as $fc)
                    <option value="{{ $fc->code }}">{{ $fc->code }} — {{ $fc->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($rows->isEmpty())
        @php
            $materialsEmptyDescription = __("You haven't uploaded any course materials yet.");
            if ($canUpload) {
                $materialsEmptyDescription .= ' '.__('Click Upload material to get started.');
            }
        @endphp
        <x-college.empty-state
            :title="__('No materials available')"
            :description="$materialsEmptyDescription"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-md dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Title') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Course') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Uploaded') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Size') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="mat-{{ $row->id }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->title ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->course?->code ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->file_type ? strtoupper($row->file_type) : '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $fileSizeLabels[$row->id] ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="capitalize">{{ $row->status ?? '—' }}</span>
                                    @if ($row->published)
                                        <span class="text-green-600 dark:text-green-400"> · {{ __('Published') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('teacher.courses.materials.download', $row) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ __('Download') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($showUploadModal)
        <x-college.modal name="tm-upload" :title="__('Upload material')" :show="true" maxWidth="lg" livewireSynced>
            <form id="tm-upload-form" wire:submit.prevent="saveMaterial" class="space-y-4">
                <div>
                    <label for="tm-course" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Class / course') }} <span class="text-red-500">*</span></label>
                    <select wire:model="uploadCourseId" id="tm-course" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Select a course') }}</option>
                        @foreach ($assignableCourses as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('uploadCourseId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tm-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Title') }} <span class="text-red-500">*</span></label>
                    <input wire:model="uploadTitle" id="tm-title" type="text" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                    @error('uploadTitle') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tm-desc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                    <textarea wire:model="uploadDescription" id="tm-desc" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
                    @error('uploadDescription') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('File') }} <span class="text-red-500">*</span></span>
                    <x-filepond
                        field="materialFilePond"
                        purpose="teacher_course_material"
                        :label="__('Material file')"
                        accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/zip"
                    />
                    @error('materialFilePond') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeUploadModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <x-college-form-submit type="submit" form="tm-upload-form" target="saveMaterial" class="inline-flex justify-center">{{ __('Upload') }}</x-college-form-submit>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
