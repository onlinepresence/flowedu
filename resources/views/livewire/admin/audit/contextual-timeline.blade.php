<div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-gray-150 dark:border-gray-700 pb-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
            <i class="fa-solid fa-clock-rotate-left mr-1.5 text-purple-600"></i>
            {{ __('Activity History') }}
        </h3>
        @if ($totalCount > 5)
            <button type="button" wire:click="toggleShowAll" class="text-xs font-semibold text-purple-600 hover:text-purple-500 dark:text-purple-400">
                @if ($showAll)
                    {{ __('Show Recent Only') }}
                @else
                    {{ __('Show All') }} (+{{ $totalCount - 5 }})
                @endif
            </button>
        @endif
    </div>

    @if ($logs->isEmpty())
        <p class="text-xs text-gray-500 dark:text-gray-400 italic text-center py-4">
            {{ __('No activity logs found for this item.') }}
        </p>
    @else
        <div class="flow-root">
            <ul class="-mb-8">
                @foreach ($logs as $index => $log)
                    <li>
                        <div class="relative pb-8">
                            @if ($index < count($logs) - 1)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    @php
                                        $iconClass = 'fa-solid fa-circle-info';
                                        $bgColor = 'bg-gray-50 text-gray-600 dark:bg-gray-950/40 dark:text-gray-400';
                                        
                                        if (str_contains($log->action, 'created')) {
                                            $iconClass = 'fa-solid fa-plus';
                                            $bgColor = 'bg-green-50 text-green-600 dark:bg-green-950/40 dark:text-green-400';
                                        } elseif (str_contains($log->action, 'paid') || str_contains($log->action, 'approved')) {
                                            $iconClass = 'fa-solid fa-check';
                                            $bgColor = 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400';
                                        } elseif (str_contains($log->action, 'rejected') || str_contains($log->action, 'voided')) {
                                            $iconClass = 'fa-solid fa-xmark';
                                            $bgColor = 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400';
                                        } elseif (str_contains($log->action, 'updated')) {
                                            $iconClass = 'fa-solid fa-pen';
                                            $bgColor = 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400';
                                        }
                                    @endphp
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $bgColor }} ring-8 ring-white dark:ring-gray-800">
                                        <i class="{{ $iconClass }} text-xs"></i>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0 pt-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white">
                                            {{ $log->description }}
                                        </p>
                                        <span class="text-[10px] font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $log->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('By') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $log->user ? $log->user->name : __('System') }}</span>
                                        @if ($log->ip_address)
                                            <span class="mx-1">&bull;</span> {{ $log->ip_address }}
                                        @endif
                                    </p>
                                    @if ($log->is_flagged)
                                        <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-0.5 text-[9px] font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 mt-1">
                                            <i class="fa-solid fa-triangle-exclamation text-[8px]"></i>
                                            {{ __('Flagged Action') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
