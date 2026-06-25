<div class="space-y-6" x-data="{ activeTab: 'invoices' }">
    <!-- Top Stats / Actions Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
            </span>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Accounts Payable & Operations') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Track institutional procurement and operational costs.') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button
                type="button"
                wire:click="openAddInvoice"
                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2.5 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
            >
                <i class="fa-solid fa-receipt text-xs"></i>{{ __('Record Invoice') }}
            </button>

            <button
                type="button"
                wire:click="openRecordExpenditure"
                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition"
            >
                <i class="fa-solid fa-wallet text-xs"></i>{{ __('Record Expenditure') }}
            </button>
        </div>
    </div>

    <!-- Navigation Tabs & Search -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <!-- Tabs Buttons -->
        <div class="flex border-b border-gray-100 dark:border-gray-700/60 pb-1 gap-1">
            <button
                type="button"
                @click="activeTab = 'invoices'"
                :class="activeTab === 'invoices' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-500 dark:text-gray-450 hover:text-gray-700 dark:hover:text-gray-300 font-semibold'"
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-file-invoice mr-1.5"></i>{{ __('Vendor Invoices') }}
            </button>
            <button
                type="button"
                @click="activeTab = 'expenditures'"
                :class="activeTab === 'expenditures' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-450 hover:text-gray-700 dark:hover:text-gray-300 font-semibold'"
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-money-bill-wave mr-1.5"></i>{{ __('Expenditures') }}
            </button>
            <button
                type="button"
                @click="activeTab = 'products'"
                :class="activeTab === 'products' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-450 hover:text-gray-700 dark:hover:text-gray-300 font-semibold'"
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-tags mr-1.5"></i>{{ __('Supplier Products') }}
            </button>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full md:w-80">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
            </div>
            <input
                wire:model.live="search"
                type="text"
                placeholder="{{ __('Search by keyword...') }}"
                class="block w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-gray-50 dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 text-xs transition"
            />
        </div>
    </div>

    <!-- TABS PANELS -->
    <div>
        <!-- PANEL: INVOICES -->
        <div x-show="activeTab === 'invoices'" class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                            <tr>
                                <th class="px-6 py-4">{{ __('Invoice #') }}</th>
                                <th class="px-6 py-4">{{ __('Vendor / Supplier') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Invoice Date') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Total Amount') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Paid') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Balance') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 border-t border-gray-100 dark:border-gray-700">
                            @forelse ($invoices as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                    <td class="px-6 py-4 font-mono font-semibold text-purple-600 dark:text-purple-400">
                                        {{ $invoice->invoice_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $invoice->vendor_name }}</div>
                                        @if($invoice->description)
                                            <div class="text-3xs text-gray-400 truncate max-w-xs">{{ $invoice->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        {{ $invoice->invoice_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-950 dark:text-white">
                                        ${{ number_format((float) $invoice->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-green-600 dark:text-green-400">
                                        ${{ number_format($invoice->paid_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-amber-600 dark:text-amber-400">
                                        ${{ number_format($invoice->remaining_balance, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $badgeClass = match($invoice->status) {
                                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300',
                                                'partially_paid' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700/50 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-3xs font-bold uppercase tracking-wider {{ $badgeClass }}">
                                            {{ str_replace('_', ' ', $invoice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($invoice->remaining_balance > 0.0)
                                                <button
                                                    type="button"
                                                    wire:click="openRecordExpenditure({{ $invoice->id }})"
                                                    title="{{ __('Record Payment') }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-950/30 dark:text-green-400 dark:hover:bg-green-900/30 transition"
                                                >
                                                    <i class="fa-solid fa-wallet text-2xs"></i>
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="viewInvoiceItems({{ $invoice->id }})"
                                                title="{{ __('View Items') }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-955/30 dark:text-purple-400 dark:hover:bg-purple-900/30 transition"
                                            >
                                                <i class="fa-solid fa-list text-2xs"></i>
                                            </button>

                                            @if ($invoice->file_path)
                                                <a
                                                    href="{{ Storage::url($invoice->file_path) }}"
                                                    target="_blank"
                                                    title="{{ __('View Receipt Scan') }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-955/30 dark:text-blue-400 dark:hover:bg-blue-900/30 transition"
                                                >
                                                    <i class="fa-solid fa-file-pdf text-2xs"></i>
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="deleteInvoice({{ $invoice->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this invoice? Associated expenditures will remain.') }}"
                                                title="{{ __('Delete Invoice') }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-955/30 dark:text-red-450 dark:hover:bg-red-900/30 transition"
                                            >
                                                <i class="fa-solid fa-trash text-2xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                        <i class="fa-solid fa-file-invoice text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                        <p class="text-xs font-semibold">{{ __('No invoices found.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($invoices->hasPages())
                    <div class="border-t border-gray-150 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- PANEL: EXPENDITURES -->
        <div x-show="activeTab === 'expenditures'" class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                            <tr>
                                <th class="px-6 py-4">{{ __('Expense #') }}</th>
                                <th class="px-6 py-4">{{ __('Category') }}</th>
                                <th class="px-6 py-4">{{ __('Invoice Reference') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('Payment Date') }}</th>
                                <th class="px-6 py-4">{{ __('Payment Method') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Amount Paid') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 border-t border-gray-100 dark:border-gray-700">
                            @forelse ($expenditures as $exp)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                    <td class="px-6 py-4 font-mono font-semibold text-gray-900 dark:text-white">
                                        {{ $exp->expense_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-950 dark:text-white">{{ $exp->category }}</div>
                                        @if($exp->notes)
                                            <div class="text-3xs text-gray-400 max-w-xs truncate">{{ $exp->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($exp->invoice)
                                            <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-3xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                <i class="fa-solid fa-file-invoice"></i>{{ $exp->invoice->invoice_number }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600 font-italic text-2xs">{{ __('Direct Expense') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        {{ $exp->payment_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $exp->payment_method }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                        ${{ number_format((float) $exp->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($exp->proof_file_path)
                                                <a
                                                    href="{{ Storage::url($exp->proof_file_path) }}"
                                                    target="_blank"
                                                    title="{{ __('View Proof') }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-955/30 dark:text-blue-400 dark:hover:bg-blue-900/30 transition"
                                                >
                                                    <i class="fa-solid fa-receipt text-2xs"></i>
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="deleteExpenditure({{ $exp->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this expenditure? This will restore the unpaid balance on any linked invoice.') }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-955/30 dark:text-red-450 dark:hover:bg-red-900/30 transition"
                                                title="{{ __('Delete') }}"
                                            >
                                                <i class="fa-solid fa-trash text-2xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                        <i class="fa-solid fa-money-bill-transfer text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                        <p class="text-xs font-semibold">{{ __('No expenditures recorded.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($expenditures->hasPages())
                    <div class="border-t border-gray-150 p-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                        {{ $expenditures->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- PANEL: SUPPLIER PRODUCTS -->
        <div x-show="activeTab === 'products'" class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                            <tr>
                                <th class="px-6 py-4">{{ __('Product Name') }}</th>
                                <th class="px-6 py-4">{{ __('SKU Code') }}</th>
                                <th class="px-6 py-4">{{ __('Category') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Default Unit Price') }}</th>
                                <th class="px-6 py-4">{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 border-t border-gray-100 dark:border-gray-700">
                            @forelse ($products as $prod)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                        {{ $prod->name }}
                                    </td>
                                    <td class="px-6 py-4 font-mono font-semibold text-purple-600 dark:text-purple-400">
                                        {{ $prod->sku }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $prod->category }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-950 dark:text-white">
                                        ${{ number_format((float) $prod->unit_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 max-w-sm truncate text-gray-400 dark:text-gray-500">
                                        {{ $prod->description ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                        <i class="fa-solid fa-tags text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                        <p class="text-xs font-semibold">{{ __('No supplier products registered.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD / RECORD INVOICE -->
    @if ($showInvoiceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-550/75 backdrop-blur-sm transition-opacity" wire:click="$set('showInvoiceModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle dark:bg-gray-800">
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-150 dark:border-gray-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                <i class="fa-solid fa-receipt"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Record Supplier Invoice') }}</h3>
                        </div>
                        <button type="button" wire:click="$set('showInvoiceModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- OCR Scan Mock Section -->
                        <div class="relative bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-950/20 dark:to-indigo-950/20 border border-purple-100 dark:border-purple-900/30 p-4 rounded-xl">
                            <!-- Glowing laser scanner animation overlay -->
                            @if ($isScanning)
                                <div class="absolute inset-0 bg-purple-900/10 dark:bg-purple-950/20 flex flex-col items-center justify-center rounded-xl overflow-hidden">
                                    <!-- Horizontal laser beam -->
                                    <div class="absolute top-0 left-0 w-full h-1 bg-green-500 shadow-[0_0_10px_#22c55e] animate-[bounce_2s_infinite]"></div>
                                    <div class="flex items-center gap-2 bg-white dark:bg-gray-900 px-4 py-2 rounded-lg shadow border border-purple-200 dark:border-purple-800/40">
                                        <i class="fa-solid fa-spinner animate-spin text-purple-600"></i>
                                        <span class="text-xs font-bold text-gray-800 dark:text-white">{{ __('AI OCR Receipt Scanning in Progress...') }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <h4 class="text-xs font-bold text-purple-900 dark:text-purple-300">{{ __('AI OCR Receipt Scanner') }}</h4>
                                    <p class="text-3xs text-purple-700 dark:text-purple-400 max-w-xl">
                                        {{ __('Upload a scanned invoice or PDF receipt. The system will mock-analyze the document using OCR to extract the supplier name, invoice details, dates, and automatically fill the items list below.') }}
                                    </p>
                                </div>

                                <div class="shrink-0 flex items-center gap-2.5">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3.5 py-2 text-3xs font-extrabold text-white shadow hover:bg-purple-700 transition">
                                        <i class="fa-solid fa-file-arrow-up"></i>{{ __('Upload Receipt') }}
                                        <input type="file" wire:model="invoiceFile" class="hidden" accept="image/*,application/pdf" />
                                    </label>
                                    @if ($invoiceFile)
                                        <button
                                            type="button"
                                            wire:click="runMockOCR"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3.5 py-2 text-3xs font-extrabold text-white shadow hover:bg-green-700 transition"
                                        >
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>{{ __('Rescan OCR') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @if ($invoiceFile)
                                <div class="mt-2 text-3xs text-purple-600 dark:text-purple-300 font-mono">
                                    {{ __('Selected File: ') }} {{ $invoiceFile->getClientOriginalName() }}
                                </div>
                            @endif
                        </div>

                        <!-- Main Form Fields -->
                        <form wire:submit="saveInvoice" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <x-input-label for="invoice_number" :value="__('Invoice Number')" />
                                    <x-text-input wire:model="invoice_number" id="invoice_number" type="text" class="block w-full text-xs" required />
                                    <x-input-error :messages="$errors->get('invoice_number')" class="mt-1" />
                                </div>

                                <div class="space-y-1.5">
                                    <x-input-label for="vendor_name" :value="__('Vendor / Supplier Name')" />
                                    <x-text-input wire:model="vendor_name" id="vendor_name" type="text" class="block w-full text-xs" required />
                                    <x-input-error :messages="$errors->get('vendor_name')" class="mt-1" />
                                </div>

                                <div class="space-y-1.5">
                                    <x-input-label for="invoice_date" :value="__('Invoice Date')" />
                                    <x-text-input wire:model="invoice_date" id="invoice_date" type="date" class="block w-full text-xs" required />
                                    <x-input-error :messages="$errors->get('invoice_date')" class="mt-1" />
                                </div>

                                <div class="space-y-1.5">
                                    <x-input-label for="due_date" :value="__('Due Date')" />
                                    <x-text-input wire:model="due_date" id="due_date" type="date" class="block w-full text-xs" required />
                                    <x-input-error :messages="$errors->get('due_date')" class="mt-1" />
                                </div>

                                <div class="md:col-span-2 space-y-1.5">
                                    <x-input-label for="description" :value="__('Description')" />
                                    <x-textarea-input wire:model="description" id="description" class="block w-full text-xs" rows="2" />
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>

                            <!-- Line Items Area -->
                            <div class="border-t border-gray-150 pt-4 dark:border-gray-700/60">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white mb-3"><i class="fa-solid fa-list-check mr-1.5 text-purple-500"></i>{{ __('Invoice Line Items') }}</h4>
                                
                                <!-- Add Item Subform -->
                                <div class="bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-wrap items-end gap-3 mb-4">
                                    <div class="flex-1 min-w-[200px] space-y-1">
                                        <label class="block text-3xs font-semibold text-gray-600 dark:text-gray-400">{{ __('Select Product') }}</label>
                                        <select wire:model.live="selected_product_id" class="block w-full rounded-lg border-gray-300 py-1.5 text-xs shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            <option value="">-- {{ __('Choose Product') }} --</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="w-20 space-y-1">
                                        <label class="block text-3xs font-semibold text-gray-600 dark:text-gray-400">{{ __('Qty') }}</label>
                                        <x-text-input wire:model="item_quantity" type="number" min="1" class="block w-full text-xs py-1" />
                                    </div>

                                    <div class="w-28 space-y-1">
                                        <label class="block text-3xs font-semibold text-gray-600 dark:text-gray-400">{{ __('Unit Price ($)') }}</label>
                                        <x-text-input wire:model="item_unit_price" type="number" step="0.01" min="0" class="block w-full text-xs py-1" />
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="addLineItem"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
                                    >
                                        <i class="fa-solid fa-plus"></i>{{ __('Add') }}
                                    </button>
                                </div>

                                <!-- Current Items List -->
                                <div class="overflow-x-auto rounded-lg border border-gray-150 dark:border-gray-700/60">
                                    <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400">
                                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold">
                                            <tr>
                                                <th class="px-4 py-2.5">{{ __('Product') }}</th>
                                                <th class="px-4 py-2.5 text-center">{{ __('Qty') }}</th>
                                                <th class="px-4 py-2.5 text-right">{{ __('Unit Price') }}</th>
                                                <th class="px-4 py-2.5 text-right">{{ __('Total') }}</th>
                                                <th class="px-4 py-2.5 text-right">{{ __('Remove') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @forelse ($invoiceItems as $index => $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
                                                    <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white">{{ $item['name'] }}</td>
                                                    <td class="px-4 py-2.5 text-center">{{ $item['quantity'] }}</td>
                                                    <td class="px-4 py-2.5 text-right">${{ number_format($item['unit_price'], 2) }}</td>
                                                    <td class="px-4 py-2.5 text-right font-semibold">${{ number_format($item['total'], 2) }}</td>
                                                    <td class="px-4 py-2.5 text-right">
                                                        <button type="button" wire:click="removeLineItem({{ $index }})" class="text-red-500 hover:text-red-750">
                                                            <i class="fa-solid fa-circle-minus text-sm"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                                                        {{ __('No items added yet. Fill in product details above or run AI OCR Scanner.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if (count($invoiceItems) > 0)
                                            <tfoot class="bg-gray-50 dark:bg-gray-900 font-bold text-gray-900 dark:text-white">
                                                <tr>
                                                    <td colspan="3" class="px-4 py-3 text-right uppercase tracking-wider text-2xs">{{ __('Total Invoice Bill:') }}</td>
                                                    <td class="px-4 py-3 text-right">${{ number_format($amount, 2) }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-gray-150 pt-4 dark:border-gray-700/60">
                                <button type="button" wire:click="$set('showInvoiceModal', false)" class="rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-250 dark:hover:bg-gray-700">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit" class="rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-semibold text-white shadow hover:bg-purple-700 transition">
                                    <i class="fa-solid fa-floppy-disk mr-1.5"></i>{{ __('Save Invoice') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: RECORD EXPENDITURE -->
    @if ($showExpenditureModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-550/75 backdrop-blur-sm transition-opacity" wire:click="$set('showExpenditureModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-gray-800">
                    <div class="bg-gray-550 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-150 dark:border-gray-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                <i class="fa-solid fa-wallet"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Record Operating Expenditure') }}</h3>
                        </div>
                        <button type="button" wire:click="$set('showExpenditureModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <form wire:submit="saveExpenditure" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <x-input-label for="expense_number" :value="__('Expense Reference Number')" />
                            <x-text-input wire:model="expense_number" id="expense_number" type="text" class="block w-full text-xs" required />
                            <x-input-error :messages="$errors->get('expense_number')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="expenditure_invoice_id" :value="__('Link to Invoice (Optional)')" />
                            <select wire:model.live="expenditure_invoice_id" class="block w-full rounded-lg border-gray-300 py-2.5 text-xs shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                                <option value="">-- {{ __('Direct / No Invoice Link') }} --</option>
                                @foreach ($unpaidInvoices as $unpaid)
                                    <option value="{{ $unpaid->id }}">{{ $unpaid->invoice_number }} - {{ $unpaid->vendor_name }} (Bal: ${{ number_format($unpaid->remaining_balance, 2) }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('expenditure_invoice_id')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="expenditure_amount" :value="__('Amount Paid ($)')" />
                            <x-text-input wire:model="expenditure_amount" id="expenditure_amount" type="number" step="0.01" min="0.01" class="block w-full text-xs" required />
                            <x-input-error :messages="$errors->get('expenditure_amount')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="expenditure_category" :value="__('Expenditure Category')" />
                            <select wire:model="expenditure_category" class="block w-full rounded-lg border-gray-300 py-2.5 text-xs shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                                <option value="Stationery">{{ __('Stationery & Supplies') }}</option>
                                <option value="IT Infrastructure">{{ __('IT Infrastructure & Software') }}</option>
                                <option value="Catering">{{ __('Catering & Welfare Services') }}</option>
                                <option value="Utilities">{{ __('Utilities (Electricity, Water, Internet)') }}</option>
                                <option value="Maintenance">{{ __('Maintenance & Repairs') }}</option>
                                <option value="Academic Support">{{ __('Academic Support Materials') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('expenditure_category')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="payment_method" :value="__('Payment Method')" />
                            <select wire:model="payment_method" class="block w-full rounded-lg border-gray-300 py-2.5 text-xs shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                                <option value="Cash">{{ __('Cash') }}</option>
                                <option value="Bank Transfer">{{ __('Bank Transfer') }}</option>
                                <option value="Check">{{ __('Corporate Check') }}</option>
                                <option value="Mobile Money">{{ __('Mobile Money') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="payment_date" :value="__('Payment Date')" />
                            <x-text-input wire:model="payment_date" id="payment_date" type="date" class="block w-full text-xs" required />
                            <x-input-error :messages="$errors->get('payment_date')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="reference_number" :value="__('Payment Reference (e.g. Txn ID, Check #)')" />
                            <x-text-input wire:model="reference_number" id="reference_number" type="text" class="block w-full text-xs" />
                            <x-input-error :messages="$errors->get('reference_number')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="proofFile" :value="__('Proof of Payment (Image / PDF)')" />
                            <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-gray-350 bg-gray-50 px-3 py-2 text-3xs font-semibold text-gray-700 hover:bg-gray-100 transition dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                <i class="fa-solid fa-paperclip"></i>{{ __('Attach Proof Receipt') }}
                                <input type="file" wire:model="proofFile" class="hidden" accept="image/*,application/pdf" />
                            </label>
                            @if ($proofFile)
                                <div class="text-3xs text-purple-600 dark:text-purple-400 font-mono mt-1">
                                    {{ $proofFile->getClientOriginalName() }}
                                </div>
                            @endif
                            <x-input-error :messages="$errors->get('proofFile')" class="mt-1" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="notes" :value="__('Additional Notes')" />
                            <x-textarea-input wire:model="notes" id="notes" class="block w-full text-xs" rows="2" />
                            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-150 pt-4 dark:border-gray-700/60">
                            <button type="button" wire:click="$set('showExpenditureModal', false)" class="rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-250 dark:hover:bg-gray-700">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-semibold text-white shadow hover:bg-purple-700 transition">
                                <i class="fa-solid fa-circle-check mr-1.5"></i>{{ __('Record Expense') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: VIEW INVOICE ITEMS -->
    @if ($showItemsModal && $activeInvoice)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-550/75 backdrop-blur-sm transition-opacity" wire:click="$set('showItemsModal', false)"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle dark:bg-gray-800">
                    <div class="bg-gray-550 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-150 dark:border-gray-700/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                <i class="fa-solid fa-list"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Invoice Line Items Details') }}</h3>
                        </div>
                        <button type="button" wire:click="$set('showItemsModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex flex-wrap justify-between gap-4 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700">
                            <div>
                                <span class="text-3xs uppercase tracking-wider text-gray-450">{{ __('Invoice #') }}</span>
                                <div class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $activeInvoice->invoice_number }}</div>
                            </div>
                            <div>
                                <span class="text-3xs uppercase tracking-wider text-gray-450">{{ __('Vendor') }}</span>
                                <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $activeInvoice->vendor_name }}</div>
                            </div>
                            <div>
                                <span class="text-3xs uppercase tracking-wider text-gray-450">{{ __('Date') }}</span>
                                <div class="text-xs font-bold text-gray-850 dark:text-gray-200">{{ $activeInvoice->invoice_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <span class="text-3xs uppercase tracking-wider text-gray-450">{{ __('Total Bill') }}</span>
                                <div class="text-xs font-bold text-gray-950 dark:text-white">${{ number_format((float) $activeInvoice->amount, 2) }}</div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-150 dark:border-gray-700">
                            <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400">
                                <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                                    <tr>
                                        <th class="px-4 py-2.5">{{ __('Product Name') }}</th>
                                        <th class="px-4 py-2.5">{{ __('SKU') }}</th>
                                        <th class="px-4 py-2.5 text-center">{{ __('Quantity') }}</th>
                                        <th class="px-4 py-2.5 text-right">{{ __('Unit Price') }}</th>
                                        <th class="px-4 py-2.5 text-right">{{ __('Total Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($activeInvoice->items as $activeItem)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
                                            <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">
                                                {{ $activeItem->product->name }}
                                            </td>
                                            <td class="px-4 py-2.5 font-mono text-purple-600 dark:text-purple-400">
                                                {{ $activeItem->product->sku }}
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                {{ $activeItem->quantity }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                ${{ number_format((float) $activeItem->unit_price, 2) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold text-gray-900 dark:text-white">
                                                ${{ number_format((float) $activeItem->total_amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button" wire:click="$set('showItemsModal', false)" class="rounded-lg bg-gray-100 px-5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
