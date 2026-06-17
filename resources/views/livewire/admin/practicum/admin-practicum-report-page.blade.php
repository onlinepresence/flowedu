<x-slot name="headerActions">
    <div x-data>
        <button type="button" x-on:click="$dispatch('export-practicum-report')" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            {{ __('Export Report') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:export-practicum-report.window="$wire.exportReport()"
>

    <!-- KPI Aggregates -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card: Total Trainees -->
        <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Total Trainees') }}</span>
                <span class="rounded-lg bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A9.342 9.342 0 0 1 12.242 18c-2.168 0-4.17-.738-5.77-1.98M12 15.75A6 6 0 0 0 6 9.75v.15a6 6 0 0 0 6 6Z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $totalTrainees }}</span>
            </div>
        </div>

        <!-- Card: Evaluated -->
        <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Completed') }}</span>
                <span class="rounded-lg bg-green-50 p-2 text-green-600 dark:bg-green-950/50 dark:text-green-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $evaluatedCount }}</span>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                    ({{ $totalTrainees > 0 ? number_format(($evaluatedCount / $totalTrainees) * 100, 1) : 0 }}%)
                </span>
            </div>
        </div>

        <!-- Card: Pending -->
        <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Pending') }}</span>
                <span class="rounded-lg bg-yellow-50 p-2 text-yellow-600 dark:bg-yellow-950/50 dark:text-yellow-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $pendingCount }}</span>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                    ({{ $totalTrainees > 0 ? number_format(($pendingCount / $totalTrainees) * 100, 1) : 0 }}%)
                </span>
            </div>
        </div>

        <!-- Card: Avg Score -->
        <div class="relative overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Average Rating') }}</span>
                <span class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $averageScore !== null ? number_format((float)$averageScore, 2) . '%' : __('N/A') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-2/3">
            <div class="w-full sm:w-1/3">
                <label for="session-select" class="sr-only">{{ __('Academic Session') }}</label>
                <select id="session-select" wire:model.live="academicSessionId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }} {{ $session->is_current ? __('(Current)') : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-1/3">
                <label for="status-filter" class="sr-only">{{ __('Status Filter') }}</label>
                <select id="status-filter" wire:model.live="statusFilter" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="assigned">{{ __('Assigned') }}</option>
                    <option value="evaluated">{{ __('Evaluated') }}</option>
                </select>
            </div>
        </div>
        <div class="w-full sm:w-1/3">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search records...') }}" class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max table-auto text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900/50 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        <th class="px-6 py-4">{{ __('Trainee') }}</th>
                        <th class="px-6 py-4">{{ __('Supervisor') }}</th>
                        <th class="px-6 py-4">{{ __('Partnership School') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Rating') }}</th>
                        <th class="px-6 py-4">{{ __('Evaluation Comments & Feedback') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-900 dark:text-gray-200">
                    @forelse($supervisions as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors align-top">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $s->student->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->student->index_number }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $s->teacher->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->teacher->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                {{ $s->partnership_school }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($s->score !== null)
                                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-400">
                                        {{ number_format((float)$s->score, 2) }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400">
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 max-w-sm truncate whitespace-normal">
                                @if($s->evaluation_notes)
                                    <span class="text-gray-600 dark:text-gray-400 text-xs line-clamp-2" title="{{ $s->evaluation_notes }}">
                                        {{ $s->evaluation_notes }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No feedback submitted yet.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <x-college.empty-state
                                    title="{{ __('No evaluations recorded') }}"
                                    description="{{ __('There are no evaluation records matching the selected academic session and filters.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($supervisions->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $supervisions->links() }}
            </div>
        @endif
    </div>

</div>
