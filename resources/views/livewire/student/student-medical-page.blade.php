<div class="mx-auto max-w-5xl space-y-6">

    <!-- CSS styles for printable medical summary card and hover/tab transitions -->
    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print {
                display: none !important;
            }
            .print-card {
                display: block !important;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                max-width: 600px;
                margin: 40px auto;
                box-shadow: none !important;
            }
            .print-header {
                border-bottom: 2px solid #6b21a8;
                padding-bottom: 12px;
                margin-bottom: 20px;
                text-align: center;
            }
        }
        @media screen {
            .print-card {
                display: none;
            }
        }
    </style>

    <!-- Top Header and Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-notes-medical text-purple-600 dark:text-purple-400"></i>
                {{ __('Medical & Health Registry') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Manage your campus medical profiles, immunizations, and emergency contacts.') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button 
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-all hover:-translate-y-0.5"
            >
                <i class="fa-solid fa-print"></i>
                {{ __('Print Summary') }}
            </button>
            <button 
                type="button"
                x-data
                x-on:click="$dispatch('open-modal', 'correction-request-modal')"
                class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 transition-all hover:-translate-y-0.5"
            >
                <i class="fa-solid fa-pen-fancy"></i>
                {{ __('Request Update') }}
            </button>
        </div>
    </div>

    <!-- Active Health Card Panel -->
    @if ($record)
        <x-card class="overflow-hidden border border-gray-200 dark:border-gray-700/80 shadow-md rounded-2xl no-print">
            <!-- Top Color Accented Banner -->
            <div class="h-2 bg-gradient-to-r from-red-500 via-purple-500 to-indigo-500"></div>
            
            <div class="p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <!-- Left: Student Health Bio details -->
                <div class="flex items-center gap-5">
                    <!-- Photo or Placeholder -->
                    <div class="relative shrink-0">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-tr from-purple-100 to-indigo-100 text-purple-600 dark:from-purple-950/40 dark:to-indigo-950/40 dark:text-purple-400 flex items-center justify-center text-2xl font-bold uppercase border-2 border-purple-200 dark:border-purple-900/60 overflow-hidden shadow-inner">
                            @if ($student->profile_pic)
                                <img src="{{ Storage::disk('college_uploads')->url($student->profile_pic) }}" alt="" class="h-full w-full object-cover" />
                            @else
                                {{ strtoupper(substr($student->lastname ?: 'P', 0, 1)) }}
                            @endif
                        </div>
                        <span class="absolute bottom-0 right-0 block h-5 w-5 rounded-full bg-emerald-500 border-4 border-white dark:border-gray-800 animate-pulse" title="{{ __('Active Account Status') }}"></span>
                    </div>

                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                            <i class="fa-solid fa-shield-halved text-[10px]"></i>
                            {{ __('Clinic File Status: Active') }}
                        </span>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ trim(implode(' ', array_filter([$student->firstname, $student->lastname]))) }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ __('Index') }}: {{ $student->index_number }} | {{ $student->program?->name ?? __('Not Enrolled') }}
                        </p>
                    </div>
                </div>

                <!-- Right: High-Impact Health Badges Grid -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-4">
                    <!-- Blood Group Badge Card -->
                    <div class="bg-red-50/70 border border-red-100 dark:bg-red-950/20 dark:border-red-900/30 rounded-xl px-4 py-2.5 flex items-center gap-3 shadow-sm min-w-[110px]">
                        <div class="text-red-500 dark:text-red-400 text-xl">
                            <i class="fa-solid fa-droplet"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold text-red-700 dark:text-red-400 uppercase tracking-wider">{{ __('Blood Group') }}</span>
                            <span class="text-base font-extrabold text-red-900 dark:text-red-200">
                                {{ $student->blood_group ?: '—' }}
                            </span>
                        </div>
                    </div>

                    <!-- Insurance Badge Card -->
                    <div class="bg-indigo-50/70 border border-indigo-100 dark:bg-indigo-950/20 dark:border-indigo-900/30 rounded-xl px-4 py-2.5 flex items-center gap-3 shadow-sm min-w-[140px]">
                        <div class="text-indigo-500 dark:text-indigo-400 text-xl">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('Insurance ID') }}</span>
                            <span class="text-sm font-extrabold text-indigo-900 dark:text-indigo-200 truncate max-w-[90px] block">
                                {{ $student->insurance_number ?: __('Not Filed') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Navigation Tabs Bar -->
        <div class="border-b border-gray-200 dark:border-gray-700 no-print">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button 
                    type="button" 
                    wire:click="setTab('profile')"
                    class="group inline-flex items-center gap-2 border-b-2 py-4 px-1 text-sm font-bold transition-all {{ $activeTab === 'profile' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <i class="fa-solid fa-kit-medical {{ $activeTab === 'profile' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                    {{ __('Health Profile') }}
                </button>

                <button 
                    type="button" 
                    wire:click="setTab('immunizations')"
                    class="group inline-flex items-center gap-2 border-b-2 py-4 px-1 text-sm font-bold transition-all {{ $activeTab === 'immunizations' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <i class="fa-solid fa-syringe {{ $activeTab === 'immunizations' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                    {{ __('Immunizations') }}
                </button>

                <button 
                    type="button" 
                    wire:click="setTab('contacts')"
                    class="group inline-flex items-center gap-2 border-b-2 py-4 px-1 text-sm font-bold transition-all {{ $activeTab === 'contacts' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <i class="fa-solid fa-address-book {{ $activeTab === 'contacts' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                    {{ __('Emergency Contact') }}
                </button>

                <button 
                    type="button" 
                    wire:click="setTab('history')"
                    class="group inline-flex items-center gap-2 border-b-2 py-4 px-1 text-sm font-bold transition-all {{ $activeTab === 'history' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <i class="fa-solid fa-clock-rotate-left {{ $activeTab === 'history' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                    {{ __('History Log') }}
                </button>
            </nav>
        </div>

        <!-- Tab Contents Panels -->
        <div class="mt-4 no-print">

            <!-- Panel 1: Health Profile -->
            @if ($activeTab === 'profile')
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Chronic Conditions Card -->
                    <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                            <span class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i class="fa-solid fa-heart-pulse"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Medical Conditions') }}</h3>
                        </div>
                        @if ($record->medical_conditions && strtolower(trim($record->medical_conditions)) !== 'none')
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium whitespace-pre-line leading-relaxed">
                                {{ $record->medical_conditions }}
                            </p>
                        @else
                            <div class="text-center py-6">
                                <i class="fa-solid fa-circle-check text-2xl text-emerald-500 mb-2"></i>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-450">{{ __('No chronic conditions listed.') }}</p>
                            </div>
                        @endif
                    </x-card>

                    <!-- Active Medications Card -->
                    <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                            <span class="h-8 w-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <i class="fa-solid fa-pills"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Current Medications') }}</h3>
                        </div>
                        @if ($record->medications && strtolower(trim($record->medications)) !== 'none')
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium whitespace-pre-line leading-relaxed">
                                {{ $record->medications }}
                            </p>
                        @else
                            <div class="text-center py-6">
                                <i class="fa-solid fa-ban text-2xl text-gray-400 mb-2"></i>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-450">{{ __('No active medications on file.') }}</p>
                            </div>
                        @endif
                    </x-card>

                    <!-- Allergies Card -->
                    <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl">
                        <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                            <span class="h-8 w-8 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Declared Allergies') }}</h3>
                        </div>
                        @if ($record->allergies && strtolower(trim($record->allergies)) !== 'none')
                            <div class="space-y-2">
                                @foreach (explode(',', $record->allergies) as $allergy)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 dark:bg-red-950/30 dark:text-red-400 border border-red-100 dark:border-red-900/30">
                                        <i class="fa-solid fa-circle text-[6px]"></i>
                                        {{ trim($allergy) }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <i class="fa-solid fa-circle-check text-2xl text-emerald-500 mb-2"></i>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-455">{{ __('No allergies reported.') }}</p>
                            </div>
                        @endif
                    </x-card>
                </div>
            @endif

            <!-- Panel 2: Immunization Log -->
            @if ($activeTab === 'immunizations')
                <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-3 mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <i class="fa-solid fa-shield-virus"></i>
                            </span>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Vaccination & Immunization Record') }}</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">{{ __('Verified by Clinic') }}</span>
                    </div>

                    @if ($record->immunization_records && strtolower(trim($record->immunization_records)) !== 'none')
                        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                            @foreach (explode(',', $record->immunization_records) as $immunization)
                                <div class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-900/40">
                                    <span class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-450 flex items-center justify-center text-xs shrink-0 mt-0.5">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">{{ trim($immunization) }}</h4>
                                        <p class="text-2xs text-gray-500 mt-0.5">{{ __('Status: Administered & Confirmed') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fa-solid fa-syringe text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-450">{{ __('No immunizations on record.') }}</p>
                        </div>
                    @endif
                </x-card>
            @endif

            <!-- Panel 3: Emergency Contact -->
            @if ($activeTab === 'contacts')
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Left: Emergency Contact Card -->
                    <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl md:col-span-2">
                        <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-3 mb-5">
                            <span class="h-8 w-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <i class="fa-solid fa-heart"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Primary Emergency Contact') }}</h3>
                                <p class="text-2xs text-gray-450">{{ __('Drawn automatically from guardian profile') }}</p>
                            </div>
                        </div>

                        @if ($guardian)
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <span class="text-2xs font-semibold text-gray-400 uppercase tracking-wider block">{{ __('Guardian Name') }}</span>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $guardian->name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-2xs font-semibold text-gray-400 uppercase tracking-wider block">{{ __('Relationship') }}</span>
                                    <p class="text-sm font-bold text-purple-650 dark:text-purple-400 capitalize">{{ $guardian->relationship }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-2xs font-semibold text-gray-400 uppercase tracking-wider block">{{ __('Contact Phone') }}</span>
                                    <p class="text-sm font-mono font-bold text-gray-900 dark:text-white">{{ $guardian->phone_number }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-2xs font-semibold text-gray-400 uppercase tracking-wider block">{{ __('Email Address') }}</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $guardian->email ?: '—' }}</p>
                                </div>
                                <div class="sm:col-span-2 space-y-1">
                                    <span class="text-2xs font-semibold text-gray-400 uppercase tracking-wider block">{{ __('Residential Address') }}</span>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-relaxed">{{ $guardian->address ?: '—' }}</p>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <i class="fa-solid fa-circle-exclamation text-4xl text-amber-500 mb-3 block animate-bounce"></i>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('No Guardian Registered') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('Please update your Parent / Guardian information in the Profile Setup section.') }}
                                </p>
                            </div>
                        @endif
                    </x-card>

                    <!-- Right: Quick Contact Warning Card -->
                    <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl bg-purple-50/50 dark:bg-purple-950/10 flex flex-col justify-between">
                        <div class="space-y-3">
                            <span class="text-xl text-purple-600 dark:text-purple-400">
                                <i class="fa-solid fa-circle-info"></i>
                            </span>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Emergency Rules') }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ __('In the event of a campus emergency or acute medical episode, the university medical clinic will immediately call this primary contact.') }}
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-purple-100 dark:border-purple-900/40">
                            <p class="text-[10px] text-gray-450 dark:text-gray-500 leading-relaxed">
                                {{ __('Need to update this contact? Go to your Profile settings or select Request Update to modify details.') }}
                            </p>
                        </div>
                    </x-card>
                </div>
            @endif

            <!-- Panel 4: History Log Timeline -->
            @if ($activeTab === 'history')
                <x-card class="p-6 border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-3 mb-6">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Academic Session Health History') }}</h3>
                        <p class="text-2xs text-gray-500 mt-0.5">{{ __('Log of all health entries made per academic year.') }}</p>
                    </div>

                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach ($records as $idx => $histRecord)
                                <li>
                                    <div class="relative pb-8">
                                        @if ($idx < count($records) - 1)
                                            <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-10 w-10 rounded-full bg-purple-50 dark:bg-purple-950/80 text-purple-600 dark:text-purple-400 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <i class="fa-solid fa-clock text-sm"></i>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5">
                                                <div class="flex justify-between items-center text-xs text-gray-500">
                                                    <span class="font-extrabold text-gray-900 dark:text-white text-sm">
                                                        {{ $histRecord->academicSession?->name ?? __('Academic Session') }}
                                                    </span>
                                                    <span class="text-2xs font-mono bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full dark:text-gray-400">
                                                        {{ $histRecord->created_at?->format('F d, Y') }}
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 space-y-1.5 bg-gray-50/50 dark:bg-gray-900/40 p-4 rounded-xl border border-gray-100 dark:border-gray-800/80">
                                                    <div><strong>{{ __('Chronic Conditions') }}:</strong> {{ $histRecord->medical_conditions ?: 'None' }}</div>
                                                    <div><strong>{{ __('Allergies') }}:</strong> {{ $histRecord->allergies ?: 'None' }}</div>
                                                    <div><strong>{{ __('Medications') }}:</strong> {{ $histRecord->medications ?: 'None' }}</div>
                                                    <div><strong>{{ __('Immunization Logs') }}:</strong> {{ $histRecord->immunization_records ?: '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>
            @endif

        </div>
    @else
        <!-- Fallback if no records at all -->
        <x-card class="p-12 text-center border border-gray-200 dark:border-gray-700/80 shadow-sm rounded-xl no-print">
            <i class="fa-solid fa-file-prescription text-5xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('No Medical History on File') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mt-2">
                {{ __('The university clinic has not registered a medical history file for your student record yet. Please visit the clinic for validation.') }}
            </p>
        </x-card>
    @endif


    <!-- ==================== PRINT CARD SUMMARY LAYOUT ==================== -->
    @if ($record)
        <div class="print-card bg-white text-black p-6 rounded-xl border-2 border-purple-800 max-w-[600px] mx-auto shadow-none">
            <!-- Header -->
            <div class="print-header text-center border-b-2 border-purple-800 pb-3 mb-4">
                <h2 class="text-lg font-extrabold uppercase tracking-wider text-purple-900">{{ __('Student Emergency Medical Card') }}</h2>
                <p class="text-[10px] text-gray-600 font-mono">{{ __('University Health Services Center') }}</p>
            </div>

            <!-- Student Bio Grid -->
            <div class="grid grid-cols-3 gap-4 border-b border-gray-200 pb-4 mb-4">
                <div class="col-span-2 space-y-1 text-xs">
                    <div><strong>{{ __('Name') }}:</strong> <span class="capitalize">{{ trim(implode(' ', array_filter([$student->firstname, $student->lastname]))) }}</span></div>
                    <div><strong>{{ __('Index Number') }}:</strong> <span class="font-mono">{{ $student->index_number }}</span></div>
                    <div><strong>{{ __('Program') }}:</strong> <span>{{ $student->program?->name ?? '—' }}</span></div>
                    <div><strong>{{ __('National ID / Card') }}:</strong> <span class="font-mono">{{ $student->ghana_card ?? '—' }}</span></div>
                </div>
                <div class="col-span-1 flex justify-center items-center">
                    <div class="h-20 w-20 rounded border border-gray-300 overflow-hidden bg-gray-50 flex items-center justify-center">
                        @if ($student->profile_pic)
                            <img src="{{ Storage::disk('college_uploads')->url($student->profile_pic) }}" alt="" class="h-full w-full object-cover" />
                        @else
                            <span class="text-xl font-bold text-gray-450">{{ strtoupper(substr($student->lastname ?: '?', 0, 1)) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Health Badges print row -->
            <div class="grid grid-cols-2 gap-4 border-b border-gray-200 pb-4 mb-4 text-xs">
                <div><strong>{{ __('Blood Group') }}:</strong> <span class="text-sm font-extrabold text-red-650">{{ $student->blood_group ?: '—' }}</span></div>
                <div><strong>{{ __('Insurance ID') }}:</strong> <span class="font-mono">{{ $student->insurance_number ?: '—' }}</span></div>
            </div>

            <!-- Detailed Medical History section -->
            <div class="space-y-2 border-b border-gray-200 pb-4 mb-4 text-xs">
                <div><strong>{{ __('Medical Conditions') }}:</strong> <span class="text-gray-700 block">{{ $record->medical_conditions ?: __('None Declared') }}</span></div>
                <div><strong>{{ __('Medications') }}:</strong> <span class="text-gray-700 block">{{ $record->medications ?: __('None Listed') }}</span></div>
                <div><strong>{{ __('Allergies') }}:</strong> <span class="text-red-700 font-semibold block">{{ $record->allergies ?: __('No Known Allergies') }}</span></div>
                <div><strong>{{ __('Immunization List') }}:</strong> <span class="text-gray-750 block">{{ $record->immunization_records ?: '—' }}</span></div>
            </div>

            <!-- Emergency Contact -->
            <div class="space-y-1 text-xs">
                <h4 class="font-bold text-purple-950 uppercase tracking-wide mb-1">{{ __('Emergency Contact Details') }}</h4>
                @if ($guardian)
                    <div><strong>{{ __('Contact') }}:</strong> <span>{{ $guardian->name }} ({{ ucfirst($guardian->relationship) }})</span></div>
                    <div><strong>{{ __('Phone') }}:</strong> <span class="font-mono">{{ $guardian->phone_number }}</span></div>
                @else
                    <div class="text-gray-500 italic">{{ __('No emergency contact listed') }}</div>
                @endif
            </div>

            <!-- Footer Stamps -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 text-[9px] text-gray-500">
                <div>
                    <p>{{ __('Clinic Card Verification Signature') }}</p>
                    <div class="h-8 border-b border-gray-300 w-32 mt-2"></div>
                </div>
                <div class="text-right">
                    <p>{{ __('Printed on') }}: {{ now()->format('Y-m-d H:i') }}</p>
                    <p>{{ __('Card ID') }}: {{ hash('crc32b', $student->index_number . $record->id) }}</p>
                </div>
            </div>
        </div>
    @endif


    <!-- ==================== CLINIC CORRECTION REQUEST MODAL ==================== -->
    <x-college.modal name="correction-request-modal" :title="__('Request Health Profile Correction')">
        <form wire:submit="submitCorrectionRequest" class="space-y-4">
            <div>
                <label for="correctionType" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Correction / Update Field') }}</label>
                <select 
                    wire:model="correctionType" 
                    id="correctionType"
                    class="block w-full text-sm rounded-lg border-gray-300 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-650 dark:bg-gray-800 dark:text-white"
                    required
                >
                    <option value="">{{ __('Select field…') }}</option>
                    <option value="allergy">{{ __('Allergies') }}</option>
                    <option value="condition">{{ __('Chronic Medical Conditions') }}</option>
                    <option value="medication">{{ __('Medications') }}</option>
                    <option value="insurance">{{ __('Insurance Information') }}</option>
                    <option value="other">{{ __('Other Information') }}</option>
                </select>
                @error('correctionType') <p class="text-xs text-red-650 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="correctionDescription" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Requested Correction / Updates') }}</label>
                <x-textarea-input 
                    wire:model="correctionDescription" 
                    id="correctionDescription" 
                    rows="4" 
                    class="block w-full text-sm" 
                    placeholder="{{ __('Describe the correction or update in detail (e.g. correct peanut allergy status, add new prescription)...') }}"
                    required
                />
                @error('correctionDescription') <p class="text-xs text-red-650 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="bg-purple-50/50 dark:bg-purple-950/10 p-3 rounded-lg border border-purple-100 dark:border-purple-900/40 text-[11px] text-gray-500 dark:text-gray-400 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info text-purple-600 dark:text-purple-400 mt-0.5"></i>
                <p class="leading-relaxed">
                    {{ __('Health corrections are processed manually by the university health clinic team. You will be notified via memo once your health registry is verified and updated.') }}
                </p>
            </div>

            <x-slot name="footer">
                <div class="flex items-center justify-end gap-3">
                    <button 
                        type="button" 
                        x-on:click="$dispatch('close-modal', 'correction-request-modal');"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-650 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <x-college-form-submit target="submitCorrectionRequest">
                        {{ __('Submit Request') }}
                    </x-college-form-submit>
                </div>
            </x-slot>
        </form>
    </x-college.modal>

</div>
