<div class="mx-auto max-w-3xl space-y-6">
    <!-- Onboarding Stepper Component -->
    <x-college.stepper :current="3" />

    <x-card class="overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Admission Status') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Check the status of your admission and activate your account.') }}
            </p>
        </div>

        <div class="p-6 space-y-6">
            @if (! $student->approved)
                <!-- Pending Registrar Approval -->
                <div class="flex flex-col items-center text-center p-8 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/50 rounded-xl space-y-4">
                    <div class="h-16 w-16 bg-amber-100 dark:bg-amber-900/40 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm animate-pulse">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    
                    <div class="space-y-2">
                        <h2 class="text-lg font-bold text-amber-800 dark:text-amber-300">
                            {{ __('Admission Pending Review') }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                            {{ __('Your admission application is currently being reviewed by the registrar. You will be notified once it is approved.') }}
                        </p>
                    </div>

                    <div class="pt-2 text-xs text-gray-500 dark:text-gray-400 font-medium">
                        {{ __('Meanwhile, you can still edit or update your details if needed.') }}
                    </div>
                </div>

                <!-- Submission Summary Card -->
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/30 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Submitted Application Summary') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Full Name') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white block">{{ $student->lastname }}, {{ $student->firstname }} {{ $student->othernames }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Index Number') }}:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white block">{{ $student->index_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Program') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white block">{{ $student->program?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Hall of Residence') }}:</span>
                            <span class="font-bold text-gray-900 dark:text-white block">{{ $student->hall?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            @else
                <!-- Approved and Ready for Activation -->
                <div class="flex flex-col items-center text-center p-8 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-900/50 rounded-xl space-y-4">
                    <div class="h-16 w-16 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm">
                        <svg class="h-8 w-8 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-lg font-bold text-emerald-800 dark:text-emerald-300">
                            {{ __('Congratulations! Admission Approved') }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                            {{ __('Your admission has been approved by the registrar. Please activate your dashboard below to gain full portal access.') }}
                        </p>
                    </div>

                    @error('activate')
                        <p class="text-xs text-red-600 dark:text-red-400 font-semibold">{{ $message }}</p>
                    @enderror

                    <div class="pt-4 w-full max-w-xs mx-auto">
                        <x-college-submit-button action="activate" class="w-full justify-center text-base py-3 shadow-lg shadow-emerald-500/10">
                            {{ __('Activate Student Dashboard') }}
                        </x-college-submit-button>
                    </div>
                </div>
            @endif
        </div>
    </x-card>

    <!-- Navigation Row -->
    <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
        <a href="{{ route('student.setup.guardian') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to Step 2') }}
        </a>
    </div>
</div>
