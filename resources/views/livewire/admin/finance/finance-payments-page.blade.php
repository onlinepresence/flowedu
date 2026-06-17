<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        <button type="button" wire:click="openRecordModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <i class="fa-solid fa-circle-plus"></i>
            {{ __('Record Payment') }}
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-vault text-indigo-500 mr-2"></i>{{ __('Total Collections') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($totalReceived, 2) }}</p>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-calendar-day text-green-500 mr-2"></i>{{ __('Today\'s Collections') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($todayReceived, 2) }}</p>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-list-ol text-gray-500 mr-2"></i>{{ __('Transactions Count') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $transactionCount }}</p>
        </div>
    </div>

    <!-- Filters Section -->
    <x-college.filter-card cols="3">
        <div>
            <x-input-label for="searchQuery" :value="__('Search Student')" />
            <x-text-input id="searchQuery" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name or Index Number') }}" wire:model.live.debounce.300ms="searchQuery" />
        </div>
        <div>
            <x-input-label for="filterSessionId" :value="__('Academic Year')" />
            <select id="filterSessionId" wire:model.live="filterSessionId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Academic Years') }}</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterMethod" :value="__('Payment Method')" />
            <select id="filterMethod" wire:model.live="filterMethod" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Methods') }}</option>
                <option value="Cash">{{ __('Cash') }}</option>
                <option value="Bank Draft">{{ __('Bank Draft') }}</option>
                <option value="Mobile Money">{{ __('Mobile Money') }}</option>
                <option value="Bank Transfer">{{ __('Bank Transfer') }}</option>
                <option value="Check">{{ __('Check') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Ledger Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program & Session') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Method') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reference') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount Paid') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="pay-{{ $row->id }}">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $row->student?->lastname }}, {{ $row->student?->firstname }}
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->student?->index_number ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->feeStructure?->program?->name ?? '—' }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->feeStructure?->session?->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400">
                                    {{ $row->payment_method ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $row->reference_number ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row->payment_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-950 dark:text-white font-bold whitespace-nowrap">{{ number_format((float) $row->amount_paid, 2) }}</td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                <button type="button" wire:click="openReceipt({{ $row->id }})" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 font-semibold" title="{{ __('Print Receipt') }}">
                                    <i class="fa-solid fa-print"></i>
                                    {{ __('Receipt') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No payment records found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $rows->links() }}
        </div>
    </div>

    <!-- Record Payment Modal -->
    <x-college.modal name="record-payment-modal" :title="__('Record Student Payment')" maxWidth="lg" livewireSynced>
        <form wire:submit.prevent="recordPayment" class="space-y-4">
            <!-- Student Selector -->
            <div class="relative">
                <x-input-label for="searchStudent" :value="__('Search & Select Student')" />
                <x-text-input id="searchStudent" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Type student name or index number') }}" wire:model.live="searchStudent" />
                <x-input-error :messages="$errors->get('student_id')" class="mt-1" />

                @if (!empty($searchedStudents))
                    <div class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:border-gray-700 dark:bg-gray-800 sm:text-sm">
                        @foreach ($searchedStudents as $std)
                            <button type="button" wire:click="selectStudent({{ $std->id }})" class="w-full text-left px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-900 dark:text-white">
                                <span class="font-medium">{{ $std->lastname }}, {{ $std->firstname }}</span>
                                <span class="ml-2 font-mono text-xs text-gray-500">{{ $std->index_number }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($student_id !== '')
                <!-- Student Debt Summary -->
                @if (!empty($studentDebtSummary))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/40">
                        <p class="text-xs font-bold uppercase text-amber-700 dark:text-amber-300 mb-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ __('Outstanding Balances') }}</p>
                        <div class="space-y-1">
                            @foreach ($studentDebtSummary as $debt)
                                <div class="flex justify-between text-xs">
                                    <span class="text-amber-800 dark:text-amber-200">{{ $debt['session'] }} — Level {{ $debt['level'] }}</span>
                                    <span class="font-bold text-amber-900 dark:text-amber-100">{{ number_format($debt['balance'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Fee Structure Selector -->
                <div>
                    <x-input-label for="fee_structure_id" :value="__('Fee Category / Structure')" />
                    <select id="fee_structure_id" wire:model="fee_structure_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('Select Fee Structure') }}</option>
                        @foreach ($feeStructures as $fs)
                            <option value="{{ $fs->id }}">
                                {{ $fs->program?->name ?? '—' }} ({{ $fs->session?->name ?? '' }}) - {{ __('Level :lvl', ['lvl' => $fs->level]) }} (Total: {{ number_format((float) $fs->total_amount, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('fee_structure_id')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="amount_paid" :value="__('Amount Paid')" />
                        <x-text-input id="amount_paid" type="number" min="0.01" step="0.01" class="mt-1 block w-full text-sm" wire:model="amount_paid" />
                        <x-input-error :messages="$errors->get('amount_paid')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="payment_method" :value="__('Payment Method')" />
                        <select id="payment_method" wire:model="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="Cash">{{ __('Cash') }}</option>
                            <option value="Bank Draft">{{ __('Bank Draft') }}</option>
                            <option value="Mobile Money">{{ __('Mobile Money') }}</option>
                            <option value="Bank Transfer">{{ __('Bank Transfer') }}</option>
                            <option value="Check">{{ __('Check') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="payment_date" :value="__('Payment Date')" />
                        <x-text-input id="payment_date" type="date" class="mt-1 block w-full text-sm" wire:model="payment_date" />
                        <x-input-error :messages="$errors->get('payment_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="reference_number" :value="__('Reference Number')" />
                        <x-text-input id="reference_number" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Optional') }}" wire:model="reference_number" />
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" x-on:click="$dispatch('close-modal', 'record-payment-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ __('Cancel') }}
                </button>
                @if ($student_id !== '')
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('Save Payment') }}
                    </button>
                @endif
            </div>
        </form>
    </x-college.modal>

    <!-- Print Receipt Modal -->
    <x-college.modal name="print-receipt-modal" :title="__('Receipt Details')" maxWidth="2xl" livewireSynced>
        @if ($receiptData)
            <div id="print-receipt" class="receipt-print-area p-6 bg-white text-gray-900 rounded-md border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <div class="flex justify-between items-start border-b pb-4 mb-6 border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-extrabold uppercase tracking-tight text-indigo-700 dark:text-indigo-400 font-sans">{{ $receiptData['settings']['header_title'] }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">{{ $receiptData['settings']['header_subtitle'] }}</p>
                        @if ($receiptData['settings']['contact_info'])
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-semibold font-sans">{{ $receiptData['settings']['contact_info'] }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm text-gray-700 dark:text-gray-300 font-sans">{{ __('Receipt No:') }} <span class="font-mono text-indigo-600 dark:text-indigo-400">REC-{{ $receiptData['payment']->id }}-{{ $receiptData['payment']->payment_date?->format('ymd') }}</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-sans">{{ __('Date:') }} {{ $receiptData['payment']->payment_date?->format('Y-m-d') }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase font-sans">{{ __('Student Details') }}</span>
                        <div class="font-bold text-gray-800 dark:text-gray-200 font-sans">{{ $receiptData['payment']->student?->lastname }}, {{ $receiptData['payment']->student?->firstname }}</div>
                        <div class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ __('Index:') }} {{ $receiptData['payment']->student?->index_number }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 font-sans">{{ __('Level:') }} {{ $receiptData['payment']->student?->current_year }}</div>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase font-sans">{{ __('Academic Details') }}</span>
                        <div class="font-bold text-gray-800 dark:text-gray-200 font-sans">{{ $receiptData['payment']->student?->program?->name }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 font-sans">{{ $receiptData['payment']->student?->department?->name }}</div>
                        <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 font-sans">{{ $receiptData['payment']->feeStructure?->session?->name }}</div>
                    </div>
                </div>
                <table class="w-full text-sm mb-6">
                    <thead>
                        <tr class="border-b border-gray-300 dark:border-gray-600 text-left text-gray-500 dark:text-gray-400">
                            <th class="pb-2 font-sans">{{ __('Description') }}</th>
                            <th class="pb-2 text-right font-sans">{{ __('Reference') }}</th>
                            <th class="pb-2 text-right font-sans">{{ __('Method') }}</th>
                            <th class="pb-2 text-right font-sans">{{ __('Paid') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-3 font-medium text-gray-800 dark:text-gray-200 font-sans">{{ __('Semester Fees Payment') }}</td>
                            <td class="py-3 text-right font-mono text-xs">{{ $receiptData['payment']->reference_number ?? '—' }}</td>
                            <td class="py-3 text-right text-xs font-sans">{{ $receiptData['payment']->payment_method }}</td>
                            <td class="py-3 text-right font-bold text-green-600 dark:text-green-400 font-mono">{{ number_format((float) $receiptData['payment']->amount_paid, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="grid grid-cols-2 gap-4 text-sm border-t pt-4 border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400 italic font-sans whitespace-pre-line leading-relaxed">
                        {{ $receiptData['settings']['footer_note'] }}
                    </div>
                    <div class="space-y-1.5 text-right font-sans">
                        <div class="flex justify-between text-xs"><span class="text-gray-500">{{ __('Gross Fees:') }}</span><span class="font-medium text-gray-800 dark:text-gray-200 font-mono">{{ number_format((float) $receiptData['breakdown']['gross_fees'], 2) }}</span></div>
                        @if ((float) $receiptData['breakdown']['discount'] > 0)
                            <div class="flex justify-between text-xs text-green-600 dark:text-green-400"><span>{{ __('Discount:') }}</span><span class="font-mono">-{{ number_format((float) $receiptData['breakdown']['discount'], 2) }}</span></div>
                        @endif
                        <div class="flex justify-between text-xs"><span class="text-gray-500">{{ __('Net Due:') }}</span><span class="font-medium text-gray-800 dark:text-gray-200 font-mono">{{ number_format((float) $receiptData['breakdown']['net_bill'], 2) }}</span></div>
                        <div class="flex justify-between text-xs border-t pt-1 border-gray-200 dark:border-gray-700"><span class="text-gray-500">{{ __('Total Paid:') }}</span><span class="font-bold text-green-600 dark:text-green-400 font-mono">{{ number_format((float) $receiptData['breakdown']['amount_paid'], 2) }}</span></div>
                        <div class="flex justify-between text-sm font-extrabold border-t pt-1.5 border-gray-300 dark:border-gray-600"><span class="text-indigo-600 dark:text-indigo-400">{{ __('Balance:') }}</span><span class="text-indigo-600 dark:text-indigo-400 font-mono">{{ number_format((float) $receiptData['breakdown']['balance'], 2) }}</span></div>
                    </div>
                </div>

                <!-- Custom Stamp & Signature Block -->
                @if ($receiptData['settings']['show_signature'] || $receiptData['settings']['show_stamp'])
                    <div class="grid grid-cols-2 gap-4 pt-6 mt-6 border-t border-gray-150 dark:border-gray-700">
                        <div>
                            @if ($receiptData['settings']['show_stamp'])
                                <div class="w-20 h-20 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded flex items-center justify-center text-[9px] text-gray-400 font-semibold uppercase tracking-wider select-none font-sans">
                                    {{ __('School Stamp') }}
                                </div>
                            @endif
                        </div>
                        <div class="flex items-end justify-end">
                            @if ($receiptData['settings']['show_signature'])
                                <div class="text-center">
                                    <div class="w-32 border-b border-dotted border-gray-300 dark:border-gray-600 mb-1"></div>
                                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider font-sans">{{ __('Authorized Sign') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <x-slot:footer>
                <div class="flex justify-between w-full">
                    <button type="button" onclick="printReceipt()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-print"></i> {{ __('Print') }}
                    </button>
                    <button type="button" wire:click="closeReceipt" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        {{ __('Close') }}
                    </button>
                </div>
            </x-slot:footer>
        @endif
    </x-college.modal>

    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #print-receipt, #print-receipt * {
                visibility: visible !important;
            }
            #print-receipt {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                border: none !important;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                padding: 1.5rem !important;
            }
        }
    </style>
    <script>
        function printReceipt() {
            window.print();
        }
    </script>
</div>
