<x-slot name="headerActions">
    <div x-data>
        <button type="button" x-on:click="$dispatch('open-upload-modal')" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Share Lesson Plan') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-upload-modal.window="$wire.openUploadModal()"
>



    <!-- Filters Section -->
    <div class="flex items-center bg-white p-4 rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="relative w-full">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search shared plan title, description, or author...') }}" class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" /></svg>
            </div>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max table-auto text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900/50 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        <th class="px-6 py-4">{{ __('Resource Details') }}</th>
                        <th class="px-6 py-4">{{ __('Shared By') }}</th>
                        <th class="px-6 py-4">{{ __('File Metadata') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Date Shared') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-900 dark:text-gray-200">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors align-top">
                            <td class="px-6 py-4 max-w-sm whitespace-normal">
                                <div class="font-semibold text-gray-950 dark:text-white">{{ $plan->title }}</div>
                                @if($plan->description)
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $plan->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                {{ $plan->teacher->user->name }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-950 dark:text-white truncate max-w-xs" title="{{ $plan->file_name }}">
                                    {{ $plan->file_name }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ number_format($plan->file_size / 1024, 1) }} KB
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">
                                {{ $plan->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <button type="button" wire:click="downloadPlan({{ $plan->id }})" class="text-purple-600 hover:text-purple-900 dark:hover:text-purple-400 font-semibold">
                                        {{ __('Download') }}
                                    </button>
                                    @if(auth()->user()?->teacher && (int)$plan->teacher_id === (int)auth()->user()->teacher->id)
                                        <span class="text-gray-300 dark:text-gray-600">|</span>
                                        <button type="button" wire:click="deletePlan({{ $plan->id }})" wire:confirm="{{ __('Are you sure you want to delete this shared lesson plan?') }}" class="text-red-600 hover:text-red-900 dark:hover:text-red-400 font-medium">
                                            {{ __('Delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <x-college.empty-state
                                    title="{{ __('No resources shared') }}"
                                    description="{{ __('There are no shared lesson plans or templates in your department yet.') }}"
                                >
                                    <x-slot:icon>
                                        <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                    </x-slot:icon>
                                </x-college.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($plans->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $plans->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Share Plan -->
    @if ($showUploadModal)
        <x-college.modal name="upload-plan-modal" title="{{ __('Share Lesson Plan / Material') }}" :show="true" livewireSynced="true">
            <form id="upload-plan-form" wire:submit.prevent="savePlan" class="space-y-4">
                <div>
                    <label for="plan-title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Resource Title') }}</label>
                    <input type="text" id="plan-title" wire:model="title" placeholder="{{ __('e.g. Grade 10 Algebra Lesson Note Guide') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="plan-description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Brief Description (Optional)') }}</label>
                    <textarea id="plan-description" wire:model="description" rows="3" placeholder="{{ __('What is this resource about? Any suggestions for using it?') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Attachment File') }} <span class="text-red-500">*</span></span>
                    <x-filepond
                        field="planFilePond"
                        purpose="teacher_lesson_plan"
                        :label="__('Lesson Plan File')"
                        accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,image/jpeg,image/png"
                    />
                    @error('planFilePond')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-slot:footer>
                    <button type="button" wire:click="closeUploadModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="upload-plan-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Share Resource') }}</span>
                        <span wire:loading>{{ __('Processing...') }}</span>
                    </button>
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

</div>
