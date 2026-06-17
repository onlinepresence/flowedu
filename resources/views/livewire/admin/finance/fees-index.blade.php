<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header tabs navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button" wire:click="switchTab('payments')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'payments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-receipt mr-2"></i>{{ __('Recent Payments') }}
            </button>
            <button type="button" wire:click="switchTab('structures')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'structures' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-list-check mr-2"></i>{{ __('Fee Structures') }}
            </button>
            <button type="button" wire:click="switchTab('components')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'components' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-gears mr-2"></i>{{ __('Fee Component Settings') }}
            </button>
            <button type="button" wire:click="switchTab('receipt')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'receipt' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-file-invoice-dollar mr-2"></i>{{ __('Receipt Settings') }}
            </button>
            <button type="button" wire:click="switchTab('requests')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'requests' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-envelope-open-text mr-2"></i>{{ __('Breakdown Requests') }}
                @if ($pendingRequestsCount > 0)
                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-300 font-mono">
                        {{ $pendingRequestsCount }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    @if ($activeTab === 'payments')
        <!-- Summary Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-circle-check text-green-500 mr-2"></i>{{ __('Total Payments Received') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($totalPaid, 2) }}</p>
            </div>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><i class="fa-solid fa-circle-exclamation text-amber-500 mr-2"></i>{{ __('Total Outstanding Balance') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($totalBalance, 2) }}</p>
            </div>
        </div>

        <!-- Filters Section -->
        <x-college.filter-card cols="2">
            <div>
                <x-input-label for="search" :value="__('Search Student')" />
                <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name or Index Number') }}" wire:model.live.debounce.300ms="search" />
            </div>
            <div>
                <x-input-label for="filterLevel" :value="__('Class Level')" />
                <select id="filterLevel" wire:model.live="filterLevel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="">{{ __('All levels') }}</option>
                    <option value="100">{{ __('Level 100') }}</option>
                    <option value="200">{{ __('Level 200') }}</option>
                    <option value="300">{{ __('Level 300') }}</option>
                    <option value="400">{{ __('Level 400') }}</option>
                </select>
            </div>
        </x-college.filter-card>

        <!-- Recent Payments Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Recent Payments Log') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Live transactions history log for all received fee payments.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Program & Academic Year') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Payment Method') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Reference & Date') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Amount Paid') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($payments as $row)
                            <tr wire:key="p-{{ $row->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400">
                                            {{ strtoupper(substr($row->student?->lastname ?? 'S', 0, 1)) }}{{ strtoupper(substr($row->student?->firstname ?? 'T', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">{{ $row->student?->lastname }}, {{ $row->student?->firstname }}</span>
                                            @if ($row->student)
                                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->student->index_number }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">{{ $row->feeStructure?->program?->name ?? '—' }}</span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->feeStructure?->session?->name ?? '—' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $row->payment_method === 'Cash' ? 'bg-green-50 text-green-700 ring-green-650/10 dark:bg-green-400/10 dark:text-green-400' : 'bg-blue-50 text-blue-700 ring-blue-650/10 dark:bg-blue-400/10 dark:text-blue-400' }}">
                                        {{ $row->payment_method }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-mono text-xs">{{ $row->reference_number ?? '—' }}</span>
                                    <div class="text-xs text-gray-500">{{ $row->payment_date }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ number_format((float) $row->amount_paid, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No fee payments log found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $payments->links() }}
            </div>
        </div>
    @endif

    @if ($activeTab === 'requests')
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Student Fee Breakdown Requests') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Manage requests from students for detailed itemized fee breakdowns.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Program & Level') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Academic Year') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($requests as $row)
                            <tr wire:key="req-{{ $row->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400">
                                            {{ strtoupper(substr($row->student?->lastname ?? 'S', 0, 1)) }}{{ strtoupper(substr($row->student?->firstname ?? 'T', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">{{ $row->student?->lastname }}, {{ $row->student?->firstname }}</span>
                                            @if ($row->student)
                                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->student->index_number }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">{{ $row->feeStructure?->program?->name ?? '—' }}</span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Level :level', ['level' => $row->feeStructure?->level]) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono">
                                    {{ $row->feeStructure?->session?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                                    @if ($row->status === 'pending')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400">
                                            {{ __('Pending') }}
                                        </span>
                                    @elseif ($row->status === 'approved')
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400">
                                            {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-950/30 dark:text-rose-450">
                                            {{ __('Declined') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if ($row->status === 'pending')
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="approveRequest({{ $row->id }})" class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-500">
                                                <i class="fa-solid fa-check"></i>
                                                {{ __('Approve') }}
                                            </button>
                                            <button type="button" wire:click="rejectRequest({{ $row->id }})" class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-500">
                                                <i class="fa-solid fa-xmark"></i>
                                                {{ __('Decline') }}
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Resolved by :name on :date', [
                                                'name' => $row->resolver?->name ?? __('System'),
                                                'date' => $row->resolved_at?->format('Y-m-d H:i') ?? '—'
                                            ]) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No fee breakdown requests found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $requests->links() }}
            </div>
        </div>
    @endif

    @if ($activeTab === 'structures')
        <!-- Header Actions -->
        <div class="flex flex-wrap gap-2 justify-end">
            <button type="button" wire:click="openStructureModal" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <i class="fa-solid fa-plus"></i>
                {{ __('New Fee Structure') }}
            </button>
        </div>

        <!-- Fee Structures Table Section -->
        @if ($showStructuresTableSection)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Fee structures') }}</h2>
                    <div class="w-full sm:w-72">
                        <select wire:model.live="filterSessionId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">{{ __('All academic years') }}</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name ?? __('Session #:id', ['id' => $session->id]) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Level') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Academic year') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($structures as $structure)
                                <tr wire:key="structure-{{ $structure->id }}">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $structure->program?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ __('Level :level', ['level' => $structure->level]) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $structure->session?->name ?? __('Session #:id', ['id' => $structure->session_id]) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ number_format((float) $structure->total_amount, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <button type="button" wire:click="editStructure({{ $structure->id }})" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" title="{{ __('Edit') }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" wire:click="deleteStructure({{ $structure->id }})" wire:confirm="{{ __('Are you sure you want to delete this fee structure?') }}" class="text-red-600 hover:text-red-500 dark:text-red-400" title="{{ __('Delete') }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No fee structures found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    {{ $structures->links() }}
                </div>
            </div>
            <!-- Fee Structure Setup Modal -->
            <x-college.modal name="fee-structure-modal" :title="$editingStructureId ? __('Edit Fee Structure') : __('New Fee Structure')" maxWidth="3xl" livewireSynced>
                <form wire:submit="createStructureFromProgram" class="space-y-6">
                    <!-- Dropdown Fields -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div>
                            <x-input-label for="program_id" :value="__('Program')" />
                            <select id="program_id" wire:model.live="program_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select program') }}</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="level" :value="__('Level')" />
                            <select id="level" wire:model="level" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select level') }}</option>
                                <option value="100">{{ __('Level 100') }}</option>
                                <option value="200">{{ __('Level 200') }}</option>
                                <option value="300">{{ __('Level 300') }}</option>
                                <option value="400">{{ __('Level 400') }}</option>
                            </select>
                            @error('level') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="session_id" :value="__('Academic Year')" />
                            <select id="session_id" wire:model="session_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500" {{ $editingStructureId ? 'disabled' : '' }}>
                                @if ($editingStructureId)
                                    @foreach ($sessions as $session)
                                        @if ((string)$session->id === (string)$session_id)
                                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                                        @endif
                                    @endforeach
                                @else
                                    <option value="">{{ __('Select academic year') }}</option>
                                    @foreach ($sessions as $session)
                                        @if ($session->is_current)
                                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                            @error('session_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Program Base Cost / Auto Fill Alert Card -->
                    @if ($program_id !== '')
                        @php
                            $selectedProgCost = \App\Models\Program::find((int) $program_id)?->cost;
                        @endphp
                        @if ($selectedProgCost !== null)
                            <div class="rounded-xl bg-indigo-50/50 p-4 text-sm text-indigo-800 dark:bg-indigo-950/20 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-950 flex flex-wrap justify-between items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-info text-indigo-500 text-base"></i>
                                    <span>{{ __('Base Program Cost:') }} <strong class="font-bold text-lg text-indigo-950 dark:text-indigo-200">GHS {{ number_format($selectedProgCost, 2) }}</strong></span>
                                </div>
                                <button type="button" wire:click="fillFromProgramCost" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 px-3.5 py-2 text-xs font-bold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-900 transition-all">
                                    <i class="fa-solid fa-magic-wand-sparkles"></i>
                                    {{ __('Auto-Fill Default Split') }}
                                </button>
                            </div>
                        @endif
                    @endif

                    <!-- Fee Breakdown Input Fields -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700 pb-1.5">{{ __('Fee Breakdown') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <x-input-label for="tuition_fee" :value="__('Tuition Fee')" />
                                <x-text-input id="tuition_fee" type="number" min="0" step="0.01" class="mt-1 block w-full text-sm" wire:model="tuition_fee" />
                            </div>
                            @foreach ($extraFeeCatalog as $key => $label)
                                @if ($extraFeeEnabled[$key] ?? false)
                                    <div wire:key="field-{{ $key }}">
                                        <x-input-label :for="$key" :value="$label" />
                                        <x-text-input :id="$key" type="number" min="0" step="0.01" class="mt-1 block w-full text-sm" wire:model="{{ $key }}" />
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 justify-end">
                        <button type="button" x-on:click="$dispatch('close-modal', 'fee-structure-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 shadow-md">
                            {{ $editingStructureId ? __('Update Fee Structure') : __('Save Fee Structure') }}
                        </button>
                    </div>
                </form>
            </x-college.modal>
        @endif
    @endif

    @if ($activeTab === 'components')
        <!-- Fee Components Configuration Tab -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Fee Component Setup') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Select extra fee components charged by the college. Enabled components will show up in the Fee Structure configuration form.') }}</p>
            </div>
            <div class="space-y-4 p-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($extraFeeCatalog as $key => $label)
                        <div class="flex items-center justify-between rounded-xl border border-gray-250 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-850">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="extraFeeEnabled.{{ $key }}" class="sr-only peer" />
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="saveExtraFeeConfiguration" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>{{ __('Save Component Setup') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($activeTab === 'receipt')
        <!-- Receipt Customization Tab -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Receipt Customization') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Customize the appearance and layout details of printable student fee payment receipts.') }}</p>
            </div>
            
            <div class="grid gap-8 p-6 lg:grid-cols-2">
                <!-- Settings Form -->
                <form wire:submit="saveReceiptSettings" class="space-y-4">
                    <div>
                        <x-input-label for="receiptHeaderTitle" :value="__('Receipt Header Title (School Name)')" />
                        <x-text-input id="receiptHeaderTitle" type="text" class="mt-1 block w-full text-sm font-sans" wire:model="receiptHeaderTitle" placeholder="e.g. College of Education" />
                        <x-input-error :messages="$errors->get('receiptHeaderTitle')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="receiptHeaderSubtitle" :value="__('Receipt Subtitle / Document Type')" />
                        <x-text-input id="receiptHeaderSubtitle" type="text" class="mt-1 block w-full text-sm font-sans" wire:model="receiptHeaderSubtitle" placeholder="e.g. Official Tuition & Fees Payment Receipt" />
                        <x-input-error :messages="$errors->get('receiptHeaderSubtitle')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="receiptContactInfo" :value="__('Office Contact / Address Info')" />
                        <x-text-input id="receiptContactInfo" type="text" class="mt-1 block w-full text-sm font-sans" wire:model="receiptContactInfo" placeholder="e.g. +233 24 000 0000 | finance@college.edu.gh" />
                        <x-input-error :messages="$errors->get('receiptContactInfo')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="receiptFooterNote" :value="__('Receipt Footer Note')" />
                        <textarea id="receiptFooterNote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-sans" rows="3" wire:model="receiptFooterNote" placeholder="e.g. Thank you for your payment. Keep this receipt safe."></textarea>
                        <x-input-error :messages="$errors->get('receiptFooterNote')" class="mt-1" />
                    </div>

                    <div class="space-y-4 border-t pt-4 border-gray-150 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 block">{{ __('Show Signature Box') }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Renders an authorized signature dotted line.') }}</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="receiptShowSignature" class="sr-only peer" />
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 block">{{ __('Show Stamp Area') }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Renders a dashed box for school seal/stamp placement.') }}</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="receiptShowStamp" class="sr-only peer" />
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">
                            <i class="fa-solid fa-floppy-disk mr-2"></i>{{ __('Save Receipt Settings') }}
                        </button>
                    </div>
                </form>

                <!-- Live Preview Pane -->
                <div class="flex flex-col justify-center rounded-xl bg-gray-50 p-6 dark:bg-gray-900/40 border border-gray-150 dark:border-gray-850">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4 text-center">{{ __('Live Printable Preview') }}</h3>
                    <div class="border rounded-lg p-6 bg-white dark:bg-gray-900 shadow-sm max-w-md mx-auto w-full border-gray-200 dark:border-gray-700">
                        <div class="text-center border-b pb-4 mb-4 border-gray-200 dark:border-gray-750">
                            <h4 class="text-sm font-extrabold text-indigo-700 dark:text-indigo-400 uppercase tracking-wide">{{ $receiptHeaderTitle ?: __('College of Education') }}</h4>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $receiptHeaderSubtitle ?: __('Official Tuition & Fees Payment Receipt') }}</p>
                            @if($receiptContactInfo)
                                <p class="text-[9px] text-gray-400 mt-1 font-medium">{{ $receiptContactInfo }}</p>
                            @endif
                        </div>
                        
                        <!-- Dummy Transaction Data -->
                        <div class="space-y-1.5 text-[11px] border-b pb-4 mb-4 border-gray-100 dark:border-gray-750 font-mono">
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Receipt No:') }}</span><span class="font-bold text-gray-800 dark:text-gray-200">REC-1029-{{ date('ymd') }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Student:') }}</span><span class="font-bold text-gray-800 dark:text-gray-200">KWAME MENSAH</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Amount Paid:') }}</span><span class="font-extrabold text-green-600">1,500.00 GHS</span></div>
                        </div>
                        
                        <!-- Footer and Signatures -->
                        <div class="space-y-4 font-sans">
                            @if($receiptFooterNote)
                                <p class="text-[10px] text-gray-500 italic text-center dark:text-gray-400 leading-relaxed font-sans">{{ $receiptFooterNote }}</p>
                            @endif
                            
                            <div class="flex justify-between items-end pt-2">
                                <div>
                                    @if($receiptShowStamp)
                                        <div class="w-16 h-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded flex items-center justify-center text-[8px] text-gray-400 font-semibold uppercase tracking-wider select-none font-sans">
                                            {{ __('Stamp') }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    @if($receiptShowSignature)
                                        <div class="text-center">
                                            <div class="w-28 border-b border-dotted border-gray-300 dark:border-gray-600 mb-1"></div>
                                            <span class="text-[9px] text-gray-400 font-bold tracking-tight uppercase font-sans">{{ __('Authorized Sign') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

