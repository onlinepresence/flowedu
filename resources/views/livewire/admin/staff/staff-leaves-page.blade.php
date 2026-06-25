<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header Actions -->
    <x-slot name="headerActions">
        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-150"
        >
            {{ __('Request Leave') }}
        </button>
    </x-slot>

    <!-- Success Message -->
    @if (session()->has('status'))
        <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.8-11.2a1 1 0 10-1.6-1.2L9 9.8 7.8 8.6a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                wire:click="$set('activeTab', 'my_leaves')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-all duration-150 {{ $activeTab === 'my_leaves' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                {{ __('My Leave Requests') }}
            </button>

            <button
                wire:click="$set('activeTab', 'pending_reviews')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-all duration-150 {{ $activeTab === 'pending_reviews' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                {{ __('Pending Reviews') }}
            </button>

            @if ($canViewAllLeaves)
                <button
                    wire:click="$set('activeTab', 'all_leaves')"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-all duration-150 {{ $activeTab === 'all_leaves' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    {{ __('All Leave Requests') }}
                </button>
            @endif
        </nav>
    </div>

    <!-- Main List Table Container -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    @if ($activeTab !== 'my_leaves')
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Applicant') }}</th>
                    @endif
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Leave Type') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Period') }}</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Days') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Current Turn / Stage') }}</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse ($leaves as $leave)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all duration-150">
                        @if ($activeTab !== 'my_leaves')
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $leave->user->name ?? $leave->user->username }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($leave->user->type) }}</div>
                                    </div>
                                </div>
                            </td>
                        @endif
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm text-gray-900 dark:text-gray-150">{{ $leave->staffLeaveType->name }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-gray-150">
                                {{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}
                            </div>
                            @if ($leave->is_emergency)
                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 mt-0.5">
                                    {{ __('Emergency') }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leave->requested_days }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($leave->status === 'approved')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50">
                                    {{ __('Approved') }}
                                </span>
                            @elseif ($leave->status === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50">
                                    {{ __('Rejected') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50">
                                    {{ __('Pending') }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($leave->current_stage === 'pending_hod')
                                <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('HOD Review') }}</span>
                            @elseif ($leave->current_stage === 'pending_registrar')
                                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ __('Registrar Review') }}</span>
                            @elseif ($leave->current_stage === 'pending_principal')
                                <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ __('Principal Final Review') }}</span>
                            @elseif ($leave->current_stage === 'approved')
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ __('Fully Signed') }}</span>
                            @elseif ($leave->current_stage === 'rejected')
                                <span class="text-sm font-medium text-rose-600 dark:text-rose-400">{{ __('Rejected') }}</span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Unknown') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            @if ($activeTab === 'pending_reviews')
                                <button
                                    type="button"
                                    wire:click="openReviewModal({{ $leave->id }})"
                                    class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-900/50 transition-all duration-150"
                                >
                                    {{ __('Review') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No leave requests found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Pagination -->
        @if ($leaves->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>

    <!-- Request Leave Modal -->
    @if ($showCreateModal)
        <x-college.modal name="request-leave-modal" :title="__('Request Leave')" :show="true" maxWidth="lg" livewireSynced>
            <form wire:submit.prevent="submitLeaveRequest" class="space-y-4">
                <div>
                    <label for="staff_leave_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Leave Type') }}</label>
                    <select
                        id="staff_leave_type_id"
                        wire:model="staff_leave_type_id"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    >
                        <option value="">{{ __('Select Leave Type') }}</option>
                        @foreach ($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} (Max: {{ $type->max_leave_days }} days)</option>
                        @endforeach
                    </select>
                    @error('staff_leave_type_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Start Date') }}</label>
                        <input
                            type="date"
                            id="start_date"
                            wire:model="start_date"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        />
                        @error('start_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('End Date') }}</label>
                        <input
                            type="date"
                            id="end_date"
                            wire:model="end_date"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        />
                        @error('end_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex h-5 items-center">
                        <input
                            id="is_emergency"
                            type="checkbox"
                            wire:model="is_emergency"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:focus:ring-offset-gray-900"
                        />
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_emergency" class="font-medium text-gray-700 dark:text-gray-300">{{ __('Emergency Leave') }}</label>
                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Mark this request if it requires immediate emergency attention.') }}</p>
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reason') }}</label>
                    <textarea
                        id="reason"
                        wire:model="reason"
                        rows="3"
                        placeholder="{{ __('Details of your leave request...') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    ></textarea>
                    @error('reason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <x-slot:footer>
                    <div class="flex space-x-3 justify-end">
                        <button
                            type="button"
                            wire:click="$set('showCreateModal', false)"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

    <!-- Review Leave Modal -->
    @if ($showReviewModal)
        <x-college.modal name="review-leave-modal" :title="__('Review Leave Request')" :show="true" maxWidth="lg" livewireSynced>
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Please review the details below. You can approve this step or specify a reason and reject the entire request.') }}
                </p>

                <div>
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Remarks / Rejection Reason') }}</label>
                    <textarea
                        id="rejection_reason"
                        wire:model="rejection_reason"
                        rows="3"
                        placeholder="{{ __('Required only if rejecting...') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    ></textarea>
                    @error('rejection_reason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <x-slot:footer>
                    <div class="flex space-x-3 justify-end">
                        <button
                            type="button"
                            wire:click="$set('showReviewModal', false)"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            wire:click="rejectRequest"
                            class="inline-flex justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                        >
                            {{ __('Reject Request') }}
                        </button>
                        <button
                            type="button"
                            wire:click="approveRequest"
                            class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ __('Approve Step') }}
                        </button>
                    </div>
                </x-slot:footer>
            </div>
        </x-college.modal>
    @endif
</div>
