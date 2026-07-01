<div class="space-y-6">
    <!-- Explanation Box & Info Banner -->
    <div class="bg-indigo-50/50 border border-indigo-100 dark:bg-indigo-950/10 dark:border-indigo-900/40 rounded-xl p-4 flex gap-3 text-sm">
        <i class="fa-solid fa-circle-info text-indigo-600 dark:text-indigo-400 mt-0.5 text-base shrink-0"></i>
        <div class="space-y-1">
            <h4 class="font-semibold text-indigo-900 dark:text-indigo-300">{{ __('How Flagged Logs Work') }}</h4>
            <p class="text-gray-600 dark:text-gray-300 text-xs leading-relaxed">
                {{ __('Security audits and administrative actions are logged automatically. Administrators can review these entries and click') }} <span class="font-semibold text-indigo-700 dark:text-indigo-400">"{{ __('Flag') }}"</span> {{ __('to highlight suspicious behaviour or issues requiring immediate investigation. Flagged items appear in the') }} <span class="font-semibold text-indigo-700 dark:text-indigo-400">"{{ __('Attention Required') }}"</span> {{ __('tab, serving as a dedicated follow-up queue. Unflagging moves them back to the general audit pool.') }}
            </p>
        </div>
    </div>

    <!-- Filters and Tabs Bar -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                type="button"
                wire:click="$set('activeTab', 'all')"
                class="border-b-2 py-4 px-1 text-sm font-semibold transition duration-150 whitespace-nowrap {{ $activeTab === 'all' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                {{ __('All Logs') }}
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'flagged')"
                class="border-b-2 py-4 px-1 text-sm font-semibold transition duration-150 whitespace-nowrap flex items-center gap-1.5 {{ $activeTab === 'flagged' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <i class="fa-solid fa-triangle-exclamation"></i>
                {{ __('Attention Required') }}
                @php
                    $flaggedCount = \App\Models\SystemAudit::query()->where('is_flagged', true)->count();
                @endphp
                @if ($flaggedCount > 0)
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400">
                        {{ $flaggedCount }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Search Filters Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 bg-white dark:bg-gray-850 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <!-- User Search -->
        <div>
            <label for="searchUser" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1.5">{{ __('User Performer') }}</label>
            <input
                id="searchUser"
                type="text"
                wire:model.live.debounce.300ms="searchUser"
                placeholder="{{ __('Search user or username...') }}"
                class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
            />
        </div>

        <!-- Action Search -->
        <div>
            <label for="selectedAction" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1.5">{{ __('Action / Operation') }}</label>
            <select
                id="selectedAction"
                wire:model.live="selectedAction"
                class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
            >
                <option value="">{{ __('All Actions') }}</option>
                @foreach ($availableActions as $action)
                    <option value="{{ $action }}">{{ ucwords(str_replace(['_', '.'], ' ', $action)) }}</option>
                @endforeach
            </select>
        </div>

        <!-- Start Date -->
        <div>
            <label for="startDate" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1.5">{{ __('From Date') }}</label>
            <input
                id="startDate"
                type="date"
                wire:model.live="startDate"
                class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
            />
        </div>

        <!-- End Date -->
        <div>
            <label for="endDate" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1.5">{{ __('To Date') }}</label>
            <input
                id="endDate"
                type="date"
                wire:model.live="endDate"
                class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
            />
        </div>
    </div>

    <!-- Logs Table Container -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Performer') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Action') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Description') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Occurred') }}</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all duration-150 {{ $log->is_flagged ? 'bg-rose-50/20 dark:bg-rose-950/10' : '' }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $log->user->name ?? __('System') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->user?->email ?? $log->user?->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @php
                                $badgeColor = 'bg-gray-100 text-gray-800 border-gray-250 dark:bg-gray-900/40 dark:text-gray-300 dark:border-gray-800';
                                if (str_contains($log->action, 'approved') || str_contains($log->action, 'sign') || str_contains($log->action, 'dispatch')) {
                                    $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50';
                                } elseif (str_contains($log->action, 'rejected') || str_contains($log->action, 'delete') || $log->is_flagged) {
                                    $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50';
                                } elseif (str_contains($log->action, 'created') || str_contains($log->action, 'update') || str_contains($log->action, 'save')) {
                                    $badgeColor = 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50';
                                }
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $badgeColor }} border">
                                {{ $log->action_display_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ $log->description }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex flex-col text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : '' }}</span>
                                <span class="text-[10px] text-gray-450 dark:text-gray-500 font-mono mt-0.5">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                            <a
                                href="{{ route('admin.audit-logs.show', $log->uuid) }}"
                                class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/50 transition-all duration-150"
                            >
                                <i class="fa-solid fa-eye mr-1"></i>
                                {{ __('Details') }}
                            </a>
                            <button
                                type="button"
                                wire:click="toggleFlag({{ $log->id }})"
                                class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold transition-all duration-150 border {{ $log->is_flagged ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50' : 'bg-gray-50 text-gray-700 border-gray-250 dark:bg-gray-800 dark:text-gray-350 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                            >
                                <i class="fa-solid fa-flag mr-1"></i>
                                {{ $log->is_flagged ? __('Unflag') : __('Flag') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No audit logs found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($logs && method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
