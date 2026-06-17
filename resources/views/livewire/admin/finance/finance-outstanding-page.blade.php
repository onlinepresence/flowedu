<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-3">
        <button type="button" wire:click="exportToExcel" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
            <i class="fa-solid fa-file-excel text-green-600"></i>
            {{ __('Export Excel') }}
        </button>
        <button type="button" wire:click="openPrintModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <i class="fa-solid fa-print"></i>
            {{ __('Print Debtors List') }}
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-circle-exclamation text-red-500 mr-2"></i>{{ __('Total Arrears') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($totalOutstanding, 2) }}</p>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-users-slash text-amber-500 mr-2"></i>{{ __('Students in Arrears') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $studentsInArrears }}</p>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-calculator text-indigo-500 mr-2"></i>{{ __('Average Debt') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($averageDebt, 2) }}</p>
        </div>
    </div>

    <!-- Filters Section -->
    <x-college.filter-card cols="4">
        <div>
            <x-input-label for="search" :value="__('Search Student')" />
            <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name or Index Number') }}" wire:model.live.debounce.300ms="search" />
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
            <x-input-label for="filterProgramId" :value="__('Program')" />
            <select id="filterProgramId" wire:model.live="filterProgramId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Programs') }}</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="filterLevel" :value="__('Class Level')" />
            <select id="filterLevel" wire:model.live="filterLevel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Levels') }}</option>
                <option value="100">{{ __('Level 100') }}</option>
                <option value="200">{{ __('Level 200') }}</option>
                <option value="300">{{ __('Level 300') }}</option>
                <option value="400">{{ __('Level 400') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program & Department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount Paid') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Balance Due') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="out-{{ $row->id }}">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $row->lastname }}, {{ $row->othernames }}
                                @if ($row->student)
                                    <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->student->index_number }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->student?->program?->name ?? '—' }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->department?->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row->class_level }}</td>
                            <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap font-medium">{{ number_format((float) $row->amount_paid, 2) }}</td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ (float) $row->balance > 1000 ? 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20' : 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ (float) $row->balance > 1000 ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                                    {{ number_format((float) $row->balance, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                @if ($row->student)
                                    <button type="button" wire:click="initRecordPayment({{ $row->student->id }})" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 font-semibold" title="{{ __('Record Payment') }}">
                                        <i class="fa-solid fa-cash-register"></i>
                                        {{ __('Collect Fee') }}
                                    </button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No outstanding fees found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $rows->links() }}
        </div>
    </div>

    <!-- Collect Arrears Modal -->
    <x-college.modal name="record-outstanding-modal" :title="__('Collect Student Arrears')" maxWidth="lg" livewireSynced>
        <form wire:submit.prevent="recordPayment" class="space-y-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ __('Student') }}</span>
                <div class="font-bold text-gray-900 dark:text-white mt-0.5 text-lg">{{ $selectedStudentName }}</div>
            </div>

            @if ($fee_structure_id !== '')
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
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
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
                        <x-input-error :messages="$errors->get('reference_number')" class="mt-1" />
                    </div>
                </div>
            @else
                <div class="rounded-md border border-amber-250 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ __('No fee structure is set for this student in the current session. Please set up a fee structure first.') }}
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" x-on:click="$dispatch('close-modal', 'record-outstanding-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                @if ($fee_structure_id !== '')
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('Save Payment') }}
                    </button>
                @endif
            </div>
        </form>
    </x-college.modal>

    <!-- Print Debtors List Modal -->
    <x-college.modal name="print-debtors-modal" :title="__('Print Debtors List Options')" maxWidth="md" livewireSynced>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Select how you would like to format the printed outstanding debtors list.') }}
            </p>
            <div>
                <x-input-label for="printGrouping" :value="__('Formatting Preference')" />
                <select id="printGrouping" wire:model="printGrouping" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="all_one_sheet">{{ __('All on one continuous sheet (separated by spaces)') }}</option>
                    <option value="grouped_by_class">{{ __('Grouped per Class (Level 100, 200, etc. on new pages)') }}</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="closePrintModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ __('Cancel') }}
                </button>
                <button type="button" onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">
                    <i class="fa-solid fa-print mr-1"></i>{{ __('Print List') }}
                </button>
            </div>
        </div>
    </x-college.modal>

    <!-- Print-only Styles & Layout Containers -->
    <div id="print-debtors-area" class="hidden print:block p-8 bg-white text-black min-h-screen">
        <div class="border-b-2 border-gray-950 pb-4 mb-6 text-center">
            <h1 class="text-2xl font-black uppercase tracking-wide text-gray-900">{{ __('COLLEGE OF EDUCATION') }}</h1>
            <p class="text-sm font-semibold text-gray-600">{{ __('OFFICIAL OUTSTANDING DEBTORS LIST') }}</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Date Generated: :date', ['date' => now()->format('Y-m-d H:i')]) }}
                @if ($filterSessionId)
                    | {{ __('Academic Year: :year', ['year' => \App\Models\AcademicSession::find((int)$filterSessionId)?->name]) }}
                @endif
            </p>
        </div>

        @if ($printGrouping === 'grouped_by_class')
            <!-- Grouped by Class Level -->
            @foreach ($printData as $level => $debtors)
                <div class="class-section break-after-page mb-8">
                    <h2 class="text-lg font-bold text-gray-900 border-b border-gray-400 pb-1 mb-3">{{ __('LEVEL :level DEBTORS', ['level' => $level]) }}</h2>
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800 font-bold">
                                <th class="py-2">{{ __('Index No.') }}</th>
                                <th class="py-2">{{ __('Student Name') }}</th>
                                <th class="py-2">{{ __('Program') }}</th>
                                <th class="py-2 text-right">{{ __('Paid') }}</th>
                                <th class="py-2 text-right">{{ __('Owed Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($debtors as $row)
                                <tr>
                                    <td class="py-2 font-mono">{{ $row->student?->index_number }}</td>
                                    <td class="py-2 font-semibold">{{ $row->lastname }}, {{ $row->othernames }}</td>
                                    <td class="py-2">{{ $row->student?->program?->name }}</td>
                                    <td class="py-2 text-right">{{ number_format((float) $row->amount_paid, 2) }}</td>
                                    <td class="py-2 text-right font-bold text-red-600">{{ number_format((float) $row->balance, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-900 font-black">
                                <td colspan="3" class="py-2 text-right uppercase">{{ __('Subtotal Level :level', ['level' => $level]) }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $debtors->sum('amount_paid'), 2) }}</td>
                                <td class="py-2 text-right text-red-700">{{ number_format((float) $debtors->sum('balance'), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            <!-- Continuous Sheet with Spaces -->
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-900 font-bold">
                        <th class="py-2">{{ __('Level') }}</th>
                        <th class="py-2">{{ __('Index No.') }}</th>
                        <th class="py-2">{{ __('Student Name') }}</th>
                        <th class="py-2">{{ __('Program') }}</th>
                        <th class="py-2 text-right">{{ __('Paid') }}</th>
                        <th class="py-2 text-right">{{ __('Owed Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastLevel = null; @endphp
                    @foreach ($allDebtors->sortBy('class_level') as $row)
                        @if ($lastLevel !== null && $lastLevel !== $row->class_level)
                            <!-- Visual Space Separator -->
                            <tr class="bg-gray-100"><td colspan="6" class="h-6 py-2 font-bold text-center border-y border-gray-300 tracking-widest text-gray-600 uppercase">{{ __('LEVEL :level SEPARATOR', ['level' => $row->class_level]) }}</td></tr>
                        @endif
                        @php $lastLevel = $row->class_level; @endphp
                        <tr class="border-b border-gray-200">
                            <td class="py-2 font-bold">{{ $row->class_level }}</td>
                            <td class="py-2 font-mono">{{ $row->student?->index_number }}</td>
                            <td class="py-2 font-semibold">{{ $row->lastname }}, {{ $row->othernames }}</td>
                            <td class="py-2">{{ $row->student?->program?->name }}</td>
                            <td class="py-2 text-right">{{ number_format((float) $row->amount_paid, 2) }}</td>
                            <td class="py-2 text-right font-bold text-red-600">{{ number_format((float) $row->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-gray-900 font-black text-sm">
                        <td colspan="4" class="py-3 text-right uppercase">{{ __('Grand Total Outstanding') }}</td>
                        <td class="py-3 text-right">{{ number_format((float) $allDebtors->sum('amount_paid'), 2) }}</td>
                        <td class="py-3 text-right text-red-700">{{ number_format((float) $allDebtors->sum('balance'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #print-debtors-area, #print-debtors-area * {
                visibility: visible !important;
            }
            #print-debtors-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                color: black !important;
                display: block !important;
                padding: 1.5rem !important;
            }
            .break-after-page { page-break-after: always !important; break-after: page !important; }
            table { width: 100% !important; page-break-inside: auto !important; }
            tr { page-break-inside: avoid !important; page-break-after: auto !important; }
        }
    </style>
</div>
