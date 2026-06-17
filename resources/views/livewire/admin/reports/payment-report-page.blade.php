<div
    class="mx-auto max-w-7xl space-y-6 print-container"
    x-data
    x-on:export-payments-csv.window="$wire.exportCSV()"
    x-on:export-payments-excel.window="$wire.exportExcel()"
>
    {{-- Header Actions --}}
    <x-slot name="headerActions">
        <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <i class="fa-solid fa-print"></i> {{ __('Print Report') }}
        </button>
        
        <div x-data="{ open: false }" class="relative inline-block text-left no-print">
            <button @click="open = !open" type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                <i class="fa-solid fa-download"></i> {{ __('Export') }} <i class="fa-solid fa-chevron-down text-xs"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700">
                <div class="py-1">
                    <button type="button" @click="$dispatch('export-payments-csv'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-csv text-green-500"></i> {{ __('Export CSV') }}
                    </button>
                    <button type="button" @click="$dispatch('export-payments-excel'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-excel text-blue-500"></i> {{ __('Export Excel') }}
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Printable Header (Hidden on Screen) --}}
    <div class="hidden print:block mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Apex Polytechnic</h1>
        <p class="text-gray-600 text-sm">Official Financial Revenue Report</p>
        <div class="mt-4 border-t border-b border-gray-200 py-2 text-xs text-gray-500 flex justify-between">
            <span>{{ __('Generated on:') }} {{ now()->toDayDateTimeString() }}</span>
            <span>{{ __('Session:') }} {{ $academicSessionId ? $sessions->firstWhere('id', $academicSessionId)?->name : __('All') }}</span>
        </div>
    </div>

    {{-- Stats Cards Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-college.stats-card 
            :title="__('All-Time Collections')" 
            :value="'GHS ' . number_format($allTimeTotal, 2)" 
            icon="fa-solid fa-piggy-bank" 
            color="purple" 
        />
        <x-college.stats-card 
            :title="__('Selected Period Total')" 
            :value="'GHS ' . number_format($selectedPeriodTotal, 2)" 
            icon="fa-solid fa-sack-dollar" 
            color="green" 
        />
        <x-college.stats-card 
            :title="__('Transactions Count')" 
            :value="number_format($transactionCount)" 
            icon="fa-solid fa-receipt" 
            color="blue" 
        />
        <x-college.stats-card 
            :title="__('Avg Transaction')" 
            :value="'GHS ' . number_format($avgTransaction, 2)" 
            icon="fa-solid fa-scale-balanced" 
            color="amber" 
        />
    </div>

    {{-- Filters Card --}}
    <div class="no-print">
        <x-college.filter-card cols="4">
            <div>
                <label for="academicSessionId" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Academic Session') }}</label>
                <select id="academicSessionId" wire:model.live="academicSessionId" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Sessions') }}</option>
                    @foreach ($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="paymentMethod" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Payment Method') }}</label>
                <select id="paymentMethod" wire:model.live="paymentMethod" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Methods') }}</option>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="startDate" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Start Date') }}</label>
                <input type="date" id="startDate" wire:model.live="startDate" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
                <label for="endDate" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('End Date') }}</label>
                <input type="date" id="endDate" wire:model.live="endDate" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
        </x-college.filter-card>
    </div>

    {{-- Main Visual Layout Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Tables Section (Occupies 2 columns on large screens) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Monthly Breakdowns --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-indigo-500"></i>
                        {{ __('Collections by Month') }}
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Month') }}</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Transaction Count') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($byMonth as $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->ym }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">{{ $row->cnt }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold font-mono text-gray-900 dark:text-white">
                                        GHS {{ number_format((float) $row->total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10">
                                        <x-college.empty-state :title="__('No Payment Data')" :description="__('No monthly payment breakdowns found matching selected filter criteria.')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Revenue by Program --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
                        {{ __('Revenue Generated by Program') }}
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payments Count') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($byProgram as $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->program_name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300 font-mono">{{ $row->cnt }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold font-mono text-gray-900 dark:text-white">
                                        GHS {{ number_format((float) $row->total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10">
                                        <x-college.empty-state :title="__('No Program Revenue Data')" :description="__('No revenue records found matching selected filter criteria.')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment Methods Column --}}
        <div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-indigo-500"></i>
                        {{ __('Payment Methods Breakdown') }}
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    @php
                        $maxMethodTotal = max(array_merge([1], $byMethod->pluck('total')->toArray()));
                    @endphp
                    @forelse ($byMethod as $row)
                        @php
                            $percentage = ($row->total / $maxMethodTotal) * 100;
                            $methodColors = [
                                'Cash' => 'bg-green-500 dark:bg-green-600',
                                'Mobile Money' => 'bg-amber-500 dark:bg-amber-600',
                                'Bank Transfer' => 'bg-blue-500 dark:bg-blue-600',
                                'Bank Draft' => 'bg-indigo-500 dark:bg-indigo-600',
                                'Check' => 'bg-purple-500 dark:bg-purple-600',
                            ];
                            $barColor = $methodColors[$row->payment_method] ?? 'bg-indigo-500';
                        @endphp
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold text-gray-700 dark:text-gray-300">{{ $row->payment_method }}</span>
                                <div class="text-right">
                                    <span class="block font-bold text-gray-900 dark:text-white font-mono">GHS {{ number_format((float) $row->total, 2) }}</span>
                                    <span class="block text-xs text-gray-500 font-mono">{{ $row->cnt }} transactions</span>
                                </div>
                            </div>
                            <div class="h-3 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-3 rounded-full {{ $barColor }} transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <x-college.empty-state :title="__('No Methods Data')" :description="__('No payment methods statistics found.')" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Transaction History Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-500"></i>
                {{ __('Transaction History Logs') }}
            </h2>
        </div>
        
        <div class="relative">
            {{-- Targeted Loading Overlay --}}
            <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, academicSessionId, paymentMethod, startDate, endDate" class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
                <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reference Number') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Method') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount Paid') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payment Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($transactions as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">{{ $row->reference_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $row->student?->lastname }}, {{ $row->student?->firstname }}
                                    <span class="block text-xs font-mono text-gray-500">{{ $row->student?->index_number }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->feeStructure?->program?->name ?? __('N/A') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400">
                                        {{ $row->payment_method }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold font-mono text-green-600 dark:text-green-400">
                                    GHS {{ number_format((float) $row->amount_paid, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $row->payment_date ? $row->payment_date->format('Y-m-d') : __('N/A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10">
                                    <x-college.empty-state :title="__('No Transactions Recorded')" :description="__('No detailed transaction history records found matching selected filter criteria.')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 no-print">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    {{-- Styling for Print Layout --}}
    <style>
        @media print {
            html, body, main, .flex-1, .overflow-y-auto, .min-h-0 {
                overflow: visible !important;
                height: auto !important;
                max-height: none !important;
                min-height: 0 !important;
            }
            body {
                background-color: white !important;
                color: black !important;
            }
            .no-print, aside, nav, header, [role="navigation"] {
                display: none !important;
            }
            .print-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</div>
