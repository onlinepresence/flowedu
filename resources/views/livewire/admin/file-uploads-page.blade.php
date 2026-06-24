@php
    $getFileIconClass = function ($mime) {
        $m = strtolower((string) $mime);
        if (str_starts_with($m, 'image/')) return 'fa-file-image text-emerald-500';
        if ($m === 'application/pdf') return 'fa-file-pdf text-rose-500';
        if (in_array($m, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) return 'fa-file-word text-blue-500';
        if (in_array($m, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])) return 'fa-file-excel text-teal-500';
        if (str_starts_with($m, 'audio/')) return 'fa-file-audio text-amber-500';
        if (str_starts_with($m, 'video/')) return 'fa-file-video text-violet-500';
        return 'fa-file-lines text-gray-500';
    };
@endphp

<x-slot name="headerActions">
    <div x-data class="flex items-center gap-3">
        <button 
            type="button" 
            x-on:click="$dispatch('trigger-upload-modal')" 
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            <i class="fa-solid fa-cloud-arrow-up"></i> {{ __('Upload File') }}
        </button>
        <button 
            type="button" 
            x-on:click="$dispatch('trigger-category-modal')" 
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
        >
            <i class="fa-solid fa-folder-plus"></i> {{ __('New Category') }}
        </button>
    </div>
</x-slot>

<div 
    x-data 
    x-on:trigger-upload-modal.window="$wire.openUploadModal()"
    x-on:trigger-category-modal.window="$wire.openCreateCategory()"
    class="mx-auto max-w-7xl space-y-6"
>
    <!-- Status Notification -->
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-950/40 dark:bg-green-950/40 dark:text-green-200 shadow-sm" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <!-- Sleek Tab Switcher -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button 
                wire:click="setTab('files')" 
                @class([
                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold transition',
                    'border-purple-500 text-purple-600 dark:text-purple-400' => $activeTab === 'files',
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'files',
                ])
            >
                <i class="fa-solid fa-file mr-2"></i>{{ __('My Files') }}
            </button>
            <button 
                wire:click="setTab('categories')" 
                @class([
                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold transition',
                    'border-purple-500 text-purple-600 dark:text-purple-400' => $activeTab === 'categories',
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'categories',
                ])
            >
                <i class="fa-solid fa-folder-tree mr-2"></i>{{ __('Manage Categories') }}
            </button>
        </nav>
    </div>

    <!-- Active Tab: Files -->
    @if ($activeTab === 'files')
        <!-- Filters & Search -->
        <x-college.filter-card cols="4">
            <div>
                <label for="filter-search" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ __('Search Files') }}
                </label>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    id="filter-search" 
                    type="search" 
                    placeholder="{{ __('Search title, filename, desc...') }}" 
                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                />
            </div>
            <div>
                <label for="filter-category" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ __('Category') }}
                </label>
                <select 
                    wire:model.live="categoryFilter" 
                    id="filter-category" 
                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-type" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ __('File Type') }}
                </label>
                <select 
                    wire:model.live="typeFilter" 
                    id="filter-type" 
                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">{{ __('All Types') }}</option>
                    <option value="image">{{ __('Images') }}</option>
                    <option value="pdf">{{ __('PDFs') }}</option>
                    <option value="document">{{ __('Word/Excel/CSV Documents') }}</option>
                    <option value="audio_video">{{ __('Audio/Video') }}</option>
                    <option value="other">{{ __('Other Files') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-sort" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ __('Sort By') }}
                </label>
                <div class="flex gap-2">
                    <select 
                        wire:model.live="sortBy" 
                        id="filter-sort" 
                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="created_at">{{ __('Upload Date') }}</option>
                        <option value="title">{{ __('File Title') }}</option>
                        <option value="file_size">{{ __('File Size') }}</option>
                    </select>
                    <select 
                        wire:model.live="sortOrder" 
                        class="block rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="desc">{{ __('DESC') }}</option>
                        <option value="asc">{{ __('ASC') }}</option>
                    </select>
                </div>
            </div>
        </x-college.filter-card>

        <!-- Files Listing Table -->
        <x-card class="overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl relative">
            <!-- Targeted Loading Overlay -->
            <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, search, categoryFilter, typeFilter, sortBy, sortOrder, setTab" 
                 class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
                <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('File Info') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Category') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Size') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Uploaded At') }}</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($files as $file)
                            <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/40 transition-colors" wire:key="file-row-{{ $file->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="text-xl shrink-0">
                                            <i class="fa-solid {{ $getFileIconClass($file->mime_type) }}"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-gray-900 dark:text-white" title="{{ $file->title }}">{{ $file->title }}</p>
                                            <p class="truncate text-xs text-gray-450 dark:text-gray-500 font-mono" title="{{ $file->original_filename }}">{{ $file->original_filename }}</p>
                                            @if ($file->description)
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5 max-w-xs">{{ $file->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300">
                                    @if ($file->category)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/30 dark:text-purple-400 border border-purple-100 dark:border-purple-900/45">
                                            <i class="fa-solid fa-folder text-[10px]"></i>
                                            {{ $file->category->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">{{ __('None') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-medium font-mono">
                                    {{ $file->formatted_size }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300 font-mono">
                                    {{ $file->created_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-3">
                                        <a 
                                            href="{{ route('admin.file-uploads.download', $file->id) }}" 
                                            class="inline-flex items-center gap-1 font-bold text-indigo-650 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            <i class="fa-solid fa-download"></i>{{ __('Download') }}
                                        </a>
                                        <span class="text-gray-300 dark:text-gray-700">|</span>
                                        <button 
                                            type="button" 
                                            wire:click="confirmDeleteFile({{ $file->id }})" 
                                            class="inline-flex items-center gap-1 font-bold text-red-600 hover:text-red-700"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>{{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <x-college.empty-state 
                                        :title="__('No Files Uploaded')" 
                                        :description="__('Start by uploading a file or defining some categories first.')"
                                    >
                                        <x-slot name="icon">
                                            <i class="fa-solid fa-folder-open text-gray-300 dark:text-gray-650 text-4xl block mb-2"></i>
                                        </x-slot>
                                    </x-college.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($files->hasPages())
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-750">
                    {{ $files->links() }}
                </div>
            @endif
        </x-card>
    @endif

    <!-- Active Tab: Categories -->
    @if ($activeTab === 'categories')
        <!-- Categories Table Card -->
        <x-card class="overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl relative">
            <!-- Targeted Loading Overlay -->
            <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, setTab" 
                 class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
                <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Category Name') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Description') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Files Count') }}</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date Created') }}</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($categoriesWithCounts as $cat)
                            <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/40 transition-colors" wire:key="category-row-{{ $cat->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-bold">{{ $cat->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-450 max-w-sm truncate" title="{{ $cat->description }}">{{ $cat->description ?: '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">
                                        <i class="fa-solid fa-file-lines text-[10px]"></i>
                                        {{ $cat->files_count }} {{ __('Files') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-650 dark:text-gray-300 font-mono">{{ $cat->created_at?->format('Y-m-d') ?: '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-3">
                                        <button 
                                            type="button" 
                                            wire:click="openEditCategory({{ $cat->id }})" 
                                            class="inline-flex items-center gap-1 font-bold text-purple-600 hover:text-purple-750 dark:text-purple-400 dark:hover:text-purple-300"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>{{ __('Edit') }}
                                        </button>
                                        <span class="text-gray-300 dark:text-gray-700">|</span>
                                        <button 
                                            type="button" 
                                            wire:click="confirmDeleteCategory({{ $cat->id }})" 
                                            class="inline-flex items-center gap-1 font-bold text-red-600 hover:text-red-700"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>{{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <x-college.empty-state 
                                        :title="__('No Categories Found')" 
                                        :description="__('Define your own custom file categories to categorize your file uploads.')"
                                    >
                                        <x-slot name="icon">
                                            <i class="fa-solid fa-folder-tree text-gray-300 dark:text-gray-650 text-4xl block mb-2"></i>
                                        </x-slot>
                                    </x-college.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categoriesWithCounts->hasPages())
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-750">
                    {{ $categoriesWithCounts->links() }}
                </div>
            @endif
        </x-card>
    @endif

    <!-- Upload File Modal (Conditional Wrapper) -->
    @if ($showUploadModal)
        <x-college.modal
            name="file-upload-modal"
            :title="__('Upload File')"
            :show="true"
            maxWidth="xl"
            livewireSynced
        >
            <form id="file-upload-form" wire:submit="saveFile" class="space-y-4 p-1">
                <div>
                    <x-input-label :value="__('File Title')" />
                    <x-text-input wire:model="fileTitle" type="text" required maxlength="255" class="mt-1.5 block w-full shadow-sm" placeholder="e.g. Q4 Financial Report" />
                    @error('fileTitle') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input-label :value="__('Category')" />
                    <x-select-input wire:model="fileCategory" class="mt-1.5 block w-full shadow-sm">
                        <option value="">{{ __('Select Category (Optional)') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </x-select-input>
                    @error('fileCategory') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input-label :value="__('Description')" />
                    <x-textarea-input wire:model="fileDescription" class="mt-1.5 block w-full shadow-sm" rows="3" placeholder="{{ __('Short description of the file...') }}" />
                    @error('fileDescription') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input-label :value="__('Choose File')" />
                    <div class="mt-1.5 flex justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-700 px-6 py-6 dark:bg-gray-900/20 bg-gray-50/50">
                        <div class="text-center">
                            <i class="fa-solid fa-file-arrow-up text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                            <div class="mt-2 flex text-sm leading-6 text-gray-600 dark:text-gray-400">
                                <label for="fileUpload" class="relative cursor-pointer rounded-md bg-white dark:bg-gray-800 font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:text-indigo-500 shadow-sm border border-gray-250 dark:border-gray-700 px-2 py-0.5 text-xs transition">
                                    <span>{{ __('Select a file') }}</span>
                                    <input id="fileUpload" type="file" wire:model="fileUpload" class="sr-only">
                                </label>
                                <p class="pl-2 pt-0.5">{{ __('or drag and drop here') }}</p>
                            </div>
                            <p class="text-[10px] leading-5 text-gray-450 dark:text-gray-500">{{ __('Maximum size allowed: 20MB') }}</p>
                            
                            @if ($fileUpload)
                                <div class="mt-2 text-xs text-green-600 dark:text-green-400 font-semibold">
                                    <i class="fa-solid fa-circle-check"></i> {{ $fileUpload->getClientOriginalName() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    @error('fileUpload') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeUploadModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="file-upload-form" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveFile">{{ __('Upload File') }}</span>
                    <span wire:loading wire:target="saveFile"><i class="fa-solid fa-circle-notch fa-spin"></i> {{ __('Uploading...') }}</span>
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Add/Edit Category Modal (Conditional Wrapper) -->
    @if ($showCategoryModal)
        <x-college.modal
            name="category-form-modal"
            :title="$isEditingCategory ? __('Edit Category') : __('Create Category')"
            :show="true"
            maxWidth="md"
            livewireSynced
        >
            <form id="category-form" wire:submit="saveCategory" class="space-y-4 p-1">
                <div>
                    <x-input-label :value="__('Category Name')" />
                    <x-text-input wire:model="categoryName" type="text" required maxlength="255" class="mt-1.5 block w-full shadow-sm" placeholder="e.g. Administrative Reports" />
                    @error('categoryName') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input-label :value="__('Description')" />
                    <x-textarea-input wire:model="categoryDescription" class="mt-1.5 block w-full shadow-sm" rows="3" placeholder="{{ __('Optional description of the category...') }}" />
                    @error('categoryDescription') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeCategoryModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="category-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition">{{ __('Save Category') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Delete File Confirm Modal -->
    @if ($showDeleteFileModal)
        <x-college.modal name="delete-file-modal" :title="__('Delete File Confirmation')" :show="true" maxWidth="md" livewireSynced>
            <div class="p-1">
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ __('Are you sure you want to delete this uploaded file? This action is permanent and cannot be undone.') }}</p>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showDeleteFileModal', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="button" wire:click="deleteFile" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">{{ __('Confirm Delete') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Delete Category Confirm Modal -->
    @if ($showDeleteCategoryModal)
        <x-college.modal name="delete-category-modal" :title="__('Delete Category Confirmation')" :show="true" maxWidth="md" livewireSynced>
            <div class="p-1">
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                    {{ __('Are you sure you want to delete this custom category? The files classified under this category will remain, but their category assignment will be removed.') }}
                </p>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showDeleteCategoryModal', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="button" wire:click="deleteCategory" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">{{ __('Confirm Delete') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
