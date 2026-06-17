<div class="mx-auto max-w-7xl space-y-6" x-data="{ showPhotoEdit: false }">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: photo + summary + password --}}
        <div class="flex flex-col gap-6 lg:gap-8">
            <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                <div class="flex flex-col items-center">
                    <!-- Photo container -->
                    <div class="relative group">
                        <x-college.avatar :src="$teacher->profile_pic ? route('teacher.profile.photo') : null" :name="$lastname" size="h-32 w-32" />
                        
                        <!-- Hover Edit Overlay -->
                        <button
                            type="button"
                            @click="showPhotoEdit = !showPhotoEdit"
                            class="absolute inset-0 flex h-32 w-32 items-center justify-center rounded-full bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
                        >
                            <i class="fa-solid fa-camera text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ trim(($title ? $title.' ' : '').$lastname.' '.$othernames) ?: __('Your name') }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 font-semibold text-purple-600 dark:text-purple-400">
                        {{ $teacher->department?->name ?? __('Department not assigned') }}
                    </p>
                    @if ($staff_id !== '')
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 font-mono">
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ __('Staff ID') }}:</span>
                            {{ $staff_id }}
                        </p>
                    @endif
                    @if ($rank || $qualification)
                        <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
                            @if ($rank){{ $rank }}@endif
                            @if ($rank && $qualification){{ ' | ' }}@endif
                            @if ($qualification){{ $qualification }}@endif
                        </p>
                    @endif
                </div>

                <!-- Toggle Photo Upload Field -->
                <div x-show="showPhotoEdit" x-transition class="mt-6 border-t border-gray-150 dark:border-gray-700/50 pt-5 text-left">
                    <x-filepond
                        field="profilePicPond"
                        purpose="teacher_profile_photo"
                        :label="__('Choose passport photo')"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                    />
                    <x-input-error :messages="$errors->get('profilePicPond')" class="mt-1" />
                    
                    @if ($profilePicPond)
                        <div class="flex justify-end mt-3">
                            <x-college-form-submit target="saveProfilePicture" class="w-full justify-center">
                                {{ __('Save Photo Only') }}
                            </x-college-form-submit>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                <livewire:profile.update-password-form />
            </x-card>
        </div>

        {{-- Right: main form --}}
        <div class="lg:col-span-2">
            <form wire:submit="save" class="space-y-6">
                <!-- Card 1: Personal Information -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <input type="hidden" wire:model="user_id" />
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Personal Information') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-title" :value="__('Title / Prefix')" />
                            <x-select-input wire:model="title" id="tp-title" class="mt-1 block w-full">
                                <option value="">{{ __('Select') }}</option>
                                <option value="Mr.">{{ __('Mr.') }}</option>
                                <option value="Mrs.">{{ __('Mrs.') }}</option>
                                <option value="Ms.">{{ __('Ms.') }}</option>
                                <option value="Dr.">{{ __('Dr.') }}</option>
                                <option value="Prof.">{{ __('Prof.') }}</option>
                                <option value="Rev.">{{ __('Rev.') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-lastname" :value="__('Last name')" />
                            <x-text-input wire:model="lastname" id="tp-lastname" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-othernames" :value="__('Other names')" />
                            <x-text-input wire:model="othernames" id="tp-othernames" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="tp-gender" :value="__('Gender')" />
                            <x-select-input wire:model="gender" id="tp-gender" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select') }}</option>
                                <option value="male">{{ __('Male') }}</option>
                                <option value="female">{{ __('Female') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="tp-dob" :value="__('Date of birth')" />
                            <x-text-input wire:model="date_of_birth" id="tp-dob" type="date" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-6">
                            <x-input-label for="tp-nationality" :value="__('Nationality')" />
                            <x-text-input wire:model="nationality" id="tp-nationality" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('nationality')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-6">
                            <x-input-label for="tp-ghana" :value="__('Ghana Card number')" />
                            <x-text-input wire:model="ghana_card" id="tp-ghana" type="text" class="mt-1 block w-full font-mono" placeholder="GHA-XXXXXXXXX-X" required />
                            <x-input-error :messages="$errors->get('ghana_card')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-6">
                            <x-input-label for="tp-address" :value="__('Contact address')" />
                            <x-textarea-input wire:model="contact_address" id="tp-address" rows="2" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('contact_address')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-6">
                            <x-input-label for="tp-phone" :value="__('Phone number')" />
                            <x-text-input wire:model="phone_number" id="tp-phone" type="tel" class="mt-1 block w-full font-mono" required />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Card 2: Professional Details -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Professional Details') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-staff" :value="__('Staff ID')" />
                            @if ($staffIdLocked)
                                <x-text-input id="tp-staff" type="text" value="{{ $staff_id }}" disabled class="mt-1 block w-full cursor-not-allowed bg-gray-150 dark:bg-gray-800 font-mono text-gray-500" />
                                <input type="hidden" wire:model="staff_id" />
                            @else
                                <x-text-input wire:model="staff_id" id="tp-staff" type="text" class="mt-1 block w-full font-mono" />
                            @endif
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ __('Your staff ID also serves as your login username when set for the first time.') }}</p>
                            <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-dept" :value="__('Department')" />
                            <x-text-input id="tp-dept" type="text" value="{{ $teacher->department?->name ?? __('Not assigned') }}" disabled class="mt-1 block w-full cursor-not-allowed bg-gray-150 dark:bg-gray-800 text-gray-500" />
                            <input type="hidden" wire:model="department_id" />
                        </div>
                        <div>
                            <x-input-label for="tp-rank" :value="__('Rank')" />
                            <x-select-input wire:model="rank" id="tp-rank" class="mt-1 block w-full">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($rankOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('rank')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-qual" :value="__('Highest qualification')" />
                            <x-select-input wire:model="qualification" id="tp-qual" class="mt-1 block w-full">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($qualificationOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('qualification')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-spec" :value="__('Field of specialization')" />
                            <x-text-input wire:model="specialization" id="tp-spec" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('specialization')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-office" :value="__('Office location (e.g. Block C, Room 302)')" />
                            <x-text-input wire:model="office_location" id="tp-office" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('office_location')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-office-hours" :value="__('Office consulting hours')" />
                            <x-text-input wire:model="office_hours" id="tp-office-hours" type="text" class="mt-1 block w-full" placeholder="e.g. Mon/Wed 2:00 PM - 4:00 PM" />
                            <x-input-error :messages="$errors->get('office_hours')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-emp" :value="__('Employment type')" />
                            <x-select-input wire:model="employment_type" id="tp-emp" class="mt-1 block w-full">
                                <option value="Full-time">{{ __('Full-time') }}</option>
                                <option value="Part-time">{{ __('Part-time') }}</option>
                                <option value="Visiting">{{ __('Visiting') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('employment_type')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-yrs" :value="__('Years of experience')" />
                            <x-text-input wire:model.number="years_experience" id="tp-yrs" type="number" min="0" max="50" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('years_experience')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-appoint" :value="__('Date of appointment')" />
                            <x-text-input wire:model="date_of_appointment" id="tp-appoint" type="date" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('date_of_appointment')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Card 3: Academic Documents -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Academic Documents') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-4">{{ __('Upload new files only when you want to replace existing documents.') }}</p>
                    <div class="space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Curriculum Vitae (CV)') }}</span>
                                @if ($teacher->cv)
                                    <a href="{{ route('teacher.profile.document', ['type' => 'cv']) }}" class="text-xs font-bold text-purple-650 hover:text-purple-700 hover:underline dark:text-purple-400" target="_blank">{{ __('View current') }}</a>
                                @endif
                            </div>
                            <x-filepond field="cvPond" purpose="teacher_cv" :label="__('CV (PDF, Word)')" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
                            <x-input-error :messages="$errors->get('cvPond')" class="mt-1" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Highest certificate') }}</span>
                                @if ($teacher->certificate)
                                    <a href="{{ route('teacher.profile.document', ['type' => 'certificate']) }}" class="text-xs font-bold text-purple-650 hover:text-purple-700 hover:underline dark:text-purple-400" target="_blank">{{ __('View current') }}</a>
                                @endif
                            </div>
                            <x-filepond field="certificatePond" purpose="teacher_certificate" :label="__('Certificate')" accept="application/pdf,image/jpeg,image/png" />
                            <x-input-error :messages="$errors->get('certificatePond')" class="mt-1" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('National ID') }}</span>
                                @if ($teacher->id_document)
                                    <a href="{{ route('teacher.profile.document', ['type' => 'id_document']) }}" class="text-xs font-bold text-purple-650 hover:text-purple-700 hover:underline dark:text-purple-400" target="_blank">{{ __('View current') }}</a>
                                @endif
                            </div>
                            <x-filepond field="idDocumentPond" purpose="teacher_id_document" :label="__('National ID')" accept="application/pdf,image/jpeg,image/png" />
                            <x-input-error :messages="$errors->get('idDocumentPond')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Card 4: Additional Information -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Additional Information') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="tp-em-name" :value="__('Emergency contact name')" />
                            <x-text-input wire:model="emergency_name" id="tp-em-name" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('emergency_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-em-phone" :value="__('Emergency contact number')" />
                            <x-text-input wire:model="emergency_phone" id="tp-em-phone" type="tel" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('emergency_phone')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-orcid" :value="__('ORCID iD')" />
                            <x-text-input wire:model="orcid_id" id="tp-orcid" type="text" class="mt-1 block w-full font-mono" placeholder="0000-0002-1825-0097" />
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ __('A unique 16-digit identifier that connects your academic publications and research work to your profile.') }}</p>
                            <x-input-error :messages="$errors->get('orcid_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="tp-scholar" :value="__('Google Scholar URL')" />
                            <x-text-input wire:model="google_scholar_url" id="tp-scholar" type="url" class="mt-1 block w-full" placeholder="https://scholar.google.com/citations?user=..." />
                            <x-input-error :messages="$errors->get('google_scholar_url')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="tp-research" :value="__('Research interests / short bio')" />
                            <x-textarea-input wire:model="research_interests" id="tp-research" rows="3" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('research_interests')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Form Submit Container -->
                <div class="flex justify-end">
                    <x-college-form-submit target="save" class="inline-flex justify-center px-6 py-2.5">
                        {{ __('Save changes') }}
                    </x-college-form-submit>
                </div>
            </form>
        </div>
    </div>
</div>
