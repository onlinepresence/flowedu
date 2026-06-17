<div class="mx-auto max-w-7xl space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Job Board & Activities Management') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Publish and manage campus opportunities, jobs, and student activities.') }}</p>
        </div>
        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            {{ __('Post Opportunity') }}
        </button>
    </div>

    <!-- Main Card -->
    <x-card>
        <!-- Filters Header -->
        <div class="flex flex-col gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                    </div>
                    <x-text-input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="{{ __('Search title, organizer…') }}"
                        class="block w-full pl-9 text-sm"
                    />
                </div>
                <div>
                    <select wire:model.live="typeFilter" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('All Types') }}</option>
                        <option value="job">{{ __('Jobs Only') }}</option>
                        <option value="activity">{{ __('Activities Only') }}</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="statusFilter" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                        <option value="active">{{ __('Active / Open') }}</option>
                        <option value="expired">{{ __('Expired (Last 3 Months)') }}</option>
                        <option value="archived">{{ __('Archived (Older than 3 Months)') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Directory -->
        <div class="overflow-x-auto -mx-6 -my-5 mt-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Opportunity Title') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Company / Organizer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Expiry Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        @php
                            $today = now()->toDateString();
                            $threeMonthsAgo = now()->subMonths(3)->toDateString();
                            $expiryStr = $row->expiry_date->toDateString();
                            $isExpired = $expiryStr < $today;
                            $isArchived = $expiryStr < $threeMonthsAgo;
                        @endphp
                        <tr wire:key="job-row-{{ $row->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/10">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row->title }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if ($row->type === 'job')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                        <i class="fa-solid fa-briefcase mr-1 mt-0.5"></i> {{ __('Job') }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                        <i class="fa-solid fa-users mr-1 mt-0.5"></i> {{ __('Activity') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->company_or_organizer ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $row->expiry_date->format('F d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if ($isArchived)
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-300">{{ __('Archived') }}</span>
                                @elseif ($isExpired)
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ __('Expired') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ __('Active') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($isExpired || $isArchived)
                                        <button
                                            type="button"
                                            wire:click="openReactivateModal({{ $row->id }})"
                                            class="text-emerald-600 hover:text-emerald-500 hover:scale-110 transition-transform"
                                            title="{{ __('Reactivate') }}"
                                        >
                                            <i class="fa-solid fa-rotate-left text-base"></i>
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $row->id }})"
                                        class="text-indigo-600 hover:text-indigo-500 hover:scale-110 transition-transform"
                                        title="{{ __('Edit') }}"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $row->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this opportunity?') }}"
                                        class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform"
                                        title="{{ __('Delete') }}"
                                    >
                                        <i class="fa-solid fa-trash text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No job board opportunities or activities found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $rows->links() }}
        </div>
    </x-card>

    <!-- Create/Edit Opportunity Modal -->
    @if ($showFormModal)
        <x-college.modal name="opportunity-form-modal" :title="$editingId ? __('Edit Opportunity') : __('Post Opportunity')" :show="true" livewireSynced="true">
            <form wire:submit.prevent="save" class="space-y-4 pr-1">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="form-title" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Opportunity Title') }}</label>
                        <x-text-input wire:model="title" id="form-title" type="text" class="block w-full text-sm" required />
                        @error('title')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
    
                    <div>
                        <label for="form-type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Type') }}</label>
                        <select wire:model="type" id="form-type" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white" required>
                            <option value="job">{{ __('Job Alert') }}</option>
                            <option value="activity">{{ __('Student Activity / Club / Event') }}</option>
                        </select>
                        @error('type')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
    
                    <div>
                        <label for="form-organizer" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Company / Organizer') }}</label>
                        <x-text-input wire:model="company_or_organizer" id="form-organizer" type="text" class="block w-full text-sm" placeholder="e.g. Google, Student SRC, etc." />
                        @error('company_or_organizer')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
    
                    <div class="sm:col-span-2">
                        <label for="form-expiry" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Expiry / Closing Date') }}</label>
                        <x-text-input wire:model="expiry_date" id="form-expiry" type="date" class="block w-full text-sm" required />
                        @error('expiry_date')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
    
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description (Rich Text)') }}</label>
                        <div wire:ignore class="mt-1">
                            <div id="editor-container" class="h-44 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white"></div>
                        </div>
                        @error('description')<span class="text-xs text-red-650 mt-1 block">{{ $message }}</span>@enderror
                    </div>
    
                    <div class="sm:col-span-2">
                        <label for="form-requirements" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Requirements / Additional Info') }} <span class="font-normal text-gray-400">({{ __('optional') }})</span></label>
                        <x-textarea-input wire:model="requirements" id="form-requirements" rows="3" class="block w-full text-sm" placeholder="e.g. GPA >= 3.0, Level 300+ students preferred..." />
                        @error('requirements')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                    </div>
                </div>
    
                <x-slot name="footer">
                    <button
                        type="button"
                        wire:click="closeFormModal"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        {{ __('Save Opportunity') }}
                    </button>
                </x-slot>
            </form>
        </x-college.modal>
    @endif
    
    <!-- Reactivate Opportunity Modal -->
    @if ($showReactivateModal)
        <x-college.modal name="reactivate-opportunity-modal" :title="__('Reactivate Opportunity')" :show="true" livewireSynced="true">
            <form wire:submit.prevent="reactivate" class="space-y-4 pr-1">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('Reactivating this post will move it back to the Active Board. Please select a new closing date.') }}</p>
                    <label for="new-expiry" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('New Expiry / Closing Date') }}</label>
                    <x-text-input wire:model="newExpiryDate" id="new-expiry" type="date" class="block w-full text-sm" required />
                    @error('newExpiryDate')<span class="text-xs text-red-650">{{ $message }}</span>@enderror
                </div>
    
                <x-slot name="footer">
                    <button
                        type="button"
                        wire:click="closeReactivateModal"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        {{ __('Reactivate') }}
                    </button>
                </x-slot>
            </form>
        </x-college.modal>
    @endif

    <!-- Quill Styles & Scripts -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>
        document.addEventListener('livewire:init', () => {
            let quillInstance;

            const initQuill = (content = '') => {
                const container = document.getElementById('editor-container');
                if (!container) return;

                // If quill was already initialized on this element, just set content
                if (quillInstance && container.querySelector('.ql-editor')) {
                    quillInstance.root.innerHTML = content;
                    return;
                }

                quillInstance = new Quill('#editor-container', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    }
                });

                // Set initial content
                quillInstance.root.innerHTML = content;

                // Sync Quill changes back to Livewire component
                quillInstance.on('text-change', () => {
                    const html = quillInstance.root.innerHTML;
                    // Only update if it contains actual content to avoid resetting model selection state
                    @this.set('description', html === '<p><br></p>' ? '' : html);
                });
            };

            // Listen to initialization events dispatched from livewire
            Livewire.on('init-editor', (data) => {
                // Wait slightly for modal to mount/render
                setTimeout(() => {
                    initQuill(data.content || '');
                }, 100);
            });
        });
    </script>
</div>
