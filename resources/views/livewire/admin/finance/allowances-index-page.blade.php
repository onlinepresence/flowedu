<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Student Allowance Disbursements') }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Record and track monthly allowance stipends paid directly to student accounts.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openBulkAwardModal" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                <i class="fa-solid fa-users"></i>
                {{ __('Bulk Award Allowance') }}
            </button>
            <button type="button" wire:click="openAwardModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <i class="fa-solid fa-plus"></i>
                {{ __('Award Allowance') }}
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <x-college.filter-card cols="4">
        <div>
            <x-input-label for="recipientSearch" :value="__('Search Student')" />
            <x-text-input id="recipientSearch" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name or Index Number') }}" wire:model.live.debounce.300ms="recipientSearch" />
        </div>
        <div>
            <x-input-label for="recipientStatus" :value="__('Status')" />
            <select id="recipientStatus" wire:model.live="recipientStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="applied">{{ __('Applied (Pending)') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="active">{{ __('Active') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="programFilter" :value="__('Program')" />
            <select id="programFilter" wire:model.live="programFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Programs') }}</option>
                @foreach ($programs as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="levelFilter" :value="__('Year (Level)')" />
            <select id="levelFilter" wire:model.live="levelFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="">{{ __('All Years') }}</option>
                <option value="100">{{ __('Level 100') }}</option>
                <option value="200">{{ __('Level 200') }}</option>
                <option value="300">{{ __('Level 300') }}</option>
                <option value="400">{{ __('Level 400') }}</option>
            </select>
        </div>
    </x-college.filter-card>

    <!-- Recipients Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if (!empty($selectedIds))
            <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-950/30 border-b border-indigo-100 dark:border-indigo-900/50 px-6 py-3 text-sm text-indigo-800 dark:text-indigo-200">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-indigo-600 dark:text-indigo-400"></i>
                    <span><strong>{{ count($selectedIds) }}</strong> {{ __('allowance(s) selected') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="bulkApprove" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-500 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-sm">
                        <i class="fa-solid fa-check"></i>
                        {{ __('Approve Selected') }}
                    </button>
                    <button type="button" wire:click="bulkReject" class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-500 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-sm">
                        <i class="fa-solid fa-xmark"></i>
                        {{ __('Reject Selected') }}
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="w-10 px-6 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-indigo-650 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-650" />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Payment Record') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Award Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount Awarded') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($applications as $app)
                        <tr wire:key="app-{{ $app->id }}" class="{{ in_array((string)$app->id, $selectedIds, true) ? 'bg-indigo-50/30 dark:bg-indigo-950/10' : '' }}">
                            <td class="w-10 px-6 py-4">
                                @if ($app->status === 'applied')
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $app->id }}" class="rounded border-gray-300 text-indigo-650 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-650" />
                                @else
                                    <input type="checkbox" disabled class="rounded border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50 dark:border-gray-700 dark:bg-gray-800" />
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $app->student?->lastname }}, {{ $app->student?->firstname }}
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $app->student?->index_number ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300">
                                {{ $app->scholarship?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300 whitespace-nowrap">{{ $app->award_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-650 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $app->status === 'approved' || $app->status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' : ($app->status === 'rejected' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-550/10 dark:text-amber-400') }}">
                                    {{ ucfirst($app->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white font-extrabold whitespace-nowrap">GHS {{ number_format((float) $app->amount_awarded, 2) }}</td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap font-semibold">
                                @if ($app->status === 'applied')
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="updateApplicationStatus({{ $app->id }}, 'approved')" class="text-xs font-bold text-green-600 hover:text-green-500 dark:text-green-400">
                                            <i class="fa-solid fa-check mr-1"></i>{{ __('Approve') }}
                                        </button>
                                        <button type="button" wire:click="updateApplicationStatus({{ $app->id }}, 'rejected')" class="text-xs font-bold text-red-600 hover:text-red-500 dark:text-red-400">
                                            <i class="fa-solid fa-xmark mr-1"></i>{{ __('Reject') }}
                                        </button>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No student allowances awarded yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $applications->links() }}
        </div>
    </div>

    <!-- Award Allowance Modal -->
    <x-college.modal name="allowance-award-modal" :title="__('Award Allowance to Student')" maxWidth="lg" livewireSynced>
        <form wire:submit.prevent="awardScholarship" class="space-y-4">
            <!-- Student Autocomplete -->
            <div>
                <x-input-label for="awardStudentSearch" :value="__('Search & Select Student')" />
                <x-text-input id="awardStudentSearch" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Type student name or index number') }}" wire:model.live="awardStudentSearch" />
                <x-input-error :messages="$errors->get('award_student_id')" class="mt-1" />

                @if (!empty($searchedStudents))
                    <div class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:border-gray-700 dark:bg-gray-800 sm:text-sm">
                        @foreach ($searchedStudents as $std)
                            <button type="button" wire:click="selectAwardStudent({{ $std->id }})" class="w-full text-left px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-900 dark:text-white">
                                <span class="font-medium">{{ $std->lastname }}, {{ $std->firstname }}</span>
                                <span class="ml-2 font-mono text-xs text-gray-500">{{ $std->index_number }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($award_student_id !== '')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="award_amount" :value="__('Amount Awarded (GHS)')" />
                        <x-text-input id="award_amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full text-sm" wire:model="award_amount" />
                        <x-input-error :messages="$errors->get('award_amount')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="award_date" :value="__('Award Date')" />
                        <x-text-input id="award_date" type="date" class="mt-1 block w-full text-sm" wire:model="award_date" />
                        <x-input-error :messages="$errors->get('award_date')" class="mt-1" />
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" x-on:click="$dispatch('close-modal', 'allowance-award-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                @if ($award_student_id !== '')
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('Save Award') }}
                    </button>
                @endif
            </div>
        </form>
    </x-college.modal>

    <!-- Bulk Award Allowance Modal -->
    <x-college.modal name="allowance-bulk-award-modal" :title="__('Bulk Award Allowance to All Active Students')" maxWidth="lg" livewireSynced>
        <form wire:submit.prevent="bulkAwardScholarship" class="space-y-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-xs rounded-lg flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info mt-0.5 text-sm"></i>
                <div>
                    <span class="font-bold">{{ __('Notice:') }}</span>
                    {{ __('This action will create a pending award record (Applied status) for all current active, non-graduated students in the system. You can then review and manually approve payouts.') }}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="bulk_amount" :value="__('Allowance Amount (GHS)')" />
                    <x-text-input id="bulk_amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full text-sm" wire:model="bulk_amount" />
                    <x-input-error :messages="$errors->get('bulk_amount')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="bulk_date" :value="__('Disbursement Date')" />
                    <x-text-input id="bulk_date" type="date" class="mt-1 block w-full text-sm" wire:model="bulk_date" />
                    <x-input-error :messages="$errors->get('bulk_date')" class="mt-1" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" x-on:click="$dispatch('close-modal', 'allowance-bulk-award-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                    {{ __('Create Bulk Allocation') }}
                </button>
            </div>
        </form>
    </x-college.modal>
</div>
