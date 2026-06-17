<div class="mx-auto max-w-7xl space-y-6">
    <!-- Header tabs navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button" wire:click="switchTab('recipients')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'recipients' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-graduation-cap mr-2"></i>{{ __('Awards & Recipients') }}
            </button>
            <button type="button" wire:click="switchTab('schemes')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'schemes' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' }}">
                <i class="fa-solid fa-list-check mr-2"></i>{{ __('Scholarship Schemes') }}
            </button>
        </nav>
    </div>

    @if ($activeTab === 'recipients')
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Scholarship Awards & Recipients') }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Track and assign financial aid awards to individual students.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="openAwardModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <i class="fa-solid fa-plus"></i>
                    {{ __('Award Scholarship') }}
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <x-college.filter-card cols="3">
            <div>
                <x-input-label for="recipientSearch" :value="__('Search Student')" />
                <x-text-input id="recipientSearch" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Name or Index Number') }}" wire:model.live.debounce.300ms="recipientSearch" />
            </div>
            <div>
                <x-input-label for="recipientSchemeId" :value="__('Scholarship Scheme')" />
                <select id="recipientSchemeId" wire:model.live="recipientSchemeId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="">{{ __('All Schemes') }}</option>
                    @foreach ($activeSchemes as $as)
                        <option value="{{ $as->id }}">{{ $as->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="recipientStatus" :value="__('Status')" />
                <select id="recipientStatus" wire:model.live="recipientStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="applied">{{ __('Applied') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                </select>
            </div>
        </x-college.filter-card>

        <!-- Recipients Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @if (!empty($selectedIds))
                <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-950/30 border-b border-indigo-100 dark:border-indigo-900/50 px-6 py-3 text-sm text-indigo-800 dark:text-indigo-200">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-indigo-600 dark:text-indigo-400"></i>
                        <span><strong>{{ count($selectedIds) }}</strong> {{ __('scholarship(s) selected') }}</span>
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
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Scholarship') }}</th>
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
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $app->scholarship?->name ?? '—' }}
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-450">{{ $app->scholarship?->type }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $app->award_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $app->status === 'approved' || $app->status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' : ($app->status === 'rejected' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-550/10 dark:text-amber-400') }}">
                                        {{ ucfirst($app->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white font-bold whitespace-nowrap">{{ number_format((float) $app->amount_awarded, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                    @if ($app->status === 'applied')
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" wire:click="updateApplicationStatus({{ $app->id }}, 'approved')" class="text-xs font-semibold text-green-600 hover:text-green-500 dark:text-green-400">
                                                <i class="fa-solid fa-check mr-1"></i>{{ __('Approve') }}
                                            </button>
                                            <button type="button" wire:click="updateApplicationStatus({{ $app->id }}, 'rejected')" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400">
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
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No scholarship awards found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $applications->links() }}
            </div>
        </div>

        <!-- Award Scholarship Modal -->
        <x-college.modal name="sc-award-modal" :title="__('Award Scholarship to Student')" maxWidth="lg" livewireSynced>
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
                    <div>
                        <x-input-label for="award_scholarship_id" :value="__('Scholarship Scheme')" />
                        <select id="award_scholarship_id" wire:model.live="award_scholarship_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">{{ __('Select Scheme') }}</option>
                            @foreach ($activeSchemes as $as)
                                <option value="{{ $as->id }}">{{ $as->name }} ({{ number_format((float) $as->amount, 2) }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('award_scholarship_id')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="award_amount" :value="__('Amount Awarded')" />
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
                    <button type="button" x-on:click="$dispatch('close-modal', 'sc-award-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
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

    @endif

    @if ($activeTab === 'schemes')
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Scholarship & Grant Schemes') }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Define scholarship offerings, coverage parameters, and durations.') }}</p>
            </div>
            <button type="button" wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <i class="fa-solid fa-plus"></i>
                {{ __('Add Scheme') }}
            </button>
        </div>

        <!-- Filters Section -->
        <x-college.filter-card cols="2">
            <div>
                <x-input-label for="filterSchemeName" :value="__('Search Scheme')" />
                <x-text-input id="filterSchemeName" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Scheme name') }}" wire:model.live.debounce.300ms="filterSchemeName" />
            </div>
            <div>
                <x-input-label for="filterSchemeType" :value="__('Type')" />
                <select id="filterSchemeType" wire:model.live="filterSchemeType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="scholarship">{{ __('Scholarship') }}</option>
                    <option value="grant">{{ __('Grant') }}</option>
                </select>
            </div>
        </x-college.filter-card>

        <!-- Schemes Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Scheme Details') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Coverage & Duration') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Value / Rate') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Recipients') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Allocated') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($rows as $row)
                            <tr wire:key="scheme-{{ $row->id }}">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $row->name }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($row->type) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400">
                                        {{ str_replace('_', ' ', ucfirst($row->coverage_type ?? 'full')) }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Duration: :sem sem.', ['sem' => $row->duration_semesters ?? 1]) }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white font-semibold">{{ number_format((float) $row->amount, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row->recipients_count ?? 0 }}</td>
                                <td class="px-6 py-4 text-right text-sm text-green-600 dark:text-green-400 font-bold">{{ number_format((float) ($row->total_awarded ?? 0), 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $row->status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400' }}">
                                        {{ ucfirst($row->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                    <button type="button" wire:click="openEditModal({{ $row->id }})" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 font-semibold">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        {{ __('Edit') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No scholarship schemes found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $rows->links() }}
            </div>
        </div>
    @endif

    <!-- Add Scholarship Modal -->
    <x-college.modal name="sc-create" :title="__('Add Scholarship / Grant Scheme')" maxWidth="lg" livewireSynced>
        <form id="sc-create-form" wire:submit.prevent="saveCreate" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-input-label for="name" :value="__('Name / Title')" />
                    <x-text-input id="name" type="text" class="mt-1 block w-full text-sm" wire:model="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="type" :value="__('Type')" />
                    <select id="type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="scholarship">{{ __('Scholarship') }}</option>
                        <option value="grant">{{ __('Grant') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="amount" :value="__('Scheme Value / Sem. (GHS)')" />
                    <x-text-input id="amount" type="number" min="0" step="0.01" class="mt-1 block w-full text-sm" wire:model="amount" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="duration_semesters" :value="__('Duration (Semesters)')" />
                    <x-text-input id="duration_semesters" type="number" min="1" step="1" class="mt-1 block w-full text-sm" wire:model="duration_semesters" />
                    <x-input-error :messages="$errors->get('duration_semesters')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="expiry_date" :value="__('Expiry Date')" />
                    <x-text-input id="expiry_date" type="date" class="mt-1 block w-full text-sm" wire:model="expiry_date" />
                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-1" />
                </div>
            </div>

            <div class="border-t pt-4 border-gray-200 dark:border-gray-700">
                <x-input-label for="coverage_type" :value="__('Coverage Rules / Settings')" />
                <select id="coverage_type" wire:model.live="coverage_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <option value="full">{{ __('Full Coverage (Covers all Semester fees)') }}</option>
                    <option value="tuition_only">{{ __('Tuition Fees Only') }}</option>
                    <option value="hostel_only">{{ __('Hostel / Hall Cost Only') }}</option>
                    <option value="partial">{{ __('Partial (Covers specific fee components)') }}</option>
                </select>
                <x-input-error :messages="$errors->get('coverage_type')" class="mt-1" />
            </div>

            @if ($coverage_type === 'partial')
                <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-md space-y-2 border border-gray-100 dark:border-gray-800">
                    <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">{{ __('Select Covered Components') }}</span>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        @foreach ($extraFeeCatalog as $key => $label)
                            <label class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="coverage_components.{{ $key }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 text-xs" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('coverage_components')" class="mt-1" />
                </div>
            @endif

            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" wire:model="description"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="closeCreateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Create Scheme') }}
                </button>
            </div>
        </form>
    </x-college.modal>

    <!-- Edit Scholarship Modal -->
    <x-college.modal name="sc-edit" :title="__('Edit Scholarship / Grant Scheme')" maxWidth="lg" livewireSynced>
        <form id="sc-edit-form" wire:submit.prevent="saveEdit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-input-label for="edit_name" :value="__('Name / Title')" />
                    <x-text-input id="edit_name" type="text" class="mt-1 block w-full text-sm" wire:model="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_type" :value="__('Type')" />
                    <select id="edit_type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="scholarship">{{ __('Scholarship') }}</option>
                        <option value="grant">{{ __('Grant') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_amount" :value="__('Scheme Value / Sem. (GHS)')" />
                    <x-text-input id="edit_amount" type="number" min="0" step="0.01" class="mt-1 block w-full text-sm" wire:model="amount" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="edit_duration_semesters" :value="__('Duration (Semesters)')" />
                    <x-text-input id="edit_duration_semesters" type="number" min="1" step="1" class="mt-1 block w-full text-sm" wire:model="duration_semesters" />
                    <x-input-error :messages="$errors->get('duration_semesters')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_expiry_date" :value="__('Expiry Date')" />
                    <x-text-input id="edit_expiry_date" type="date" class="mt-1 block w-full text-sm" wire:model="expiry_date" />
                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-1" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-input-label for="edit_coverage_type" :value="__('Coverage Rules / Settings')" />
                    <select id="edit_coverage_type" wire:model.live="coverage_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="full">{{ __('Full Coverage (Covers all Semester fees)') }}</option>
                        <option value="tuition_only">{{ __('Tuition Fees Only') }}</option>
                        <option value="hostel_only">{{ __('Hostel / Hall Cost Only') }}</option>
                        <option value="partial">{{ __('Partial (Covers specific fee components)') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('coverage_type')" class="mt-1" />
                </div>
                <div class="col-span-2">
                    <x-input-label for="edit_status" :value="__('Status')" />
                    <select id="edit_status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="closed">{{ __('Closed') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>
            </div>

            @if ($coverage_type === 'partial')
                <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-md space-y-2 border border-gray-100 dark:border-gray-800">
                    <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">{{ __('Select Covered Components') }}</span>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        @foreach ($extraFeeCatalog as $key => $label)
                            <label class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="coverage_components.{{ $key }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 text-xs" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('coverage_components')" class="mt-1" />
                </div>
            @endif

            <div>
                <x-input-label for="edit_description" :value="__('Description')" />
                <textarea id="edit_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" wire:model="description"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="closeEditModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('Save Changes') }}
                </button>
            </div>
        </form>
    </x-college.modal>
</div>
