<div class="mx-auto max-w-4xl space-y-6" x-data="{ activeTab: 'general', search: '' }">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-200 shadow-sm" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <!-- Search Input & Tabs Bar -->
    <div class="flex flex-col gap-4 bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            </div>
            <input
                x-model="search"
                type="text"
                placeholder="{{ __('Search preferences by title or description...') }}"
                class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-gray-50 dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 text-sm transition-all"
            />
        </div>

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap border-b border-gray-100 dark:border-gray-700/60 pb-1 gap-1">
            <button 
                type="button" 
                @click="activeTab = 'general'" 
                :class="activeTab === 'general' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold'" 
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-gears mr-1.5 text-xs"></i>{{ __('General') }}
            </button>
            <button 
                type="button" 
                @click="activeTab = 'finance'" 
                :class="activeTab === 'finance' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold'" 
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-coins mr-1.5 text-xs"></i>{{ __('Finance Settings') }}
            </button>
            <button 
                type="button" 
                @click="activeTab = 'memo'" 
                :class="activeTab === 'memo' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold'" 
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-file-signature mr-1.5 text-xs"></i>{{ __('Memo Workflow') }}
            </button>
            <button 
                type="button" 
                @click="activeTab = 'leave'" 
                :class="activeTab === 'leave' ? 'border-purple-600 text-purple-600 dark:text-purple-400 border-b-2 font-bold' : 'text-gray-550 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold'" 
                class="px-4 py-2 text-sm transition focus:outline-none"
            >
                <i class="fa-solid fa-calendar-check mr-1.5 text-xs"></i>{{ __('Leave Management') }}
            </button>
        </div>
    </div>

    <!-- Form wrapper -->
    <form wire:submit="saveSettings" class="space-y-6">
        <div class="space-y-4">
            
            <!-- GENERAL SETTINGS TAB -->
            <div x-show="activeTab === 'general' || (search && 'student grading redirect external grading software'.includes(search.toLowerCase()))" class="space-y-4">
                <!-- Preference Card 1: Student Grading Redirect -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-square-poll-vertical"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('External Grading Software Redirect') }}</h3>
                                
                                @if (!$hasTeacherToolsLicence)
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-3xs font-extrabold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 uppercase tracking-wider">
                                        <i class="fa-solid fa-lock text-[9px]"></i>{{ __('Locked') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Redirect students directly to the external grading software interface from their dashboard. Requires the Advanced Teacher Tools licence package.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            @if ($hasTeacherToolsLicence)
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="student_grading_redirect" id="student_grading_redirect" class="peer sr-only">
                                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                                </label>
                            @else
                                <div class="text-xs text-amber-600 dark:text-amber-400 font-semibold italic bg-amber-50 dark:bg-amber-950/20 px-3 py-1.5 rounded-lg border border-amber-200 dark:border-amber-900/30">
                                    {{ __('Upgrade required to unlock redirect') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($student_grading_redirect)
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700/50 space-y-2">
                            <x-input-label for="external_grading_url" :value="__('External Grading / Results Portal URL')" />
                            <x-text-input
                                wire:model="external_grading_url"
                                id="external_grading_url"
                                type="url"
                                placeholder="https://grading.external-college.edu/dashboard"
                                class="block w-full text-sm"
                                required
                            />
                            <x-input-error :messages="$errors->get('external_grading_url')" class="mt-1" />
                        </div>
                    @endif
                </div>

                <!-- Preference Card 2: Student Self Registration -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-user-plus"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Student Self-Registration') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Enable or disable the self-registration link for new students on the public login page.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="allow_student_self_registration" id="allow_student_self_registration" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preference Card 3: System Email Notifications -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-bell"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Email Alerts & Notifications') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Enforce global sending of transactional email updates for password resets and system announcements.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="enable_email_notifications" id="enable_email_notifications" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preference Card 4: Detailed Bill Breakdown for Students -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-receipt"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Show Detailed Bill Breakdown') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Allow students to view the complete individual itemized breakdown of their semester fees (e.g. tuition, library, medical fees) instead of just the total billed.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="show_detailed_bill_breakdown" id="show_detailed_bill_breakdown" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preference Card 5: Class Attendance Policy & Threshold -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-clipboard-user"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Class Attendance Policy & Settings') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Enable the student-facing attendance policy disclaimer and define the default minimum attendance percentage threshold required to sit for examinations.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="show_attendance_policy" id="show_attendance_policy" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>

                    @if ($show_attendance_policy)
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700/50 space-y-2">
                            <x-input-label for="min_attendance_threshold" :value="__('Minimum Attendance Threshold (%)')" />
                            <x-text-input
                                wire:model="min_attendance_threshold"
                                id="min_attendance_threshold"
                                type="number"
                                min="0"
                                max="100"
                                class="block w-28 text-sm"
                                required
                            />
                            <p class="text-2xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ __('Students with attendance rates below this threshold for the current semester will be marked as Ineligible for exams.') }}
                            </p>
                            <x-input-error :messages="$errors->get('min_attendance_threshold')" class="mt-1" />
                        </div>
                    @endif
                </div>
            </div>

            <!-- FINANCE SETTINGS TAB -->
            <div x-show="activeTab === 'finance' || (search && 'finance billing cycle tuition fees calculate'.includes(search.toLowerCase()))" class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-coins"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Tuition Billing Cycle') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Choose whether tuition fees are calculated and billed on a semester-by-semester basis or a full academic year cycle.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <select wire:model="finance_billing_cycle" id="finance_billing_cycle" class="block rounded-lg border-gray-300 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="semester">{{ __('Semester-based Billing') }}</option>
                                <option value="yearly">{{ __('Yearly-based Billing') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEMO WORKFLOW TAB -->
            <div x-show="activeTab === 'memo' || (search && 'memo departmental isolation restrict members department'.includes(search.toLowerCase()))" class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-file-signature"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Department Memo Isolation') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Enforce strict departmental memo communication isolation. When enabled, users can only see and route memos within their own departments.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="memo_department_isolation" id="memo_department_isolation" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LEAVE MANAGEMENT TAB -->
            <div x-show="activeTab === 'leave' || (search && 'staff leave approval workflow path reviewer hod hr'.includes(search.toLowerCase()))" class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 transition duration-205">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Leave Approval Workflow') }}</h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {{ __('Define the validation pathway strategy for staff leave requests. Choose HOD-only signature or dual HOD and HR review.') }}
                            </p>
                        </div>

                        <div class="flex items-center shrink-0">
                            <select wire:model="leave_approval_workflow" id="leave_approval_workflow" class="block rounded-lg border-gray-305 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                                <option value="hod_only">{{ __('HOD Review Only') }}</option>
                                <option value="hod_and_hr">{{ __('Dual Review (HOD & HR)') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex justify-end">
            <x-college-form-submit target="saveSettings" class="rounded-lg px-6 py-2.5">
                <i class="fa-solid fa-cloud-arrow-up mr-2"></i>{{ __('Save Preferences') }}
            </x-college-form-submit>
        </div>
    </form>
</div>
