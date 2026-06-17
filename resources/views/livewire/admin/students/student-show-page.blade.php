<div class="mx-auto max-w-7xl space-y-6">
    <!-- Back Navigation -->
    <div>
        <a href="{{ route('admin.students.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition-colors duration-150">
            <i class="fa-solid fa-arrow-left"></i>
            {{ __('Back to Students Directory') }}
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Sidebar profile card -->
        <div class="space-y-6 lg:col-span-1">
            <x-card class="text-center p-6">
                <div class="flex flex-col items-center">
                    <!-- Photo placeholder -->
                    <div class="flex h-28 w-28 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 shadow-inner">
                        @if ($student->passport_photo)
                            <!-- If passport photo path exists, render it. Fallback is initials -->
                            <img src="{{ asset('storage/' . $student->passport_photo) }}" alt="{{ $student->lastname }}" class="h-28 w-28 rounded-full object-cover" />
                        @else
                            <span class="text-3xl font-bold uppercase">{{ substr($student->firstname ?? 'S', 0, 1) }}{{ substr($student->lastname ?? 'S', 0, 1) }}</span>
                        @endif
                    </div>
                    <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">{{ trim(implode(' ', array_filter([$student->firstname, $student->othernames, $student->lastname]))) }}</h2>
                    <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">{{ $student->index_number }}</p>
                    <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $student->approved ? 'bg-green-150 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' }}">
                        {{ $student->approved ? __('Approved Account') : __('Pending Approval') }}
                    </span>
                </div>

                <div class="mt-6 border-t border-gray-150 pt-5 text-left space-y-4">
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Level') }}</span>
                        <span class="text-sm text-gray-900 dark:text-white font-medium">{{ __('Level :level', ['level' => $student->current_year]) }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Primary Email') }}</span>
                        <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $student->user?->email ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Mobile Number') }}</span>
                        <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $student->phone_number }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-150 pt-5 flex flex-wrap gap-2 justify-center">
                    @if (! $student->approved)
                        <a
                            href="{{ route('admin.approve-student', ['index_number' => $student->index_number, 'guardian' => $student->parentGuardians->count() > 0 ? 1 : 0, 'id' => $student->user_id]) }}"
                            class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-4 py-2 text-xs font-semibold text-white hover:bg-purple-500 transition-colors duration-150 shadow-sm"
                            wire:navigate
                        >
                            <i class="fa-solid fa-user-check"></i>
                            {{ __('Approve Student') }}
                        </a>
                    @else
                        <a
                            href="{{ route('admin.students.print', ['index_number' => $student->index_number]) }}"
                            class="inline-flex items-center gap-1.5 rounded-md bg-white border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-750 dark:hover:text-white transition-colors duration-150 shadow-sm"
                            wire:navigate
                        >
                            <i class="fa-solid fa-print"></i>
                            {{ __('Print Student Record') }}
                        </a>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Main Details area -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Personal and Academic tabs -->
            <x-card>
                <div class="border-b border-gray-150 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Profile details') }}</h3>
                </div>
                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gender') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white uppercase">{{ $student->gender }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date of Birth') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->date_of_birth?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Nationality') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->nationality ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Religion / Denomination') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $student->religion ?: '—' }} {{ $student->denomination ? '('.$student->denomination.')' : '' }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Contact Address') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->contact_address ?: '—' }}</span>
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-150 pt-5">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ __('Academic Information') }}</h4>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Faculty') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->department?->faculty?->name ?? $student->program?->department?->faculty?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Department') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->department?->name ?? $student->program?->department?->name ?? '—' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned Program') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->program?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Hall of Residence') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->hall?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date of Admission') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->admission_date?->format('Y-m-d') ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Parent / Guardian Information -->
            <x-card>
                <div class="border-b border-gray-150 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Parent / Guardian details') }}</h3>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse ($student->parentGuardians as $guardian)
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50 border border-gray-150 dark:border-gray-700">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Relationship') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->relationship }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Phone Number') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->phone_number }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Email') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->email ?: '—' }}</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Address') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->address ?: '—' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No guardian details recorded.') }}</p>
                    @endforelse
                </div>
            </x-card>

            <!-- Health & Medical Overview -->
            <x-card>
                <div class="border-b border-gray-150 pb-3 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Medical information') }}</h3>
                    <a href="{{ route('admin.students.medical') }}" wire:navigate class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                        {{ __('Manage Medical Records') }}
                    </a>
                </div>
                @if ($student->medicalHistory)
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Medical conditions') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->medicalHistory->medical_conditions ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Allergies') }}</span>
                            <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $student->medicalHistory->allergies ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Insurance number') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->insurance_number ?: '—' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Emergency contacts') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->medicalHistory->emergency_contacts ?: '—' }}</span>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('No medical history available.') }}</p>
                @endif
            </x-card>

            <!-- Disciplinary History -->
            <x-card>
                <div class="border-b border-gray-150 pb-3 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Disciplinary record') }}</h3>
                    <a href="{{ route('admin.students.discipline') }}" wire:navigate class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                        {{ __('Manage Discipline') }}
                    </a>
                </div>
                @if ($disciplinaryRecords->isNotEmpty())
                    <div class="mt-4 space-y-4">
                        @foreach ($disciplinaryRecords as $record)
                            <div class="rounded-lg border border-gray-150 p-4 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $record->offense }}</span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $record->return_status ? 'bg-green-100 text-green-800 dark:bg-green-900/30' : 'bg-red-100 text-red-800 dark:bg-red-900/30' }}">
                                        {{ $record->return_status ? __('Case Closed') : __('Active Case') }}
                                    </span>
                                </div>
                                <div class="mt-2 grid gap-2 text-xs text-gray-600 dark:text-gray-400 sm:grid-cols-2">
                                    <div><strong>{{ __('Action taken') }}:</strong> {{ $record->action_taken }}</div>
                                    <div><strong>{{ __('Date') }}:</strong> {{ $record->date_of_action?->format('Y-m-d') ?? '—' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('No disciplinary incidents reported.') }}</p>
                @endif
            </x-card>

            <!-- Activities & Career Milestones -->
            <x-card>
                <div class="border-b border-slate-200 dark:border-slate-700 pb-3 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-briefcase text-purple-600 dark:text-purple-400"></i>
                        {{ __('Activities & Career Milestones') }}
                    </h3>
                    <button type="button" wire:click="toggleActivityForm" class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300">
                        <i class="fa-solid {{ $showActivityForm ? 'fa-minus' : 'fa-plus' }}"></i>
                        {{ $showActivityForm ? __('Cancel') : __('Record Activity') }}
                    </button>
                </div>

                @if ($showActivityForm)
                    <form wire:submit.prevent="addActivity" class="mt-4 p-4 rounded-lg bg-slate-50 dark:bg-gray-900/40 border border-slate-200 dark:border-slate-750 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-input-label for="activityName" :value="__('Activity / Opportunity Name')" />
                                <x-text-input id="activityName" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('e.g., Summer Software Internship') }}" wire:model="activityName" />
                                <x-input-error :messages="$errors->get('activityName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="activityRole" :value="__('Role / Responsibility')" />
                                <x-text-input id="activityRole" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('e.g., Frontend Developer') }}" wire:model="activityRole" />
                                <x-input-error :messages="$errors->get('activityRole')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="activityDate" :value="__('Date of Participation')" />
                                <x-text-input id="activityDate" type="date" class="mt-1 block w-full text-sm" wire:model="activityDate" />
                                <x-input-error :messages="$errors->get('activityDate')" class="mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="submit" class="rounded-md bg-purple-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-purple-700">
                                {{ __('Save Record') }}
                            </button>
                        </div>
                    </form>
                @endif

                @if ($activities->isNotEmpty())
                    <div class="mt-4 space-y-4">
                        @foreach ($activities as $act)
                            <div class="flex items-start justify-between rounded-lg border border-slate-200 dark:border-slate-700 p-4" wire:key="act-{{ $act->id }}">
                                <div>
                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $act->activity_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        <span class="font-semibold">{{ __('Role') }}:</span> {{ $act->role }}
                                        <span class="mx-1.5">&middot;</span>
                                        <span class="font-semibold">{{ __('Date') }}:</span> {{ $act->participation_date?->format('Y-m-d') ?? '—' }}
                                    </p>
                                </div>
                                <button type="button" wire:click="deleteActivity({{ $act->id }})" wire:confirm="{{ __('Are you sure you want to delete this activity record?') }}" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400 p-1">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('No activities or opportunities logged for this student.') }}</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
