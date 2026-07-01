<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.audit-logs') }}" class="inline-flex items-center text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition duration-150">
            <i class="fa-solid fa-arrow-left mr-1.5"></i>
            {{ __('Back to Audit Logs') }}
        </a>
    </div>

    <!-- Main Grid Layout: LHS and RHS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LHS: Focus, Details, Changes & Targets (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Main Overview Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-150 dark:border-gray-700 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                            </span>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $log->action_display_name }}
                            </h3>
                        </div>
                        <p class="text-xxs text-gray-450 dark:text-gray-500 font-mono">
                            UUID: {{ $log->uuid }}
                        </p>
                    </div>

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
                    <span class="inline-flex items-center self-start sm:self-center rounded-md px-3 py-1 text-sm font-semibold {{ $badgeColor }} border">
                        {{ $log->action_display_name }}
                    </span>
                </div>

                <div class="space-y-2">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        {{ __('Description / Activity Detail') }}
                    </h4>
                    <p class="text-base text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                        {{ $log->description }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="button"
                        wire:click="toggleFlag"
                        class="inline-flex items-center rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all duration-150 border {{ $log->is_flagged ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50' : 'bg-gray-50 text-gray-700 border-gray-250 dark:bg-gray-800 dark:text-gray-350 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <i class="fa-solid fa-flag mr-1.5"></i>
                        {{ $log->is_flagged ? __('Unflag Action') : __('Flag Action for Attention') }}
                    </button>

                    @if ($log->is_flagged)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            {{ __('Attention Required') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Changed Data & Metadata Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-database text-purple-600"></i>
                    {{ __('Captured Data & Structural Changes') }}
                </h3>

                <!-- If there are before/after arrays -->
                @if (is_array($log->metadata) && isset($log->metadata['before']) && isset($log->metadata['after']))
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Property') }}</th>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Before Change') }}</th>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('After Change') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs">
                                @php
                                    $allKeys = array_unique(array_merge(array_keys($log->metadata['before']), array_keys($log->metadata['after'])));
                                @endphp
                                @foreach($allKeys as $key)
                                    @php
                                        $beforeVal = $log->metadata['before'][$key] ?? null;
                                        $afterVal = $log->metadata['after'][$key] ?? null;
                                        $isChanged = $beforeVal !== $afterVal;
                                    @endphp
                                    <tr class="{{ $isChanged ? 'bg-amber-50/20 dark:bg-amber-950/10' : '' }} transition-colors">
                                        <td class="px-4 py-2.5 font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                                            {{ \App\Models\SystemAudit::formatMetadataKey($key) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 font-mono break-all max-w-[200px]">
                                            {{ \App\Models\SystemAudit::formatMetadataValue($key, $beforeVal) }}
                                        </td>
                                        <td class="px-4 py-2.5 font-mono break-all max-w-[200px] {{ $isChanged ? 'text-amber-700 dark:text-amber-400 font-bold' : 'text-gray-650 dark:text-gray-300' }}">
                                            {{ \App\Models\SystemAudit::formatMetadataValue($key, $afterVal) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif (is_array($log->metadata) && count($log->metadata) > 0)
                    <!-- Flat metadata key-value grid -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Property') }}</th>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Value') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs">
                                @foreach($log->metadata as $key => $val)
                                    @if ($key !== 'before' && $key !== 'after')
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                                                {{ \App\Models\SystemAudit::formatMetadataKey($key) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-650 dark:text-gray-300 font-mono break-all">
                                                {{ \App\Models\SystemAudit::formatMetadataValue($key, $val) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-6 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                            {{ __('No metadata captured for this operation.') }}
                        </p>
                    </div>
                @endif
            </div>

            <!-- Affected Auditable Entity Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-cube text-purple-600"></i>
                    {{ __('Affected Entity / Audit Target') }}
                </h3>

                @if ($log->auditable_type)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm bg-gray-50/50 dark:bg-gray-900/30 p-4 rounded-lg border border-gray-150 dark:border-gray-700/60">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1">
                                {{ __('Target Model Class') }}
                            </span>
                            <span class="font-mono text-gray-900 dark:text-white break-all">
                                {{ $log->auditable_type }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1">
                                {{ __('Target Database ID') }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200/50">
                                #{{ $log->auditable_id }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-6 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-500 dark:text-gray-400 italic">
                        {{ __('No specific target model was linked to this system action.') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- RHS: Performer & Client context (1/3 width) -->
        <div class="space-y-6">
            <!-- Performer Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-150 dark:border-gray-700 pb-3">
                    <i class="fa-solid fa-user-shield text-purple-600"></i>
                    {{ __('Performer Profile') }}
                </h3>

                @if ($log->user)
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-bold text-lg shadow-sm">
                            {{ strtoupper(substr($log->user->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $log->user->name }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                @ {{ $log->user->username }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-150 dark:border-gray-700 pt-3 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('Email') }}:</span>
                            <span class="text-gray-900 dark:text-white font-mono">{{ $log->user->email }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('System User Type') }}:</span>
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-750 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/50">
                                {{ ucfirst($log->user->type) }}
                            </span>
                        </div>
                        @if ($log->user->type === 'admin')
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400 font-semibold">{{ __('Admin Role') }}:</span>
                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 font-semibold text-purple-750 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200/50">
                                    {{ ucfirst($log->user->adminRoleSlug() ?? 'staff') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-400 font-bold text-lg">
                            SYS
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('System Action') }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Automated / Crontab') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Client & Security context Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-150 dark:border-gray-700 pb-3">
                    <i class="fa-solid fa-shield-halved text-purple-600"></i>
                    {{ __('Client Context & Timing') }}
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 font-semibold mb-1">
                            {{ __('IP Address') }}
                        </span>
                        <span class="inline-flex items-center font-mono bg-gray-100 dark:bg-gray-900 px-2.5 py-1 rounded text-gray-900 dark:text-white border border-gray-200/40 dark:border-gray-700/60 font-bold">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 font-semibold mb-1">
                            {{ __('Occurred Date & Time') }}
                        </span>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-gray-900 dark:text-white font-semibold">
                                {{ $log->created_at ? $log->created_at->format('M d, Y - h:i:s A') : '' }}
                            </span>
                            <span class="text-xxs text-gray-450 dark:text-gray-500 italic">
                                {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 font-semibold mb-1">
                            {{ __('User Agent') }}
                        </span>
                        <div class="bg-gray-50 dark:bg-gray-900/60 p-2.5 rounded border border-gray-150 dark:border-gray-700 font-mono text-[10px] text-gray-600 dark:text-gray-400 leading-normal break-words max-h-32 overflow-y-auto">
                            {{ $log->user_agent ?? __('No user agent registered.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
