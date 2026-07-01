<!-- If there are before/after arrays -->
@if (is_array($log->metadata) && isset($log->metadata['before']) && isset($log->metadata['after']))
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('Property') }}</th>
                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('Before Change') }}</th>
                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('After Change') }}</th>
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
                    <tr class="{{ $isChanged ? 'bg-amber-50/15 dark:bg-amber-950/5' : '' }} transition-colors">
                        <td class="px-4 py-2.5 font-semibold text-gray-950 dark:text-white whitespace-nowrap">
                            {{ \App\Models\SystemAudit::formatMetadataKey($key) }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-650 dark:text-gray-300 font-mono break-all max-w-[200px]">
                            {{ \App\Models\SystemAudit::formatMetadataValue($key, $beforeVal) }}
                        </td>
                        <td class="px-4 py-2.5 font-mono break-all max-w-[200px] {{ $isChanged ? 'text-amber-700 dark:text-amber-400 font-bold' : 'text-gray-650 dark:text-gray-350' }}">
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
            <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('Property') }}</th>
                    <th scope="col" class="px-4 py-2.5 text-left">{{ __('Value') }}</th>
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
