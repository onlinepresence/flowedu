<div class="mx-auto max-w-7xl space-y-6">
    @if ($capNotice)
        <div @class([
            'rounded-lg border p-4 text-sm',
            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100' => ! $capNoticeIsBlock,
            'border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100' => $capNoticeIsBlock,
        ]) role="alert">
            {{ $capNotice }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-college.stats-card
            :title="__('Pending approval')"
            :value="$pendingCount"
            color="amber"
            icon="fa-solid fa-clock-rotate-left"
            :href="route('admin.students.index', ['approval' => 'pending'])"
        />
        <x-college.stats-card
            :title="__('Approved students')"
            :value="$approvedCount"
            color="green"
            icon="fa-solid fa-user-check"
            :href="route('admin.students.index', ['approval' => 'approved'])"
        />
        <x-college.stats-card
            :title="__('Licence — active students')"
            color="purple"
            icon="fa-solid fa-id-card-clip"
            class="sm:col-span-2 lg:col-span-2"
        >
            {{ $activeForCap }}
            @if ($maxStudents !== null)
                <span class="text-base font-normal text-gray-500 dark:text-gray-400">/ {{ $maxStudents }} {{ __('max') }}</span>
            @endif
            @if ($maxStudents === null)
                <span class="block text-xs font-normal text-gray-400 dark:text-gray-500 mt-1">{{ __('No student cap set.') }}</span>
            @endif
        </x-college.stats-card>
    </div>

    <x-college.quick-links :title="__('Quick links')">
        <a href="{{ route('admin.students.index') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-graduation-cap text-purple-500 dark:text-purple-400"></i>
            {{ __('Students') }}
        </a>
        <a href="{{ route('admin.grading.enter') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-pen-to-square text-purple-500 dark:text-purple-400"></i>
            {{ __('Enter grades') }}
        </a>
        @if ($canFinance)
            <a href="{{ route('admin.finance.fees') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-wallet text-purple-500 dark:text-purple-400"></i>
                {{ __('Fees') }}
            </a>
        @endif
        <a href="{{ route('admin.settings.school') }}" wire:navigate class="dashboard-quick-link">
            <i class="fa-solid fa-gears text-purple-500 dark:text-purple-400"></i>
            {{ __('Settings') }}
        </a>
    </x-college.quick-links>

    <div>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent pending') }}</h2>
            @if ($pendingPreview->isNotEmpty())
                <a href="{{ route('admin.students.index', ['approval' => 'pending']) }}" wire:navigate class="text-sm font-medium text-purple-600 hover:text-purple-500 dark:text-purple-400">{{ __('View all pending') }}</a>
            @endif
        </div>
        @if ($pendingPreview->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($pendingPreview as $row)
                        <li class="flex items-center justify-between gap-4 px-4 py-3" wire:key="pend-{{ $row->id }}">
                            <div>
                                <p class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $row->index_number }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ trim(implode(' ', array_filter([$row->firstname, $row->othernames, $row->lastname]))) }}</p>
                            </div>
                            <a href="{{ route('admin.students.index', ['approval' => 'pending']) }}" wire:navigate class="shrink-0 text-sm text-purple-600 hover:text-purple-500 dark:text-purple-400 font-semibold">{{ __('Open list') }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <x-college.empty-state
                :title="__('No pending students')"
                :description="__('There are currently no new student registrations requiring admin review or approval.')"
            >
                <x-slot name="icon">
                    <i class="fa-solid fa-user-clock text-4xl text-gray-400"></i>
                </x-slot>
            </x-college.empty-state>
        @endif
    </div>
</div>
