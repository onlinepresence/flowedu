<div class="mx-auto max-w-7xl space-y-6">
    @if ($capNotice)
        <div @class([
            'rounded-lg border p-4 text-sm',
            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100' => ! $capNoticeIsBlock,
            'border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100' => $capNoticeIsBlock,
        ]) role="alert">
            {{ $capNotice }}
        </div>
    @endif

    @if ($archetype === 'executive')
        <!-- ==================== EXECUTIVE LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @can('admin.student_management_view')
                <x-college.stats-card
                    :title="__('Pending approval')"
                    :value="$pendingCount"
                    color="amber"
                    icon="fa-solid fa-clock-rotate-left"
                    :href="route('admin.students.index', ['approval' => 'pending'])"
                />
                <x-college.stats-card
                    :title="__('Approved students')"
                    :value="$approvedCount"
                    color="green"
                    icon="fa-solid fa-user-check"
                    :href="route('admin.students.index', ['approval' => 'approved'])"
                />
            @endcan
            <x-college.stats-card
                :title="__('Licence — active students')"
                color="purple"
                icon="fa-solid fa-id-card-clip"
                class="sm:col-span-2 lg:col-span-2"
            >
                {{ $activeForCap }}
                @if ($maxStudents !== null)
                    <span class="text-base font-normal text-gray-500 dark:text-gray-400">/ {{ $maxStudents }} {{ __('max') }}</span>
                @endif
                @if ($maxStudents === null)
                    <span class="block text-xs font-normal text-gray-400 dark:text-gray-500 mt-1">{{ __('No student cap set.') }}</span>
                @endif
            </x-college.stats-card>
        </div>

        <x-college.quick-links :title="__('Executive Quick Links')">
            @can('admin.student_management_view')
                <a href="{{ route('admin.students.index') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-graduation-cap text-indigo-500 dark:text-indigo-400"></i>
                    {{ __('Students') }}
                </a>
            @endcan
            @can('admin.approve_registrations')
                <a href="{{ route('admin.students.index', ['approval' => 'pending']) }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-user-clock text-amber-500 dark:text-amber-400"></i>
                    {{ __('Approve Registrations') }}
                </a>
            @endcan
            @can('admin.view_financial_data')
                @if($canFinance)
                    <a href="{{ route('admin.finance.fees') }}" wire:navigate class="dashboard-quick-link">
                        <i class="fa-solid fa-wallet text-emerald-500 dark:text-emerald-400"></i>
                        {{ __('Fee Structure') }}
                    </a>
                @endif
            @endcan
            @can('admin.view_audit_logs')
                @if($canSystemAdmin)
                    <a href="{{ route('admin.audit-logs') }}" wire:navigate class="dashboard-quick-link">
                        <i class="fa-solid fa-clock-rotate-left text-purple-500 dark:text-purple-400"></i>
                        {{ __('Audit Trail') }}
                    </a>
                @endif
            @endcan
            <a href="{{ route('admin.settings.school') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-gears text-gray-500 dark:text-gray-400"></i>
                {{ __('School Settings') }}
            </a>
        </x-college.quick-links>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Pending Registrations -->
            @can('admin.approve_registrations')
                <div>
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recent Pending Approvals') }}</h2>
                        @if ($pendingPreview->isNotEmpty())
                            <a href="{{ route('admin.students.index', ['approval' => 'pending']) }}" wire:navigate class="text-sm font-medium text-purple-600 hover:text-purple-500 dark:text-purple-400">{{ __('View all pending') }}</a>
                        @endif
                    </div>
                    @if ($pendingPreview->isNotEmpty())
                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($pendingPreview as $row)
                                    <li class="flex items-center justify-between gap-4 px-4 py-3" wire:key="pend-{{ $row->id }}">
                                        <div>
                                            <p class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $row->index_number }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trim(implode(' ', array_filter([$row->firstname, $row->othernames, $row->lastname]))) }}</p>
                                        </div>
                                        <a href="{{ route('admin.students.index', ['approval' => 'pending']) }}" wire:navigate class="shrink-0 text-sm text-purple-600 hover:text-purple-500 dark:text-purple-400 font-semibold">{{ __('Open') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <x-college.empty-state :title="__('No pending registrations')" :description="__('No new registrations are waiting for your approval.')">
                            <x-slot name="icon"><i class="fa-solid fa-user-clock text-4xl text-gray-400"></i></x-slot>
                        </x-college.empty-state>
                    @endif
                </div>
            @endcan

            <!-- Announcements -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Recent Announcements') }}</h2>
                @if ($recentAnnouncements->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentAnnouncements as $ann)
                                <li class="px-4 py-3" wire:key="ann-{{ $ann->id }}">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ann->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ann->created_at->diffForHumans() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No announcements')" :description="__('No recent announcements have been posted.')">
                        <x-slot name="icon"><i class="fa-solid fa-bullhorn text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>
        </div>

    @elseif ($archetype === 'academic')
        <!-- ==================== ACADEMIC & HOD LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-college.stats-card
                :title="__('Department Students')"
                :value="$approvedCount"
                color="blue"
                icon="fa-solid fa-graduation-cap"
                :href="route('admin.students.index')"
            />
            <x-college.stats-card
                :title="__('Programs')"
                :value="$programsCount"
                color="indigo"
                icon="fa-solid fa-graduation-cap"
                :href="route('admin.academic.program')"
            />
            <x-college.stats-card
                :title="__('Courses')"
                :value="$coursesCount"
                color="cyan"
                icon="fa-solid fa-book"
            />
            <x-college.stats-card
                :title="__('Pending Grade Approvals')"
                :value="$pendingGradesCount"
                color="amber"
                icon="fa-solid fa-file-signature"
                :href="route('admin.grading.approve')"
            />
        </div>

        <x-college.quick-links :title="__('Academic Portal Links')">
            @can('admin.course_management_view')
                <a href="{{ route('admin.academic.program') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
                    {{ __('Programs') }}
                </a>
            @endcan
            @can('admin.nav_grading_enter')
                <a href="{{ route('admin.grading.enter') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-pen-to-square text-cyan-500"></i>
                    {{ __('Enter Grades') }}
                </a>
            @endcan
            @can('admin.nav_grading_approve')
                <a href="{{ route('admin.grading.approve') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-check-double text-emerald-500"></i>
                    {{ __('Approve Grades') }}
                </a>
            @endcan
            @can('admin.nav_teachers_evaluations')
                @if($canEvaluations)
                    <a href="{{ route('admin.evaluations') }}" wire:navigate class="dashboard-quick-link">
                        <i class="fa-solid fa-clipboard-question text-purple-500"></i>
                        {{ __('Evaluations') }}
                    </a>
                @endif
            @endcan
        </x-college.quick-links>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Evaluations -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Recent Evaluation Feedback') }}</h2>
                @if ($recentEvaluations->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentEvaluations as $eval)
                                <li class="px-4 py-3" wire:key="eval-{{ $eval->id }}">
                                    <div class="flex justify-between">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $eval->form->title }}</p>
                                        <span class="text-xs text-gray-500">{{ $eval->submitted_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Dept:') }} {{ $eval->studentDepartment?->name }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No evaluation responses')" :description="__('No feedback has been submitted yet.')">
                        <x-slot name="icon"><i class="fa-solid fa-clipboard-question text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>

            <!-- Announcements -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Department Announcements') }}</h2>
                @if ($recentAnnouncements->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentAnnouncements as $ann)
                                <li class="px-4 py-3" wire:key="ann-{{ $ann->id }}">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ann->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ann->created_at->diffForHumans() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No announcements')" :description="__('No announcements recorded.')">
                        <x-slot name="icon"><i class="fa-solid fa-bullhorn text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>
        </div>

    @elseif ($archetype === 'finance')
        <!-- ==================== FINANCE LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-college.stats-card
                :title="__('Total Invoiced')"
                value="GH₵{{ number_format($totalInvoiced, 2) }}"
                color="indigo"
                icon="fa-solid fa-file-invoice-dollar"
            />
            <x-college.stats-card
                :title="__('Total Collected')"
                value="GH₵{{ number_format($totalCollected, 2) }}"
                color="green"
                icon="fa-solid fa-hand-holding-dollar"
            />
            <x-college.stats-card
                :title="__('Outstanding Balance')"
                value="GH₵{{ number_format($totalOutstanding, 2) }}"
                color="rose"
                icon="fa-solid fa-circle-exclamation"
            />
            <x-college.stats-card
                :title="__('Expenditures')"
                value="GH₵{{ number_format($totalExpenditure, 2) }}"
                color="amber"
                icon="fa-solid fa-money-bill-transfer"
            />
        </div>

        @if($canFinance)
            <x-college.quick-links :title="__('Finance Actions')">
                <a href="{{ route('admin.finance.fees') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-wallet text-emerald-500"></i>
                    {{ __('Fee Structures') }}
                </a>
                <a href="{{ route('admin.finance.payments') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-circle-dollar-to-slot text-green-500"></i>
                    {{ __('Payments Log') }}
                </a>
                <a href="{{ route('admin.finance.outstanding') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-receipt text-rose-500"></i>
                    {{ __('Outstanding Fees') }}
                </a>
                <a href="{{ route('admin.finance.invoices') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-money-check-dollar text-indigo-500"></i>
                    {{ __('Invoices') }}
                </a>
            </x-college.quick-links>
        @endif

        <div x-data="{ finTab: 'income' }">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Cash Flow') }}</h2>
                <div class="flex rounded-lg bg-gray-100 p-0.5 text-xs font-semibold dark:bg-gray-700" role="tablist" aria-label="{{ __('Cash flow tabs') }}">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="finTab === 'income'"
                        @click="finTab = 'income'"
                        :class="finTab === 'income' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="rounded-md px-3 py-1.5 transition"
                    >
                        {{ __('Income') }} ({{ $recentPayments->count() }})
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="finTab === 'expenditure'"
                        @click="finTab = 'expenditure'"
                        :class="finTab === 'expenditure' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="rounded-md px-3 py-1.5 transition"
                    >
                        {{ __('Expenditure') }} ({{ $recentExpenditures->count() }})
                    </button>
                </div>
            </div>

            <!-- Income tab -->
            <div x-show="finTab === 'income'" role="tabpanel">
                @if ($recentPayments->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentPayments as $pay)
                                <li class="flex justify-between items-center gap-3 px-4 py-3" wire:key="pay-{{ $pay->id }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $pay->student?->firstname }} {{ $pay->student?->lastname }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ __('Ref:') }} {{ $pay->reference_number ?? '—' }} · {{ $pay->payment_method ?? __('Cash') }} · {{ ($pay->payment_date ?? $pay->created_at)?->format('d M Y') }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-green-600 dark:text-green-400">+GH₵{{ number_format((float) $pay->amount_paid, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No payments')" :description="__('No payments recorded recently.')">
                        <x-slot name="icon"><i class="fa-solid fa-circle-dollar-to-slot text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>

            <!-- Expenditure tab -->
            <div x-show="finTab === 'expenditure'" x-cloak role="tabpanel">
                @if ($recentExpenditures->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentExpenditures as $exp)
                                <li class="flex justify-between items-center gap-3 px-4 py-3" wire:key="exp-{{ $exp->id }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $exp->category ?? __('Expenditure') }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $exp->expense_number }} · {{ $exp->payment_method }} · {{ ($exp->payment_date ?? $exp->created_at)?->format('d M Y') }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-red-600 dark:text-red-400">-GH₵{{ number_format((float) $exp->amount, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No expenditures')" :description="__('No expenditures recorded recently.')">
                        <x-slot name="icon"><i class="fa-solid fa-money-bill-transfer text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>
        </div>

    @elseif ($archetype === 'welfare')
        <!-- ==================== WELFARE LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-college.stats-card
                :title="__('Disciplinary Incidents')"
                :value="$disciplinaryCount"
                color="rose"
                icon="fa-solid fa-gavel"
                :href="$canStudentWelfare ? route('admin.students.discipline') : null"
            />
            <x-college.stats-card
                :title="__('Medical History Records')"
                :value="$medicalCount"
                color="blue"
                icon="fa-solid fa-briefcase-medical"
                :href="$canStudentWelfare ? route('admin.students.medical') : null"
            />
            <x-college.stats-card
                :title="__('Welfare Enrolled')"
                :value="$approvedCount"
                color="green"
                icon="fa-solid fa-users"
                :href="route('admin.students.index')"
            />
        </div>

        <x-college.quick-links :title="__('Welfare & Discipline actions')">
            <a href="{{ route('admin.students.index') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-users text-blue-500"></i>
                {{ __('Student List') }}
            </a>
            @if($canStudentWelfare)
                <a href="{{ route('admin.students.discipline') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-gavel text-rose-500"></i>
                    {{ __('Discipline') }}
                </a>
                <a href="{{ route('admin.students.medical') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-heart-pulse text-red-500"></i>
                    {{ __('Medical Records') }}
                </a>
            @endif
        </x-college.quick-links>

        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Recent Disciplinary Cases') }}</h2>
            @if ($recentDisciplinary->isNotEmpty())
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($recentDisciplinary as $case)
                            <li class="px-4 py-3 flex justify-between" wire:key="case-{{ $case->id }}">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $case->fullname }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $case->offense }}</p>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $case->return_status ? 'bg-green-100 text-green-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $case->return_status ? __('Resolved') : __('Pending') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <x-college.empty-state :title="__('No disciplinary cases')" :description="__('Student conducts are perfectly aligned with guidelines.')">
                    <x-slot name="icon"><i class="fa-solid fa-gavel text-4xl text-gray-400"></i></x-slot>
                </x-college.empty-state>
            @endif
        </div>

    @elseif ($archetype === 'hr')
        <!-- ==================== HR LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-college.stats-card
                :title="__('Admins & Support Staff')"
                :value="$totalStaffCount"
                color="blue"
                icon="fa-solid fa-user-tie"
                :href="$canStaffHr ? route('admin.staff.index') : null"
            />
            <x-college.stats-card
                :title="__('Total Active Teachers')"
                :value="$totalTeachersCount"
                color="indigo"
                icon="fa-solid fa-chalkboard-user"
                :href="$canStaffHr ? route('admin.staff.teachers') : null"
            />
            <x-college.stats-card
                :title="__('Pending Leave Requests')"
                :value="$pendingLeavesCount"
                color="amber"
                icon="fa-solid fa-calendar-minus"
                :href="$canStaffHr ? route('admin.staff.leaves') : null"
            />
        </div>

        @if($canStaffHr)
            <x-college.quick-links :title="__('Human Resource Center')">
                <a href="{{ route('admin.staff.index') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-user-shield text-blue-500"></i>
                    {{ __('Support Staff') }}
                </a>
                <a href="{{ route('admin.staff.teachers') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-chalkboard-user text-indigo-500"></i>
                    {{ __('Teachers List') }}
                </a>
                <a href="{{ route('admin.staff.leaves') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-calendar-day text-amber-500"></i>
                    {{ __('Leave Manager') }}
                </a>
            </x-college.quick-links>
        @endif

        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Pending Leaves for Review') }}</h2>
            @if ($pendingLeavesPreview->isNotEmpty())
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($pendingLeavesPreview as $leave)
                            <li class="px-4 py-3 flex justify-between items-center" wire:key="leave-{{ $leave->id }}">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $leave->user?->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $leave->staffLeaveType?->name }} ({{ $leave->requested_days }} {{ __('days') }})</p>
                                </div>
                                <a href="{{ route('admin.staff.leaves') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-500 font-semibold">{{ __('Review') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <x-college.empty-state :title="__('No pending leaves')" :description="__('All staff leave requests are fully reviewed.')">
                    <x-slot name="icon"><i class="fa-solid fa-calendar-check text-4xl text-gray-400"></i></x-slot>
                </x-college.empty-state>
            @endif
        </div>

    @elseif ($archetype === 'audit')
        <!-- ==================== AUDIT LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-3">
            <x-college.stats-card
                :title="__('System Audit Logs')"
                value="Active Tracking"
                color="indigo"
                icon="fa-solid fa-shield-halved"
                :href="route('admin.audit-logs')"
            />
            <x-college.stats-card
                :title="__('Financial Transactions')"
                value="GH₵{{ number_format($totalCollected, 2) }}"
                color="green"
                icon="fa-solid fa-hand-holding-dollar"
            />
            <x-college.stats-card
                :title="__('Pending Leaves')"
                :value="$pendingLeavesCount"
                color="amber"
                icon="fa-solid fa-calendar-check"
                :href="route('admin.staff.leaves')"
            />
        </div>

        <x-college.quick-links :title="__('Risk & Audit Shortcuts')">
            <a href="{{ route('admin.audit-logs') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-shield-halved text-indigo-500"></i>
                {{ __('System Audit Logs') }}
            </a>
            @if($canFinance)
                <a href="{{ route('admin.finance.invoices') }}" wire:navigate class="dashboard-quick-link">
                    <i class="fa-solid fa-file-invoice-dollar text-emerald-500"></i>
                    {{ __('Invoices & Expenses') }}
                </a>
            @endif
            <a href="{{ route('admin.staff.leaves') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-calendar-day text-amber-500"></i>
                {{ __('Staff Leaves') }}
            </a>
        </x-college.quick-links>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Audit Logs -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Recent System Actions') }}</h2>
                @if ($recentAuditLogs->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentAuditLogs as $log)
                                <li class="px-4 py-3" wire:key="log-{{ $log->id }}">
                                    <div class="flex justify-between">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $log->action }}</p>
                                        <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('By:') }} {{ $log->user?->name }} ({{ $log->ip_address }})</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No audit logs')" :description="__('No system audit trails recorded recently.')">
                        <x-slot name="icon"><i class="fa-solid fa-shield-halved text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>

            <!-- Financial Transactions -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Audited Payment Transactions') }}</h2>
                @if ($recentPayments->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($recentPayments as $pay)
                                <li class="flex justify-between items-center gap-3 px-4 py-3" wire:key="pay-{{ $pay->id }}">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $pay->student?->firstname }} {{ $pay->student?->lastname }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ __('Ref:') }} {{ $pay->reference_number ?? '—' }} · {{ $pay->payment_method ?? __('Cash') }} · {{ ($pay->payment_date ?? $pay->created_at)?->format('d M Y') }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-green-600 dark:text-green-400">+GH₵{{ number_format((float) $pay->amount_paid, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <x-college.empty-state :title="__('No payments')" :description="__('No payments detected.')">
                        <x-slot name="icon"><i class="fa-solid fa-circle-dollar-to-slot text-4xl text-gray-400"></i></x-slot>
                    </x-college.empty-state>
                @endif
            </div>
        </div>

    @else
        <!-- ==================== GENERAL / FALLBACK LAYOUT ==================== -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-college.stats-card
                :title="__('Scoped Students')"
                :value="$approvedCount"
                color="green"
                icon="fa-solid fa-graduation-cap"
                :href="route('admin.students.index')"
            />
            <x-college.stats-card
                :title="__('Courses')"
                :value="$coursesCount"
                color="blue"
                icon="fa-solid fa-book"
            />
            <x-college.stats-card
                :title="__('Total Staff')"
                :value="$totalStaffCount"
                color="indigo"
                icon="fa-solid fa-user-shield"
            />
        </div>

        <x-college.quick-links :title="__('General Operations Shortcuts')">
            <a href="{{ route('admin.students.index') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-graduation-cap text-purple-500"></i>
                {{ __('Students') }}
            </a>
            <a href="{{ route('admin.profile') }}" wire:navigate class="dashboard-quick-link">
                <i class="fa-solid fa-user text-purple-500"></i>
                {{ __('My Profile') }}
            </a>
        </x-college.quick-links>

        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Recent Announcements') }}</h2>
            @if ($recentAnnouncements->isNotEmpty())
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($recentAnnouncements as $ann)
                            <li class="px-4 py-3" wire:key="ann-{{ $ann->id }}">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $ann->title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ann->created_at->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <x-college.empty-state :title="__('No announcements')" :description="__('No announcements recorded.')">
                    <x-slot name="icon"><i class="fa-solid fa-bullhorn text-4xl text-gray-400"></i></x-slot>
                </x-college.empty-state>
            @endif
        </div>
    @endif
</div>
