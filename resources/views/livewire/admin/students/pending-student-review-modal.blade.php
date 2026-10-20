<div>
    <x-college.modal name="pending-student-review-modal" maxWidth="4xl" :title="__('Pending Admission Review')">
        <div @if ($student !== null) wire:loading wire:target="open" @endif class="animate-pulse space-y-6" aria-hidden="true">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="h-24 w-24 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="h-6 w-56 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-36 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-5 w-28 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                </div>
            </div>

            @for ($section = 0; $section < 3; $section++)
                <div class="space-y-4">
                    <div class="h-5 w-52 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @for ($field = 0; $field < 6; $field++)
                            <div class="space-y-2">
                                <div class="h-3 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                                <div class="h-4 w-36 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>

        @if ($student)
            <div wire:loading.remove wire:target="open" class="space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full bg-purple-100 text-purple-600 shadow-inner dark:bg-purple-900/30 dark:text-purple-400">
                        @if ($photoDataUrl)
                            <img src="{{ $photoDataUrl }}" alt="{{ $student->lastname }}" class="h-24 w-24 object-cover" />
                        @else
                            <span class="text-2xl font-bold uppercase">
                                {{ substr($student->firstname ?? 'S', 0, 1) }}{{ substr($student->lastname ?? 'S', 0, 1) }}
                            </span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ trim(implode(' ', array_filter([$student->firstname, $student->othernames, $student->lastname]))) }}
                        </h3>
                        <p class="mt-1 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $student->index_number }}</p>
                        <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                            {{ $student->approved ? __('Approved') : __('Pending Approval') }}
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="border-b border-gray-200 pb-2 text-sm font-bold uppercase tracking-wider text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ __('Personal Information') }}
                    </h4>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Email') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->user?->email ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Phone Number') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->phone_number ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gender') }}</span>
                            <span class="text-sm font-medium uppercase text-gray-900 dark:text-white">{{ $student->gender ?: '—' }}</span>
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
                                {{ $student->religion ?: '—' }}{{ $student->denomination ? ' ('.$student->denomination.')' : '' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Ghana Card') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->ghana_card ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Insurance Number') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->insurance_number ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Blood Group') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->blood_group ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Disability') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $student->disability_status === 'yes' ? ($student->disability_type ?: __('Yes')) : __('No') }}
                            </span>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Contact Address') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->contact_address ?: '—' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="border-b border-gray-200 pb-2 text-sm font-bold uppercase tracking-wider text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ __('Academic Information') }}
                    </h4>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->program?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Department') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->department?->name ?? $student->program?->department?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Faculty') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->department?->faculty?->name ?? $student->program?->department?->faculty?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Level') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Level :level', ['level' => $student->current_year]) }}</span>
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

                <div>
                    <h4 class="border-b border-gray-200 pb-2 text-sm font-bold uppercase tracking-wider text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ __('Parent / Guardian Information') }}
                    </h4>
                    <div class="mt-4 space-y-3">
                        @forelse ($student->parentGuardians as $guardian)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Name') }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->name }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Relationship') }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->relationship ?: '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Phone Number') }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->phone_number ?: '—' }}</span>
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
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                {{ __('No guardian information has been provided. The student cannot be approved until a guardian is added.') }}
                            </p>
                        @endforelse
                    </div>
                </div>

                @error('approve')
                    <p class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        @endif

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'pending-student-review-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                {{ __('Close') }}
            </button>
            @if ($student && ! $student->approved)
                <span wire:loading.remove wire:target="open">
                    <button
                        type="button"
                        wire:click="approve"
                        wire:loading.attr="disabled"
                        @disabled(! $guardianComplete)
                        class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-purple-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i class="fa-solid fa-user-check"></i>
                        {{ __('Approve Student') }}
                    </button>
                </span>
            @endif
        </x-slot>
    </x-college.modal>
</div>
