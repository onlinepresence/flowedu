<div class="mx-auto max-w-5xl space-y-6">
    <!-- Header tabs navigation / Top selectors -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4 print:hidden">
        <div>
            <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ __('Fees & Financial Details') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Track billed amounts, payments made, and review itemized bill breakdowns.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Select Term:') }}</span>
            <select 
                id="fee-structure-select" 
                wire:change="selectStructure($event.target.value)" 
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs font-medium focus:ring-purple-500 focus:border-purple-500"
            >
                @foreach ($structures as $fs)
                    <option value="{{ $fs->id }}" {{ $selectedStructureId === $fs->id ? 'selected' : '' }}>
                        {{ $fs->session->name }} (Level {{ $fs->level }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($selectedStructure)
        <!-- Summary Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
            <!-- Total Billed -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Billed') }}</span>
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($billedAmount, 2) }}</p>
                <span class="text-[10px] text-gray-400 font-mono">GHS</span>
            </div>

            <!-- Total Paid -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Paid') }}</span>
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-green-50 text-green-600 dark:bg-green-950/30 dark:text-green-400">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($paidAmount, 2) }}</p>
                <span class="text-[10px] text-gray-400 font-mono">GHS</span>
            </div>

            <!-- Scholarships -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Scholarships') }}</span>
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($scholarshipAmount, 2) }}</p>
                <span class="text-[10px] text-gray-400 font-mono">GHS</span>
            </div>

            <!-- Outstanding Balance -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Outstanding Balance') }}</span>
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg {{ $balance > 0 ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' : 'bg-green-50 text-green-600 dark:bg-green-950/30 dark:text-green-400' }}">
                        <i class="fa-solid fa-scale-unbalanced"></i>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ number_format($balance, 2) }}</p>
                <span class="text-[10px] text-gray-400 font-mono">GHS</span>
            </div>
        </div>

        <!-- Detailed Breakdown Banner Trigger -->
        <div class="flex items-center justify-between bg-purple-50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 p-5 rounded-xl print:hidden">
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-purple-950 dark:text-purple-300">{{ __('Fee breakdown details') }}</h4>
                <p class="text-xs text-purple-700 dark:text-purple-400">{{ __('Check all detailed billing items for this academic session.') }}</p>
            </div>
            <button 
                type="button" 
                wire:click="openBreakdown" 
                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-purple-500 transition duration-205"
            >
                <i class="fa-solid fa-list-ul"></i>
                {{ __('Show Breakdown') }}
            </button>
        </div>

        <!-- Recent Payments Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 print:hidden">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">{{ __('Recent Payments for this Session') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reference') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payment Method') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($payments as $payment)
                            <tr wire:key="p-{{ $payment->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-medium text-gray-900 dark:text-gray-100">
                                    {{ $payment->reference_number ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $payment->payment_method === 'Cash' ? 'bg-green-50 text-green-700 ring-green-600/10 dark:bg-green-400/10 dark:text-green-400' : 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-400/10 dark:text-blue-400' }}">
                                        {{ $payment->payment_method }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $payment->payment_date }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400 font-mono">
                                    {{ number_format((float)$payment->amount_paid, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No payments recorded for this structure yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Breakdown Details Modal (Alpine-backed) -->
        <div
            x-data="{ show: @entangle('showBreakdownModal') }"
            x-show="show"
            class="fixed inset-0 z-50 overflow-y-auto print:hidden"
            style="display: none;"
        >
            <div class="flex min-h-screen items-center justify-center p-4 text-center">
                <!-- Backdrop -->
                <div 
                    x-show="show" 
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0" 
                    x-transition:enter-end="opacity-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100" 
                    x-transition:leave-end="opacity-0" 
                    class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" 
                    @click="show = false"
                ></div>

                <!-- Panel -->
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 text-left shadow-xl transition-all w-full max-w-xl border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                        <h3 class="text-md font-bold text-gray-950 dark:text-white">
                            {{ __('Fee Breakdown') }} - {{ $selectedStructure->session->name }} (L{{ $selectedStructure->level }})
                        </h3>
                        <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-450">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>                    @if ($hasDetailedAccess)
                        <div class="space-y-4">
                            <!-- Table breakdown -->
                            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left font-semibold text-gray-500 dark:text-gray-400">{{ __('Component') }}</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-500 dark:text-gray-400">{{ __('Amount (GHS)') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($breakdown as $item)
                                            <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-900/10">
                                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $item['label'] }}</td>
                                                <td class="px-4 py-2 text-right font-mono text-gray-900 dark:text-white font-medium">{{ number_format($item['amount'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-50 dark:bg-gray-900/50 font-bold">
                                            <td class="px-4 py-2.5 text-gray-900 dark:text-white">{{ __('Total Billed Amount') }}</td>
                                            <td class="px-4 py-2.5 text-right font-mono text-gray-900 dark:text-white">{{ number_format($billedAmount, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex gap-2 justify-end pt-3 border-t border-gray-200 dark:border-gray-700">
                                <button 
                                    type="button" 
                                    @click="show = false" 
                                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    {{ __('Close') }}
                                </button>
                                <button 
                                    type="button" 
                                    onclick="window.print()" 
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 px-4 py-2 text-xs font-semibold text-white shadow-sm transition"
                                >
                                    <i class="fa-solid fa-print"></i>
                                    {{ __('Print Breakdown') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Request Details Block -->
                        <div class="space-y-4">
                            <div class="flex items-start gap-3 rounded-lg bg-amber-50 p-4 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40 text-amber-800 dark:text-amber-300">
                                <span class="shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                                    <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                </span>
                                <div class="space-y-1 text-xs leading-relaxed">
                                    <p class="font-bold">{{ __('Itemized Billing Breakdown Restricted') }}</p>
                                    <p>{{ __('Detailed breakdown components are hidden by default. You can request breakdown visibility approval from the accounts office below.') }}</p>
                                </div>
                            </div>

                            <div class="text-center py-4">
                                @if ($requestStatus === null)
                                    <button 
                                        type="button" 
                                        wire:click="requestDetailedBreakdown" 
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-semibold text-white shadow-md hover:bg-purple-500 transition duration-205"
                                    >
                                        <i class="fa-solid fa-paper-plane"></i>
                                        {{ __('Request Detailed Access') }}
                                    </button>
                                @elseif ($requestStatus === 'pending')
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400">
                                            <i class="fa-solid fa-hourglass-half"></i>
                                            {{ __('Awaiting Approval') }}
                                        </span>
                                        <p class="text-2xs text-gray-500 mt-2 max-w-xs leading-relaxed">
                                            {{ __('Your access request is currently pending review by the finance officer.') }}
                                        </p>
                                    </div>
                                @elseif ($requestStatus === 'rejected')
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3.5 py-1 text-xs font-semibold text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-400/10 dark:text-red-400">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                            {{ __('Access Request Declined') }}
                                        </span>
                                        <p class="text-2xs text-gray-500 mt-2 max-w-xs leading-relaxed">
                                            {{ __('Your access request was declined. Please contact the finance officer to inquire.') }}
                                        </p>
                                        <button 
                                            type="button" 
                                            wire:click="requestDetailedBreakdown" 
                                            class="mt-3 text-xs font-bold text-purple-600 hover:text-purple-500 dark:text-purple-400 hover:underline"
                                        >
                                            {{ __('Re-request Access') }}
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-700">
                                <button 
                                    type="button" 
                                    @click="show = false" 
                                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- FORMAL INSTITUTION PRINT LAYOUT (Hidden on screen, block when printed) -->
        @if ($hasDetailedAccess)
            <div class="hidden print:block print-invoice-container font-serif p-8 text-black bg-white max-w-4xl mx-auto">
                <!-- Formal Institution Header -->
                <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold uppercase tracking-wide">{{ $schoolName }}</h1>
                        <p class="text-sm mt-1">{{ $schoolAddress }}</p>
                        <p class="text-sm">{{ $schoolPhone }} | {{ $schoolEmail }}</p>
                        <p class="text-xs italic text-gray-600 mt-1 font-sans uppercase">{{ $schoolMotto }}</p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-bold uppercase tracking-wider text-gray-800">{{ __('INVOICE / BILL DETAILS') }}</h2>
                        <p class="text-sm font-mono mt-1">{{ __('Date') }}: {{ now()->format('Y-m-d') }}</p>
                        <p class="text-xs font-mono text-gray-500 mt-1 uppercase">{{ __('Academic Session') }}: {{ $selectedStructure->session->name }}</p>
                    </div>
                </div>

                <!-- Student & Bill Info -->
                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div class="space-y-1">
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">{{ __('Billed To') }}:</h3>
                        <p class="font-bold text-base">{{ auth()->user()->name }}</p>
                        <p><span class="text-gray-500">{{ __('Index No') }}:</span> <span class="font-semibold font-mono">{{ $student->index_number }}</span></p>
                        <p><span class="text-gray-500">{{ __('Program') }}:</span> <span>{{ $student->program->name }}</span></p>
                        <p><span class="text-gray-500">{{ __('Level') }}:</span> <span>{{ $selectedStructure->level }}</span></p>
                    </div>
                    <div class="text-right space-y-1">
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">{{ __('Summary') }}:</h3>
                        <p>{{ __('Total Billed') }}: <span class="font-bold font-mono">{{ number_format($billedAmount, 2) }}</span></p>
                        <p>{{ __('Total Paid') }}: <span class="font-bold font-mono text-green-700">{{ number_format($paidAmount, 2) }}</span></p>
                        <p>{{ __('Scholarships') }}: <span class="font-bold font-mono text-blue-700">{{ number_format($scholarshipAmount, 2) }}</span></p>
                        <div class="border-t border-gray-300 mt-1 pt-1">
                            <p class="font-bold text-base">{{ __('Outstanding Balance') }}: <span class="font-mono text-red-700 font-extrabold">{{ number_format($balance, 2) }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Itemized Table -->
                <table class="w-full text-left text-sm border-collapse mb-8">
                    <thead>
                        <tr class="border-b border-gray-800 uppercase text-xs tracking-wider">
                            <th class="py-2.5 font-bold">{{ __('Fee Component') }}</th>
                            <th class="py-2.5 text-right font-bold">{{ __('Amount (GHS)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($breakdown as $item)
                            <tr class="border-b border-gray-200">
                                <td class="py-2.5">{{ $item['label'] }}</td>
                                <td class="py-2.5 text-right font-mono">{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold border-b-2 border-gray-800 bg-gray-50/50">
                            <td class="py-2.5 uppercase">{{ __('Total Billed') }}</td>
                            <td class="py-2.5 text-right font-mono">{{ number_format($billedAmount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Signature and Footer -->
                <div class="flex justify-between items-end mt-12 text-sm">
                    <div>
                        <p class="italic text-gray-500 mb-1 text-xs">{{ __('Issued by:') }}</p>
                        <p class="font-bold">{{ __('Finance & Accounts Department') }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $schoolName }}</p>
                    </div>
                    <div class="text-center w-48 border-t border-gray-400 pt-1">
                        <p class="font-semibold text-xs">{{ __('Authorized Signature') }}</p>
                        <div class="mt-4 border-2 border-dashed border-gray-300 h-10 w-32 mx-auto flex items-center justify-center text-[10px] text-gray-400 uppercase font-bold tracking-widest font-sans">
                            {{ __('STAMP') }}
                        </div>
                    </div>
                </div>
            </div>

            <style>
                @media print {
                    /* Hide everything inside body */
                    body * {
                        visibility: hidden !important;
                    }
                    /* Show only the print invoice container and its contents */
                    .print-invoice-container,
                    .print-invoice-container * {
                        visibility: visible !important;
                    }
                    /* Ensure print layout spans the page correctly and resets styling styles */
                    .print-invoice-container {
                        position: absolute !important;
                        left: 0 !important;
                        top: 0 !important;
                        width: 100% !important;
                        padding: 0 !important;
                        margin: 0 !important;
                        background: white !important;
                        color: black !important;
                        box-shadow: none !important;
                        border: none !important;
                    }
                    /* Remove headers, footers, scrollbars from root/html/body */
                    html, body {
                        background-color: white !important;
                        color: black !important;
                        overflow: visible !important;
                        height: auto !important;
                    }
                }
            </style>
        @endif
    @else
        <x-college.empty-state
            :title="__('No fees recorded')"
            :description="__('No fee structures have been defined for your level or program yet.')"
        >
            <x-slot:icon>
                <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-1.958-.659-1.171-.879-1.171-2.303 0-3.182 1.172-.879 3.07-.879 4.242 0L15 8.818M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-college.empty-state>
    @endif
</div>
