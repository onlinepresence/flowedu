<x-slot name="headerActions">
    <div x-data>
        <button type="button" x-on:click="$dispatch('open-create-announcement-modal')" class="inline-flex w-full items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors sm:w-auto">
            <svg class="mr-2 h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Create Announcement') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-5xl space-y-6"
    x-data
    x-on:open-create-announcement-modal.window="$wire.openCreateModal()"
>

    <!-- Announcements Feed list -->
    @if ($rows->isEmpty())
        <x-college.empty-state
            :title="__('No announcements recorded')"
            :description="__('You have not published any announcements for your course cohorts yet.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" /></svg>
            </x-slot:icon>
            <div class="mt-4">
                <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
                    {{ __('Post your first announcement') }}
                </button>
            </div>
        </x-college.empty-state>
    @else
        <div class="space-y-4">
            @foreach ($rows as $row)
                <div wire:key="ann-row-{{ $row->id }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-750 dark:bg-gray-800 transition hover:shadow-md p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <!-- Course Code & Timestamp -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-350">
                                    {{ $row->course?->code }} — {{ $row->course?->name }}
                                </span>
                                @if ($row->academicSession)
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $row->academicSession->name }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $row->created_at->format('M d, Y H:i') }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $row->title }}
                            </h3>

                            <!-- Body -->
                            <p class="text-sm text-gray-700 dark:text-gray-250 whitespace-pre-line leading-relaxed">
                                {{ $row->body }}
                            </p>

                            <!-- Rejection Remark if Rejected -->
                            @if ($row->status === 'rejected' && $row->rejection_reason)
                                <div class="rounded-lg bg-rose-50 border border-rose-100 p-3.5 text-xs text-rose-800 dark:bg-rose-950/20 dark:border-rose-900/40 dark:text-rose-300 mt-3">
                                    <span class="font-bold uppercase tracking-wider block mb-1 text-[10px]">{{ __('Reason for rejection') }}:</span>
                                    "{{ $row->rejection_reason }}"
                                </div>
                            @endif
                        </div>

                        <!-- Status Badge & Action Menu -->
                        <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-start gap-4 shrink-0 border-t md:border-t-0 pt-4 md:pt-0 border-gray-100 dark:border-gray-700">
                            @php
                                $statusClass = match($row->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-450 border border-emerald-100 dark:border-emerald-900/40',
                                    'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-450 border border-rose-100 dark:border-rose-900/40',
                                    'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-450 border border-amber-100 dark:border-amber-900/40',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600'
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($row->status === 'pending' ? 'Pending Review' : $row->status) }}
                            </span>

                            <div class="flex items-center space-x-2">
                                <button type="button" wire:click="openCreateModal({{ $row->id }})" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.062a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    {{ __('Edit') }}
                                </button>
                                <button type="button" wire:click="openDeleteModal({{ $row->id }})" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm hover:bg-red-50 dark:border-red-900/40 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-950/20 transition-colors">
                                    <svg class="mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($rows->hasPages())
            <div class="pt-4">
                {{ $rows->links() }}
            </div>
        @endif
    @endif

    <!-- Create/Edit Modal -->
    <x-college.modal name="announcement-create-modal" :livewireSynced="true" title="{{ $announcementId ? __('Edit Announcement') : __('Create Announcement') }}" maxWidth="xl">
        <form wire:submit.prevent="saveAnnouncement(false)" class="space-y-4">
            <!-- Class Selection -->
            <div>
                <label for="form-course" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Select Class / Course') }}</label>
                <select wire:model="courseId" id="form-course" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500">
                    <option value="">{{ __('Choose a course...') }}</option>
                    @foreach ($assignedCourses as $ac)
                        <option value="{{ $ac->id }}">{{ $ac->code }} — {{ $ac->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('courseId')" class="mt-1" />
            </div>

            <!-- Title -->
            <div>
                <label for="form-title" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Title') }}</label>
                <x-text-input wire:model="title" id="form-title" type="text" placeholder="{{ __('Enter announcement title…') }}" class="w-full" />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <!-- Body -->
            <div>
                <label for="form-body" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Content') }}</label>
                <textarea wire:model="body" id="form-body" rows="6" placeholder="{{ __('Type your announcement details here for your students…') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-1" />
            </div>

            <!-- Modal Actions -->
            <div class="flex items-center justify-end space-x-3 border-t border-gray-100 dark:border-gray-700 pt-4 mt-6">
                <button type="button" wire:click="resetForm" x-on:click="$dispatch('close-modal', 'announcement-create-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="saveAnnouncement(true)" class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                    {{ __('Save as Draft') }}
                </button>
                <button type="submit" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
                    {{ __('Submit for Review') }}
                </button>
            </div>
        </form>
    </x-college.modal>

    <!-- Delete Confirmation Modal -->
    <x-college.confirm-modal
        name="delete-announcement-modal"
        title="{{ __('Confirm Deletion') }}"
        type="danger"
        confirmText="{{ __('Delete Permanently') }}"
        cancelText="{{ __('Cancel') }}"
        wireConfirm="deleteAnnouncement"
    >
        {{ __('Are you sure you want to delete this announcement? This action cannot be undone.') }}
    </x-college.confirm-modal>

</div>
