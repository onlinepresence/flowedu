<div 
    x-data="{
        selectedSession: '',
        payments: @js($payments->map(fn($p) => [
            'id' => $p->id,
            'reference_number' => $p->reference_number,
            'amount_paid' => (float)$p->amount_paid,
            'payment_date' => $p->payment_date,
            'payment_method' => $p->payment_method,
            'session_id' => $p->feeStructure?->session_id,
            'session_name' => $p->feeStructure?->session?->name ?? '—',
            'program_name' => $p->feeStructure?->program?->name ?? '—',
        ])),
        get filteredPayments() {
            if (this.selectedSession === '') return this.payments;
            return this.payments.filter(p => String(p.session_id) === String(this.selectedSession));
        },
        get totalPaid() {
            return this.filteredPayments.reduce((sum, p) => sum + p.amount_paid, 0);
        },
        get count() {
            return this.filteredPayments.length;
        }
    }"
    class="mx-auto max-w-5xl space-y-6"
>
    <!-- Header tabs navigation / Top selectors -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ __('Payment History') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Track and review all tuition and fees payment transactions made.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Academic Year:') }}</span>
            <select 
                x-model="selectedSession"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs font-medium focus:ring-purple-500 focus:border-purple-500"
            >
                <option value="">{{ __('All Sessions') }}</option>
                @foreach ($sessions as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Summary metrics grid calculated dynamically by Alpine -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Total Paid -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Amount Paid') }}</span>
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-green-50 text-green-600 dark:bg-green-950/30 dark:text-green-400">
                    <i class="fa-solid fa-circle-check"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono" x-text="Number(totalPaid).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
            <span class="text-[10px] text-gray-400 font-mono">GHS</span>
        </div>

        <!-- Transaction Count -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Transactions') }}</span>
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400">
                    <i class="fa-solid fa-list-check"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white font-mono" x-text="count"></p>
            <span class="text-[10px] text-gray-400 font-mono">{{ __('Payments') }}</span>
        </div>
    </div>

    <!-- Payments List Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Reference') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Session') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payment Method') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount Paid') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="p in filteredPayments" :key="p.id">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-medium text-gray-900 dark:text-gray-100" x-text="p.reference_number || '—'"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300" x-text="p.session_name"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400" x-text="p.program_name"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <span 
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="p.payment_method === 'Cash' ? 'bg-green-50 text-green-700 ring-green-600/10 dark:bg-green-400/10 dark:text-green-400' : 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-400/10 dark:text-blue-400'"
                                    x-text="p.payment_method"
                                ></span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono" x-text="p.payment_date"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400 font-mono" x-text="Number(p.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' GHS'"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredPayments.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <span class="text-2xl text-gray-400 dark:text-gray-600"><i class="fa-solid fa-receipt"></i></span>
                                <span x-text="selectedSession === '' ? '{{ __('No payments recorded on this account yet.') }}' : '{{ __('No payments recorded for the selected academic session.') }}'"></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
