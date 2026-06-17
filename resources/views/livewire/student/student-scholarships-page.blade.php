<div class="mx-auto max-w-5xl space-y-8">
    <!-- Page Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Scholarships & Grants') }}</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Explore funding opportunities, tuition relief grants, and track your active award applications.') }}</p>
    </div>

    <!-- Available Scholarships Section -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Available Opportunities') }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($available as $scholarship)
                @php
                    $isApplied = in_array($scholarship->id, $appliedScholarshipIds, true);
                    $iconClass = match(strtolower((string)$scholarship->type)) {
                        'merit' => 'fa-medal text-amber-500 bg-amber-50 dark:bg-amber-950/30',
                        'need' => 'fa-hand-holding-dollar text-emerald-500 bg-emerald-50 dark:bg-emerald-950/30',
                        default => 'fa-graduation-cap text-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                    };
                @endphp
                <div 
                    wire:key="sch-{{ $scholarship->id }}"
                    class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md dark:border-gray-700 dark:bg-gray-800 transition duration-205"
                >
                    <div class="space-y-3">
                        <!-- Top Row: Icon and Badge -->
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex items-center justify-center h-9 w-9 rounded-lg font-bold text-sm {{ $iconClass }}">
                                <i class="fa-solid {{ strtok($iconClass, ' ') }}"></i>
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ strtolower((string)$scholarship->type) === 'merit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                {{ ucfirst((string)$scholarship->type) }}
                            </span>
                        </div>

                        <!-- Title and Info -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug">{{ $scholarship->name }}</h4>
                            <p class="text-[10px] text-gray-400 mt-0.5 uppercase font-mono tracking-tight">{{ __('Award Value') }}</p>
                            <p class="text-lg font-extrabold text-gray-900 dark:text-white font-mono mt-0.5">
                                {{ number_format((float) $scholarship->amount, 2) }} <span class="text-xs font-semibold text-gray-400">GHS</span>
                            </p>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-gray-500 dark:text-gray-450 leading-relaxed line-clamp-3">
                            {{ $scholarship->description ?? __('No additional details provided.') }}
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60">
                        <button 
                            type="button" 
                            wire:click="viewDetails({{ $scholarship->id }})" 
                            class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg {{ $isApplied ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-655 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm' }} px-3.5 py-2 text-xs font-bold transition duration-205"
                        >
                            <i class="fa-solid {{ $isApplied ? 'fa-circle-check text-green-500' : 'fa-circle-info' }}"></i>
                            {{ $isApplied ? __('Applied (View Details)') : __('View Details & Apply') }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex flex-col items-center justify-center space-y-2">
                        <span class="text-2xl"><i class="fa-solid fa-folder-open text-gray-400"></i></span>
                        <span>{{ __('No scholarships or grants are currently accepting applications.') }}</span>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Application History Section -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('My Application History') }}</h3>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Scholarship / Grant') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Awarded Value') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Decision Date') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($myApplications as $app)
                            <tr wire:key="my-app-{{ $app->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-semibold">
                                    {{ $app->scholarship?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                                    @if ($app->status === 'applied' || $app->status === 'pending')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-450/10 dark:text-amber-400">
                                            <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                            {{ __('Applied') }}
                                        </span>
                                    @elseif ($app->status === 'approved')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                                            {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20 dark:bg-red-400/10 dark:text-red-400">
                                            <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                                            {{ __('Declined') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold font-mono {{ $app->status === 'approved' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                    {{ $app->status === 'approved' ? number_format((float) $app->amount_awarded, 2) . ' GHS' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $app->award_date ? $app->award_date->format('Y-m-d') : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    @if ($app->status === 'applied' || $app->status === 'pending')
                                        <button
                                            wire:click="confirmCancel({{ $app->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-650"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                            {{ __('Cancel') }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('You have not submitted any scholarship applications yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SCHOLARSHIP DETAILS MODAL -->
    @if ($showDetailsModal && $viewingScholarship)
        @php
            $isApplied = in_array($viewingScholarship->id, $appliedScholarshipIds, true);
            $iconClass = match(strtolower((string)$viewingScholarship->type)) {
                'merit' => 'fa-medal text-amber-500 bg-amber-50 dark:bg-amber-950/30',
                'need' => 'fa-hand-holding-dollar text-emerald-500 bg-emerald-50 dark:bg-emerald-950/30',
                default => 'fa-graduation-cap text-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
            };
            
            // Map fee keys to human labels
            $feeLabels = [
                'tuition_fee' => __('Tuition Fee'),
                'library_fee' => __('Library Fee'),
                'lab_fee' => __('Lab Fee'),
                'medical_fee' => __('Medical Fee'),
                'sports_fee' => __('Sports Fee'),
                'examination_fee' => __('Examination Fee'),
                'registration_fee' => __('Registration Fee'),
                'ict_fee' => __('ICT Fee'),
                'id_card_fee' => __('ID Card Fee'),
                'facility_maintenance_fee' => __('Facility Maintenance Fee'),
                'utility_fee' => __('Utility Fee'),
                'field_trip_fee' => __('Field Trip / Practicum Fee'),
                'internship_fee' => __('Internship / Attachment Fee'),
                'src_dues' => __('SRC / Student Dues'),
            ];
        @endphp
        <x-college.modal name="scholarship-details-modal" :title="__('Funding Scheme Details')" :show="true" maxWidth="lg" livewireSynced>
            <div class="space-y-6">
                <!-- Scheme Header Info -->
                <div class="flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-12 w-12 rounded-xl font-bold text-lg {{ $iconClass }} shrink-0">
                        <i class="fa-solid {{ strtok($iconClass, ' ') }}"></i>
                    </span>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $viewingScholarship->name }}</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ strtolower((string)$viewingScholarship->type) === 'merit' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                {{ ucfirst((string)$viewingScholarship->type) }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                {{ $viewingScholarship->coverage_type === 'full' ? __('Full Coverage') : __('Partial Coverage') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Financial Benefit Stats -->
                <div class="grid grid-cols-2 gap-4 rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/20">
                    <div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">{{ __('Award Value') }}</span>
                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white font-mono">
                            {{ number_format((float) $viewingScholarship->amount, 2) }} <span class="text-xs font-semibold text-gray-400">GHS</span>
                        </p>
                    </div>
                    <div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">{{ __('Duration') }}</span>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $viewingScholarship->duration_semesters }} {{ trans_choice('Semester|Semesters', $viewingScholarship->duration_semesters) }}
                        </p>
                    </div>
                </div>

                <!-- Scholarship Details / Description -->
                <div class="space-y-2">
                    <h4 class="text-xs font-bold text-gray-850 dark:text-gray-200 uppercase tracking-wider">{{ __('About this Opportunity') }}</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">
                        {{ $viewingScholarship->description ?? __('No additional description or terms provided.') }}
                    </p>
                </div>

                <!-- Coverage details breakdown -->
                <div class="space-y-2">
                    <h4 class="text-xs font-bold text-gray-850 dark:text-gray-200 uppercase tracking-wider">{{ __('Coverage Breakdown') }}</h4>
                    
                    @if ($viewingScholarship->coverage_type === 'full')
                        <div class="rounded-lg bg-green-50/50 border border-green-200 p-3 text-xs text-green-800 dark:bg-green-950/20 dark:border-green-900/30 dark:text-green-300">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-shield-halved text-sm shrink-0 mt-0.5"></i>
                                <span>{{ __('This scheme provides Full Coverage, covering 100% of all billed semester fee components (including tuition, registration, exams, and campus amenities) plus student hostel/residential fees.') }}</span>
                            </div>
                        </div>
                    @elseif ($viewingScholarship->coverage_type === 'tuition_only')
                        <div class="rounded-lg bg-indigo-50/50 border border-indigo-200 p-3 text-xs text-indigo-800 dark:bg-indigo-950/20 dark:border-indigo-900/30 dark:text-indigo-300">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-graduation-cap text-sm shrink-0 mt-0.5"></i>
                                <span>{{ __('This scheme provides Tuition Relief, covering only the main academic tuition fee component. Other auxiliary fees and campus dues are not covered.') }}</span>
                            </div>
                        </div>
                    @elseif ($viewingScholarship->coverage_type === 'hostel_only')
                        <div class="rounded-lg bg-amber-50/50 border border-amber-200 p-3 text-xs text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/30 dark:text-amber-300">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-hotel text-sm shrink-0 mt-0.5"></i>
                                <span>{{ __('This scheme provides Accommodation Support, covering only the student hall / hostel residential fee component. All other educational and service fees are excluded.') }}</span>
                            </div>
                        </div>
                    @elseif ($viewingScholarship->coverage_type === 'partial' && is_array($viewingScholarship->coverage_components) && count($viewingScholarship->coverage_components) > 0)
                        <div class="space-y-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('This scheme covers the following specific fee components (up to the awarded value):') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($viewingScholarship->coverage_components as $comp)
                                    <span class="inline-flex items-center rounded-lg bg-gray-100 text-gray-700 px-2 py-1 text-2xs font-semibold dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fa-solid fa-check text-green-500 mr-1 shrink-0"></i>
                                        {{ $feeLabels[$comp] ?? ucwords(str_replace('_', ' ', $comp)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-gray-50/50 border border-gray-200 p-3 text-xs text-gray-750 dark:bg-gray-800/40 dark:border-gray-700 dark:text-gray-300">
                            <span>{{ __('General support coverage up to the awarded scheme value.') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Expiry Info -->
                @if ($viewingScholarship->expiry_date)
                    <div class="flex items-center gap-1.5 text-2xs text-gray-500 dark:text-gray-450 font-mono">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ __('Applications close on') }}: {{ $viewingScholarship->expiry_date->format('Y-m-d') }}</span>
                    </div>
                @endif
            </div>

            <x-slot name="footer">
                <button 
                    type="button" 
                    wire:click="closeDetailsModal"
                    class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-650"
                >
                    {{ __('Close') }}
                </button>
                @if ($isApplied)
                    <button 
                        type="button" 
                        disabled
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-gray-150 dark:bg-gray-850 px-3.5 py-2 text-xs font-bold text-gray-400 dark:text-gray-500 cursor-not-allowed select-none"
                    >
                        <i class="fa-solid fa-circle-check text-green-500"></i>
                        {{ __('Already Applied') }}
                    </button>
                @else
                    <button 
                        type="button" 
                        wire:click="apply({{ $viewingScholarship->id }})"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition duration-205"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                        {{ __('Submit Application') }}
                    </button>
                @endif
            </x-slot>
        </x-college.modal>
    @endif

    <!-- CANCELLATION CONFIRMATION MODAL -->
    <x-college.confirm-modal
        name="confirm-cancel-scholarship"
        title="{{ __('Cancel Scholarship Application?') }}"
        type="danger"
        confirmText="{{ __('Yes, Cancel') }}"
        cancelText="{{ __('No, Keep Application') }}"
        wireConfirm="cancel"
    >
        <p>{{ __('Are you sure you want to cancel and withdraw your application for this scholarship? This action cannot be undone, and you will have to re-apply if you change your mind.') }}</p>
    </x-college.confirm-modal>
</div>
