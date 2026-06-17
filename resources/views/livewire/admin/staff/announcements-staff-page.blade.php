<div class="mx-auto max-w-7xl space-y-6">
    <!-- Filter and actions bar -->
    <x-college.filter-card cols="3">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Search announcements...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterCourse" :value="__('Course')" />
            <select id="filterCourse" wire:model.live="filterCourse" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Courses') }}</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterStatus" :value="__('Review Status')" />
            <select id="filterStatus" wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="pending">{{ __('Pending Review') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Table Card -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Announcement') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Course') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Lecturer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Review Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="ann-{{ $row->id }}">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $row->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 max-w-md truncate">{{ strip_tags($row->body) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row->course?->code }}
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">{{ $row->course?->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $row->teacher?->lastname }} {{ $row->teacher?->othernames }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($row->status === 'approved')
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">
                                        <i class="fa-solid fa-circle-check mr-1"></i> {{ __('Approved') }}
                                    </span>
                                @elseif ($row->status === 'rejected')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-300" title="{{ $row->rejection_reason }}">
                                        <i class="fa-solid fa-circle-xmark mr-1"></i> {{ __('Rejected') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300">
                                        <i class="fa-solid fa-clock mr-1"></i> {{ __('Pending Review') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="openReviewModal({{ $row->id }})"
                                        title="{{ __('Review & Audit') }}"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        <i class="fa-solid fa-clipboard-check fa-lg"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openDeleteModal({{ $row->id }})"
                                        title="{{ __('Delete') }}"
                                        class="text-red-600 hover:text-red-955 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <i class="fa-solid fa-trash-can fa-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No teacher announcements found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $rows->links() }}</div>
    </div>

    <!-- Review / Audit Modal -->
    @if ($showReviewModal && $selectedAnnouncement)
        <x-college.modal name="ann-review" :title="__('Review Announcement')" :show="true" maxWidth="lg" livewireSynced>
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Announcement Details') }}</h3>
                    <p class="text-base font-medium text-gray-900 dark:text-white mt-1">{{ $selectedAnnouncement->title }}</p>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-150 dark:border-gray-700 prose dark:prose-invert max-w-none">
                        {!! $selectedAnnouncement->body !!}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-gray-150 pt-3 dark:border-gray-700">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ __('Lecturer') }}</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $selectedAnnouncement->teacher?->lastname }} {{ $selectedAnnouncement->teacher?->othernames }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ __('Course') }}</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $selectedAnnouncement->course?->code }}</span>
                    </div>
                </div>

                <!-- Reject Reason Form -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Rejection Reason') }}
                        <span class="text-xs text-gray-400 font-normal">({{ __('Only required when rejecting') }})</span>
                    </label>
                    <textarea
                        wire:model="rejectionReason"
                        rows="3"
                        placeholder="{{ __('Specify exactly what needs to be fixed…') }}"
                        class="mt-1 block w-full rounded-md border-gray-350 dark:border-gray-655 dark:bg-gray-900 dark:text-white text-sm"
                    ></textarea>
                    @error('rejectionReason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeReviewModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    {{ __('Cancel') }}
                </button>
                <div class="flex gap-2">
                    <button type="button" wire:click="rejectAnnouncement" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                        <i class="fa-solid fa-circle-xmark mr-1"></i> {{ __('Reject') }}
                    </button>
                    <button type="button" wire:click="approveAnnouncement" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">
                        <i class="fa-solid fa-circle-check mr-1"></i> {{ __('Approve') }}
                    </button>
                </div>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <x-college.modal name="ann-delete" :title="__('Delete Announcement?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to permanently delete this announcement? This action cannot be undone.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeDeleteModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Delete permanently') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
