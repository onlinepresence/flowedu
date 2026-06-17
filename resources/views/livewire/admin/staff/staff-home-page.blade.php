<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <button
            type="button"
            wire:click="openAddStaffModal"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
            {{ __('Add staff') }}
        </button>
    </x-slot>

    @if ($showAddStaffModal)
        <x-college.modal name="staff-add-choice" :title="__('Add staff')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Choose the type of account to create.') }}</p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
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
                    {{ __('Teacher') }}
                </a>
                <a
                    href="{{ route('admin.staff.non-teaching', ['create' => 1]) }}"
                    wire:navigate
                    wire:click="closeAddStaffModal"
                    class="inline-flex justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
                >
                    {{ __('Non-teaching staff') }}
                </a>
            </div>
            <x-slot:footer>
                <button
                    type="button"
                    wire:click="closeAddStaffModal"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.staff.administrators') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Administrators') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $adminCount }}</span>
        </a>
        <a href="{{ route('admin.staff.teachers') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Teachers') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $teacherCount }}</span>
        </a>
        <a href="{{ route('admin.staff.non-teaching') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Non-teaching staff') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $nonTeachingCount }}</span>
        </a>
        <a href="{{ route('admin.staff.assignments') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Staff office assignments') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $staffAssignmentCount }}</span>
        </a>
        <a href="{{ route('admin.staff.teacher-assignments') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Teacher assignments') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $teacherAssignmentCount }}</span>
        </a>
        <a href="{{ route('admin.staff.teacher-roles') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Teacher roles') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $teacherRoleCount }}</span>
        </a>
        <a href="{{ route('admin.staff.roles') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Roles & Permissions') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $userRoleCount }}</span>
        </a>
        <a href="{{ route('admin.staff.materials') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Course Materials Review') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $materialCount }}</span>
        </a>
        <a href="{{ route('admin.staff.announcements') }}" wire:navigate class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Announcements') }}</span>
            <span class="mt-1 block text-2xl font-semibold text-indigo-600 dark:text-indigo-400">{{ $announcementCount }}</span>
        </a>
    </div>
</div>
