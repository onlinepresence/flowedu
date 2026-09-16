<div class="mx-auto max-w-7xl space-y-6" x-data x-on:open-add-staff-modal.window="$wire.openAddStaffModal()">
    <x-slot name="headerActions">
        <div x-data>
            <button
                type="button"
                x-on:click="$dispatch('open-add-staff-modal')"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <i class="fa-solid fa-plus me-2"></i>
                {{ __('Add Staff') }}
            </button>
        </div>
    </x-slot>

    @if ($showAddStaffModal)
        <x-college.modal name="staff-add-choice" :title="__('Add staff')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Choose the type of account to create.') }}</p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <a
                    href="{{ route('admin.staff.administrators', ['create' => 1]) }}"
                    wire:navigate
                    wire:click="closeAddStaffModal"
                    class="inline-flex justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
                >
                    {{ __('Administrator') }}
                </a>
                <a
                    href="{{ route('admin.staff.teachers', ['create' => 1]) }}"
                    wire:navigate
                    wire:click="closeAddStaffModal"
                    class="inline-flex justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
                >
                    {{ __('Lecturer') }}
                </a>
            </div>
            <x-slot:footer>
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'staff-add-choice')"
                    wire:click="closeAddStaffModal"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Count Summary Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.staff.administrators') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Administrators') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-650 dark:text-indigo-400">{{ $adminCount }}</span>
        </a>
        <a href="{{ route('admin.staff.teachers') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Lecturers') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-650 dark:text-indigo-400">{{ $teacherCount }}</span>
        </a>
        <a href="{{ route('admin.staff.teacher-assignments') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Lecturer Assignments') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-650 dark:text-indigo-400">{{ $teacherAssignmentCount }}</span>
        </a>
        <a href="{{ route('admin.staff.materials') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Course Materials') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-650 dark:text-indigo-400">{{ $materialCount }}</span>
        </a>
    </div>

    <!-- Filters Section -->
    <x-college.filter-card cols="4">
        <div>
            <x-input-label for="search" :value="__('Search')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name, email, staff number...') }}" wire:model.live.debounce.300ms="search" />
        </div>
        <div>
            <x-input-label for="filterType" :value="__('Staff Type')" />
            <select id="filterType" wire:model.live="filterType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Staff Types') }}</option>
                <option value="admin">{{ __('Administrative Staff') }}</option>
                <option value="teacher">{{ __('Teaching Staff (Lecturer)') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="filterDepartment" :value="__('Department')" />
            <select id="filterDepartment" wire:model.live="filterDepartment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterStatus" :value="__('Status')" />
            <select id="filterStatus" wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="all">{{ __('All Statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Directory Table Section -->
    <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{-- Targeted Loading Overlay --}}
        <div wire:loading.delay wire:target="search, filterType, filterDepartment, filterStatus, previousPage, nextPage, gotoPage"
             class="absolute inset-0 z-10 flex items-center justify-center bg-white/40 backdrop-blur-[1px] transition-opacity duration-200 dark:bg-gray-900/40">
            <div class="flex items-center gap-2 rounded-lg border border-gray-100 bg-white/80 px-4 py-2 shadow-lg dark:border-gray-700 dark:bg-gray-800/80">
                <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Staff Member') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Staff ID') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Type / Role') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($staff as $member)
                        @php
                            $profilePic = $member->type === 'admin' ? $member->admin?->profile_pic : $member->teacher?->profile_pic;
                            $profileUrl = $profilePic ? asset('storage/' . $profilePic) : null;
                            $dept = $member->type === 'admin' ? $member->admin?->department : $member->teacher?->department;
                            $phone = $member->type === 'admin' ? $member->admin?->phone_number : $member->teacher?->phone_number;
                        @endphp
                        <tr wire:key="staff-{{ $member->id }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-college.avatar :src="$profileUrl" :name="$member->name" size="sm" />
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $member->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $member->email }}
                                        </div>
                                        @if ($phone)
                                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ $phone }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">
                                {{ $member->username }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                @if ($member->type === 'teacher')
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-700/30">
                                        {{ __('Lecturer') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-700/30">
                                        {{ $member->admin?->role?->display_name ?? __('Administrator') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $dept?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                @if ($member->active)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950/40 dark:text-green-300">
                                        <span class="me-1 h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950/40 dark:text-red-300">
                                        <span class="me-1 h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-end text-sm font-medium">
                                @if ($member->type === 'teacher')
                                    <a
                                        href="{{ route('admin.staff.teachers', ['search' => $member->username]) }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        <i class="fa-solid fa-user-pen"></i>
                                        {{ __('Edit Lecturer') }}
                                    </a>
                                @else
                                    <a
                                        href="{{ route('admin.staff.administrators', ['search' => $member->username]) }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        <i class="fa-solid fa-user-pen"></i>
                                        {{ __('Edit Admin') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="fa-solid fa-users-slash text-4xl text-gray-300 dark:text-gray-600"></i>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('No staff members found matching your filters.') }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($staff->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $staff->links() }}
            </div>
        @endif
    </div>
</div>
