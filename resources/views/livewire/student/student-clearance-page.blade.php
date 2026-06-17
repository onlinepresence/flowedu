<div class="space-y-6">
    @if ($isLocked)
        <x-college.empty-state
            :title="__('Clearance not available')"
            :description="__('Clearance is only available for final-year students (Level :final). You are currently in Level :current.', ['final' => $finalYear, 'current' => $currentYearLabel])"
        >
            <x-slot:icon>
                <svg class="h-12 w-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </x-slot:icon>
        </x-college.empty-state>
    @else
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
            <div class="bg-gray-50/50 dark:bg-gray-900/30 px-6 py-5 border-b border-gray-150 dark:border-gray-700/50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </span>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                        {{ __('Clearance Checklist') }}
                    </h2>
                </div>
                @php
                    $badgeClass = match ($overallStatus) {
                        'cleared' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300 ring-emerald-600/10',
                        'partial' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300 ring-amber-600/10',
                        default => 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300 ring-red-600/10',
                    };
                @endphp
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                    @if ($overallStatus === 'cleared')
                        <i class="fa-solid fa-circle-check mr-1.5"></i>
                    @elseif ($overallStatus === 'partial')
                        <i class="fa-solid fa-hourglass-half mr-1.5 animate-pulse"></i>
                    @else
                        <i class="fa-solid fa-circle-xmark mr-1.5"></i>
                    @endif
                    {{ ucfirst($overallStatus) }}
                </span>
            </div>

            <div class="divide-y divide-gray-150 dark:divide-gray-750">
                @foreach (\App\Support\ClearanceDepartments::definitions() as $key => $label)
                    @php 
                        $row = $clearanceStatus[$key] ?? ['status' => 'pending', 'cleared_by' => null, 'cleared_at' => null];
                        $statusBadge = match ($row['status']) {
                            'cleared' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
                            'not_required' => 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            default => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400',
                        };
                    @endphp
                    <div class="flex flex-col gap-3 px-6 py-4.5 sm:flex-row sm:items-center sm:justify-between hover:bg-gray-50/20 dark:hover:bg-gray-850/10 transition">
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $label }}</p>
                            @if (! empty($row['cleared_by']))
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    <i class="fa-solid fa-user-check text-[10px] text-gray-400"></i>
                                    <span>
                                        {{ __('Cleared by') }}: <span class="text-gray-700 dark:text-gray-300 font-semibold">{{ $row['cleared_by'] }}</span>
                                        @if (! empty($row['cleared_at']))
                                            <span class="text-gray-400 dark:text-gray-500 ml-1">({{ \Carbon\Carbon::parse($row['cleared_at'])->format('M d, Y') }})</span>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                        <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold self-start sm:self-center capitalize {{ $statusBadge }}">
                            {{ str_replace('_', ' ', $row['status']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
