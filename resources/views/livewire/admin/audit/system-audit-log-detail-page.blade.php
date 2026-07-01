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

            <!-- Affected Auditable Entity Card (Now positioned Second) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-150 dark:border-gray-700 pb-3">
                    <i class="fa-solid fa-cube text-purple-600"></i>
                    {{ __('Affected Entity / Audit Target') }}
                </h3>

                @if ($resolvedInvoice)
                    <!-- Premium Live Invoice View -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                                <i class="fa-solid fa-file-invoice mr-1.5"></i>{{ __('Live Invoice Summary') }}
                            </h4>
                            @php
                                $invBadge = match($resolvedInvoice->status) {
                                    'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-250',
                                    'partially_paid' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-250',
                                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border-rose-250',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700/50 dark:text-gray-300 border-gray-250',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-3xs font-bold uppercase tracking-wider {{ $invBadge }}">
                                {{ str_replace('_', ' ', $resolvedInvoice->status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700">
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Invoice Number') }}</span>
                                <div class="text-xs font-bold text-gray-900 dark:text-white font-mono mt-0.5">{{ $resolvedInvoice->invoice_number }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Vendor / Supplier') }}</span>
                                <div class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">{{ $resolvedInvoice->vendor_name }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Invoice Date') }}</span>
                                <div class="text-xs font-bold text-gray-800 dark:text-gray-200 mt-0.5">{{ $resolvedInvoice->invoice_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Total Bill') }}</span>
                                <div class="text-xs font-bold text-gray-950 dark:text-white mt-0.5">GHC {{ number_format((float) $resolvedInvoice->amount, 2) }}</div>
                            </div>
                        </div>

                        <!-- Invoice Line Items -->
                        <div class="overflow-x-auto rounded-lg border border-gray-150 dark:border-gray-700">
                            <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                                    <tr>
                                        <th class="px-4 py-2">{{ __('Product Name') }}</th>
                                        <th class="px-4 py-2">{{ __('SKU') }}</th>
                                        <th class="px-4 py-2 text-center">{{ __('Quantity') }}</th>
                                        <th class="px-4 py-2 text-right">{{ __('Unit Price') }}</th>
                                        <th class="px-4 py-2 text-right">{{ __('Total Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($resolvedInvoice->items as $activeItem)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/10">
                                            <td class="px-4 py-2 font-bold text-gray-900 dark:text-white">
                                                {{ $activeItem->product->name }}
                                            </td>
                                            <td class="px-4 py-2 font-mono text-purple-600 dark:text-purple-400">
                                                {{ $activeItem->product->sku }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                {{ $activeItem->quantity }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                GHC {{ number_format((float) $activeItem->unit_price, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white">
                                                GHC {{ number_format((float) $activeItem->total_amount, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-450 italic">
                                                {{ __('No line items defined for this invoice.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Expenditure / Payment History (if paid/partially paid) -->
                        @if ($resolvedInvoice->expenditures->count() > 0)
                            <div class="space-y-2 pt-2">
                                <h5 class="text-[11px] font-bold text-gray-455 uppercase tracking-wider">
                                    <i class="fa-solid fa-receipt mr-1"></i>{{ __('Recorded Payment Operations') }}
                                </h5>
                                <div class="overflow-x-auto rounded-lg border border-gray-150 dark:border-gray-750">
                                    <table class="w-full border-collapse text-left text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                                            <tr>
                                                <th class="px-4 py-1.5">{{ __('Expense Reference') }}</th>
                                                <th class="px-4 py-1.5">{{ __('Payment Date') }}</th>
                                                <th class="px-4 py-1.5">{{ __('Payment Method') }}</th>
                                                <th class="px-4 py-1.5 text-right">{{ __('Amount Paid') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($resolvedInvoice->expenditures as $payment)
                                                <tr>
                                                    <td class="px-4 py-2 font-mono font-semibold text-gray-900 dark:text-white">{{ $payment->expense_number }}</td>
                                                    <td class="px-4 py-2">{{ $payment->payment_date->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2">{{ $payment->payment_method }}</td>
                                                    <td class="px-4 py-2 text-right font-bold text-emerald-600 dark:text-emerald-400">GHC {{ number_format((float) $payment->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 pt-2">
                            <a
                                href="{{ route('admin.finance.invoices') }}"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>{{ __('Go to Invoices Dashboard') }}
                            </a>
                        </div>
                    </div>
                @elseif ($resolvedStudent)
                    <!-- Premium Live Student Card -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                            <i class="fa-solid fa-user-graduate mr-1.5"></i>{{ __('Live Student Profile') }}
                        </h4>

                        <div class="flex flex-col sm:flex-row gap-4 items-start bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-bold text-xl shadow-sm">
                                {{ strtoupper(substr($resolvedStudent->firstname, 0, 1) . substr($resolvedStudent->lastname, 0, 1)) }}
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full text-xs">
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Student Name') }}</span>
                                    <div class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">{{ $resolvedStudent->firstname }} {{ $resolvedStudent->lastname }}</div>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Index Number') }}</span>
                                    <div class="text-xs font-bold text-purple-600 dark:text-purple-400 font-mono mt-0.5">{{ $resolvedStudent->index_number }}</div>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Gender & Nationality') }}</span>
                                    <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ ucfirst($resolvedStudent->gender) }} / {{ $resolvedStudent->nationality }}</div>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Phone Number') }}</span>
                                    <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $resolvedStudent->phone_number }}</div>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Assigned Hall') }}</span>
                                    <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $resolvedStudent->hall?->name ?? __('No Hall') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a
                                href="{{ route('admin.students.show', $resolvedStudent->index_number) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>{{ __('View Full Student Profile') }}
                            </a>
                        </div>
                    </div>
                @elseif ($resolvedMemo)
                    <!-- Premium Live Memo Card -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                            <i class="fa-solid fa-envelope-open-text mr-1.5"></i>{{ __('Live Memo Document') }}
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700 text-xs">
                            <div class="sm:col-span-3">
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Subject / Title') }}</span>
                                <div class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $resolvedMemo->title }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Confidentiality Level') }}</span>
                                <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 mt-1 border border-blue-200/50">
                                    {{ ucfirst($resolvedMemo->confidentiality_level) }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Document Status') }}</span>
                                <span class="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 mt-1 border border-amber-200/50">
                                    {{ ucfirst(str_replace('_', ' ', $resolvedMemo->status)) }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Route Sequentially') }}</span>
                                <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-1">{{ $resolvedMemo->route_sequentially ? __('Yes') : __('No') }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a
                                href="{{ route('admin.memos.show', $resolvedMemo->id) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>{{ __('Open Memo Detail View') }}
                            </a>
                        </div>
                    </div>
                @elseif ($resolvedLeaveRequest)
                    <!-- Premium Live Leave Request Card -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                            <i class="fa-solid fa-calendar-minus mr-1.5"></i>{{ __('Live Leave Request') }}
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700 text-xs">
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Staff Member') }}</span>
                                <div class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">{{ $resolvedLeaveRequest->user?->name }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Leave Type') }}</span>
                                <div class="text-xs font-bold text-purple-600 dark:text-purple-400 mt-0.5">{{ $resolvedLeaveRequest->staffLeaveType?->name }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Duration / Requested Days') }}</span>
                                <div class="text-xs font-bold text-gray-900 dark:text-white mt-0.5">{{ $resolvedLeaveRequest->requested_days }} {{ __('Days') }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Start Date') }}</span>
                                <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $resolvedLeaveRequest->start_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('End Date') }}</span>
                                <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $resolvedLeaveRequest->end_date->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Approval Status') }}</span>
                                <span class="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 mt-1 border border-amber-200/50">
                                    {{ ucfirst($resolvedLeaveRequest->status) }}
                                </span>
                            </div>
                            <div class="sm:col-span-3">
                                <span class="block text-[10px] uppercase tracking-wider text-gray-450">{{ __('Reason / Remarks') }}</span>
                                <div class="text-xs text-gray-650 dark:text-gray-350 mt-0.5 italic">"{{ $resolvedLeaveRequest->reason }}"</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a
                                href="{{ route('admin.staff.leaves') }}"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-purple-700 transition"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>{{ __('Go to Leave Management') }}
                            </a>
                        </div>
                    </div>
                @elseif ($isTargetDeleted)
                    <!-- Red Alert indicating Target Deleted -->
                    <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50/50 p-4 dark:border-red-900/50 dark:bg-red-950/20">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-750 dark:bg-red-950/60 dark:text-red-400">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </span>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-red-800 dark:text-red-300">
                                {{ __('Target Record Permanently Deleted') }}
                            </h4>
                            <p class="text-xxs text-red-700 dark:text-red-400">
                                {{ __('The linked target model (:class #:id) has been removed from the live system. Only snapshot metadata and historical logs are preserved for security and diagnostics.', ['class' => class_basename($log->auditable_type), 'id' => $log->auditable_id]) }}
                            </p>
                        </div>
                    </div>
                @elseif ($log->auditable_type)
                    <!-- Generic Target Model Card -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-gray-50/50 dark:bg-gray-900/30 p-4 rounded-lg border border-gray-150 dark:border-gray-700/60 font-medium">
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1">
                                {{ __('Target Model Class') }}
                            </span>
                            <span class="font-mono text-gray-900 dark:text-white break-all">
                                {{ $log->auditable_type }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-1">
                                {{ __('Target Database ID') }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200/50">
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

            <!-- Changed Data & Metadata Card (Now positioned Third, collapsible for live objects) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-database text-purple-600"></i>
                    {{ __('Captured Data & Structural Changes') }}
                </h3>

                @php
                    $hasLiveTarget = $resolvedInvoice || $resolvedStudent || $resolvedMemo || $resolvedLeaveRequest;
                @endphp

                @if ($hasLiveTarget)
                    <!-- Note for Live Target -->
                    <div class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20 text-xxs text-indigo-700 dark:text-indigo-400 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>{{ __('This target entity is live. You can review its full live properties in the card above.') }}</span>
                    </div>

                    <!-- Collapsible Advanced Metadata Accordion for Premium UX -->
                    <details class="group border border-gray-100 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-900/10 p-3">
                        <summary class="flex items-center justify-between cursor-pointer text-xs font-semibold text-gray-650 dark:text-gray-400 hover:text-purple-600 select-none">
                            <span>{{ __('Show Advanced Database Audit Metadata') }}</span>
                            <span class="transition group-open:rotate-180">
                                <i class="fa-solid fa-chevron-down text-xxs"></i>
                            </span>
                        </summary>
                        <div class="mt-4 pt-3 border-t border-gray-150 dark:border-gray-700/60 space-y-4">
                            @include('livewire.admin.audit.partials.metadata-tables')
                        </div>
                    </details>
                @else
                    <!-- Display metadata tables directly for deleted/non-live entities -->
                    <div class="space-y-4">
                        @include('livewire.admin.audit.partials.metadata-tables')
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
