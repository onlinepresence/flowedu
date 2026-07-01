<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:open-create-leave-modal.window="$wire.openCreateModal()"
>
    <!-- Header Actions -->
    <x-slot name="headerActions">
        <button
            type="button"
            x-on:click="$dispatch('open-create-leave-modal')"
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
        <nav class="-mb-px flex flex-wrap gap-x-8 gap-y-2" aria-label="Tabs">
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

            @if ($canManageConfigs)
                <button
                    wire:click="$set('activeTab', 'leave_configurations')"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-all duration-150 {{ $activeTab === 'leave_configurations' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    {{ __('Leave Configurations') }}
                </button>
            @endif

            @if ($canManageStaffAssignments)
                <button
                    wire:click="$set('activeTab', 'staff_assignments')"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-all duration-150 {{ $activeTab === 'staff_assignments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    {{ __('Staff Assignments') }}
                </button>
            @endif
        </nav>
    </div>

    <!-- My Leaves Entitlement Cards -->
    @if ($activeTab === 'my_leaves')
        @php
            $entitlements = $this->getEntitlements();
        @endphp
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
            <!-- Total Entitlement -->
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <dt class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Entitlement') }}</dt>
                <dd class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $entitlements['total'] }} {{ __('Days') }}</dd>
            </div>
            <!-- Used / Approved -->
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <dt class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Approved / Used') }}</dt>
                <dd class="mt-1 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $entitlements['approved'] }} {{ __('Days') }}</dd>
            </div>
            <!-- Pending -->
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <dt class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Pending Approval') }}</dt>
                <dd class="mt-1 text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">{{ $entitlements['pending'] }} {{ __('Days') }}</dd>
            </div>
            <!-- Remaining -->
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <dt class="truncate text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Remaining Days') }}</dt>
                <dd class="mt-1 text-2xl font-bold tracking-tight text-indigo-650 dark:text-indigo-400">{{ $entitlements['remaining'] }} {{ __('Days') }}</dd>
            </div>
        </div>
    @endif

    <!-- Main List Table Container -->
    @if (in_array($activeTab, ['my_leaves', 'pending_reviews', 'all_leaves'], true))
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
            @if ($leaves && method_exists($leaves, 'hasPages') && $leaves->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Configurations Tab View -->
    @if ($activeTab === 'leave_configurations')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Global submission window settings -->
            <div class="md:col-span-1 bg-white shadow rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Submission Window Settings') }}</h3>
                <form wire:submit.prevent="saveConfigurations" class="space-y-4">
                    <div>
                        <label for="submission_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Window Start Date') }}</label>
                        <input
                            type="date"
                            id="submission_start_date"
                            wire:model="submission_start_date"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        />
                        @error('submission_start_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="submission_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Window End Date') }}</label>
                        <input
                            type="date"
                            id="submission_end_date"
                            wire:model="submission_end_date"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        />
                        @error('submission_end_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-start">
                        <div class="flex h-5 items-center">
                            <input
                                id="emergency_leave_enabled"
                                type="checkbox"
                                wire:model="emergency_leave_enabled"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:focus:ring-offset-gray-900"
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="emergency_leave_enabled" class="font-medium text-gray-700 dark:text-gray-300">{{ __('Enable Emergency Bypass') }}</label>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ __('Emergency requests bypass the submission window constraint.') }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ __('Save Configurations') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Leave Types CRUD -->
            <div class="md:col-span-2 bg-white shadow rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Staff Leave Types') }}</h3>
                    <button
                        type="button"
                        wire:click="openTypeModal"
                        class="inline-flex items-center rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-900/50"
                    >
                        <i class="fa-solid fa-plus mr-1"></i>
                        {{ __('Add Leave Type') }}
                    </button>
                </div>

                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Max Days') }}</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">{{ __('Actions') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-850">
                            @foreach ($leaveTypes as $type)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all duration-150">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $type->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-300">
                                        {{ $type->max_leave_days }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <button
                                            type="button"
                                            wire:click="openTypeModal({{ $type->id }})"
                                            class="text-indigo-650 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ __('Edit') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteLeaveType({{ $type->id }})"
                                            class="text-rose-650 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('type_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>
    @endif

    <!-- Staff Assignments Tab View -->
    @if ($activeTab === 'staff_assignments')
        <div class="bg-white shadow rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Assign Staff Leave Types') }}</h3>
            
            <div class="grid gap-4 sm:grid-cols-4 items-end bg-gray-50 p-4 rounded-lg dark:bg-gray-900/50">
                <div class="sm:col-span-2">
                    <label for="searchStaff" class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">{{ __('Search Staff') }}</label>
                    <input
                        type="text"
                        id="searchStaff"
                        wire:model.live.debounce.300ms="searchStaff"
                        placeholder="{{ __('Search by name, email, username...') }}"
                        class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    />
                </div>
                <div>
                    <label for="filterStaffType" class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">{{ __('Staff Type') }}</label>
                    <select
                        id="filterStaffType"
                        wire:model.live="filterStaffType"
                        class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    >
                        <option value="all">{{ __('All Staff') }}</option>
                        <option value="teaching">{{ __('Teaching Staff') }}</option>
                        <option value="non_teaching">{{ __('Non-Teaching Staff') }}</option>
                    </select>
                </div>
                @if (auth()->user()->adminRoleSlug() !== 'hod')
                    <div>
                        <label for="filterStaffDepartment" class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">{{ __('Department') }}</label>
                        <select
                            id="filterStaffDepartment"
                            wire:model.live="filterStaffDepartment"
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        >
                            <option value="all">{{ __('All Departments') }}</option>
                            <option value="none">{{ __('No Department') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Staff Member') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned Leave Type') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-850">
                        @forelse ($staffMembers as $staff)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all duration-150">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $staff->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $staff->email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($staff->type === 'teacher')
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-750 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-950/30 dark:text-purple-300">
                                            {{ __('Teacher') }}
                                        </span>
                                    @elseif ($staff->type === 'staff')
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-750 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-300">
                                            {{ __('Non-Teaching Staff') }}
                                        </span>
                                    @elseif ($staff->type === 'admin')
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-750 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-300">
                                            {{ __('Admin') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    @php
                                        $deptName = $staff->admin?->department?->name 
                                            ?? $staff->teacher?->department?->name 
                                            ?? $staff->nonTeachingStaff?->department?->name;
                                    @endphp
                                    {{ $deptName ?? __('No Department') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <select
                                        wire:change="assignLeaveType({{ $staff->id }}, $event.target.value)"
                                        class="rounded-lg border border-gray-300 bg-white py-1.5 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-xs"
                                    >
                                        <option value="">{{ __('No Assignment') }}</option>
                                        @foreach ($leaveTypes as $type)
                                            <option value="{{ $type->id }}" {{ $staff->staff_leave_type_id == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }} ({{ $type->max_leave_days }} days)
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No staff members found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($staffMembers && method_exists($staffMembers, 'hasPages') && $staffMembers->hasPages())
                <div class="pt-4">
                    {{ $staffMembers->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Add/Edit Leave Type Modal -->
    @if ($showTypeModal)
        <x-college.modal name="leave-type-modal" :title="$editing_type_id ? __('Edit Leave Type') : __('Add Leave Type')" :show="true" maxWidth="md" livewireSynced>
            <form wire:submit.prevent="saveLeaveType" class="space-y-4">
                <div>
                    <label for="type_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Leave Type Name') }}</label>
                    <input
                        type="text"
                        id="type_name"
                        wire:model="type_name"
                        placeholder="{{ __('e.g. Maternity Leave') }}"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    />
                    @error('type_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="type_max_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Maximum Days allowed') }}</label>
                    <input
                        type="number"
                        id="type_max_days"
                        wire:model="type_max_days"
                        min="1"
                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    />
                    @error('type_max_days') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <x-slot:footer>
                    <div class="flex space-x-3 justify-end">
                        <button
                            type="button"
                            wire:click="$set('showTypeModal', false)"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {{ __('Save') }}
                        </button>
                    </div>
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

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
        @php
            $stats = $this->getApplicantStats();
        @endphp
        <x-college.modal name="review-leave-modal" :title="__('Review Leave Request')" :show="true" maxWidth="2xl" livewireSynced>
            <div class="space-y-6">
                @if (!empty($stats))
                    <!-- Applicant Info -->
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ $stats['user']->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['user']->email }} &bull; {{ ucfirst($stats['user']->adminRoleSlug() ?: 'Staff') }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 font-mono">
                            {{ $stats['request']->staffLeaveType->name }}
                        </span>
                    </div>

                    <!-- Details & Statistics Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Request Details -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Request Details') }}</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 space-y-2 text-xs">
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400 block">{{ __('Requested Duration') }}</span>
                                    <span class="text-gray-950 dark:text-white font-semibold">
                                        {{ $stats['request']->start_date->format('M d, Y') }} - {{ $stats['request']->end_date->format('M d, Y') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400 block">{{ __('Total Days') }}</span>
                                    <span class="text-gray-950 dark:text-white font-semibold">
                                        {{ $stats['request']->requested_days }} {{ __('day(s)') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400 block">{{ __('Reason') }}</span>
                                    <p class="text-gray-700 dark:text-gray-300 italic mt-0.5">
                                        "{{ $stats['request']->reason }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Year Statistics -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Annual Stats (Current Session)') }}</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-100/40 dark:border-indigo-900/30 rounded-xl p-3 text-center">
                                    <span class="block text-[10px] uppercase font-bold text-indigo-500 dark:text-indigo-400">{{ __('Entitlement') }}</span>
                                    <span class="text-lg font-extrabold text-indigo-950 dark:text-white">{{ $stats['max_days'] }}</span>
                                </div>
                                <div class="bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-100/40 dark:border-emerald-900/30 rounded-xl p-3 text-center">
                                    <span class="block text-[10px] uppercase font-bold text-emerald-500 dark:text-emerald-400">{{ __('Approved') }}</span>
                                    <span class="text-lg font-extrabold text-emerald-950 dark:text-white">{{ $stats['approved_days'] }}</span>
                                </div>
                                <div class="bg-amber-50/40 dark:bg-amber-950/20 border border-amber-100/40 dark:border-amber-900/30 rounded-xl p-3 text-center">
                                    <span class="block text-[10px] uppercase font-bold text-amber-500 dark:text-amber-400">{{ __('Pending Others') }}</span>
                                    <span class="text-lg font-extrabold text-amber-950 dark:text-white">{{ $stats['pending_days'] }}</span>
                                </div>
                                <div class="bg-purple-50/40 dark:bg-purple-950/20 border border-purple-100/40 dark:border-purple-900/30 rounded-xl p-3 text-center">
                                    <span class="block text-[10px] uppercase font-bold text-purple-500 dark:text-purple-400">{{ __('Remaining') }}</span>
                                    <span class="text-lg font-extrabold text-purple-950 dark:text-white">{{ $stats['remaining_days'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leave History -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Leave History (excluding this request)') }}</h4>
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl max-h-32 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Period') }}</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Days') }}</th>
                                        <th class="px-4 py-2 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @forelse ($stats['history'] as $hist)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all duration-150">
                                            <td class="px-4 py-2 text-gray-950 dark:text-white font-medium">{{ $hist->staffLeaveType->name }}</td>
                                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $hist->start_date->format('M d') }} - {{ $hist->end_date->format('M d, Y') }}</td>
                                            <td class="px-4 py-2 text-gray-950 dark:text-white font-semibold">{{ $hist->requested_days }}</td>
                                            <td class="px-4 py-2">
                                                @php
                                                    $statusColors = [
                                                        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30',
                                                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30',
                                                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-2xs font-semibold {{ $statusColors[$hist->status] ?? 'bg-gray-50 text-gray-700' }}">
                                                    {{ ucfirst($hist->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500 italic">
                                                {{ __('No previous leave requests found.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Contextual Audit Timeline for Leave Request -->
                @if (auth()->user()?->hasAdminPermission('view_audit_logs') && isset($stats['request']) && $stats['request'])
                    <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2"><i class="fa-solid fa-clock-history mr-1.5 text-purple-550"></i>{{ __('Leave Audit History') }}</h4>
                        <livewire:admin.audit.contextual-timeline :model="$stats['request']" :key="'leave-timeline-' . $stats['request']->id" />
                    </div>
                @endif

                <!-- Remarks & Decision -->
                <div class="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <label for="rejection_reason" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Remarks / Rejection Reason') }}</label>
                    <textarea
                        id="rejection_reason"
                        wire:model="rejection_reason"
                        rows="3"
                        placeholder="{{ __('Specify remarks or the reason for rejection (required only if rejecting)...') }}"
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
