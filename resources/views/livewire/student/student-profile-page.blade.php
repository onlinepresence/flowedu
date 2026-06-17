<div class="mx-auto max-w-7xl space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left Column: Profile Picture & Guardian Information -->
        <div class="flex flex-col gap-6 lg:col-span-1">
            <!-- Profile Picture Card -->
            <x-card class="p-6 text-center border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl" x-data="{ showPhotoEdit: false }">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 text-left">{{ __('Passport Photo') }}</h3>
                <div class="flex flex-col items-center">
                    <!-- Photo container -->
                    <div class="relative group">
                        <x-college.avatar :src="$photoDataUrl" :name="$lastname" size="h-28 w-28" />
                        
                        <!-- Hover Edit Overlay -->
                        <button
                            type="button"
                            @click="showPhotoEdit = !showPhotoEdit"
                            class="absolute inset-0 flex h-28 w-28 items-center justify-center rounded-full bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
                        >
                            <i class="fa-solid fa-camera text-xl"></i>
                        </button>
                    </div>

                    <!-- Toggle Photo Upload Field -->
                    <div x-show="showPhotoEdit" x-transition class="mt-6 w-full text-left">
                        <x-filepond
                            field="profilePicPond"
                            purpose="passport_photo"
                            :label="__('Choose new photo')"
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
                </div>
            </x-card>

            <!-- Parent / Guardian Card -->
            <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                <div class="border-b border-gray-200 pb-3 dark:border-gray-700 mb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Parent / Guardian') }}</h3>
                </div>

                <!-- Guardian Form -->
                <form wire:submit="saveGuardian" class="space-y-4">
                    <input type="hidden" wire:model="guardian_id" />

                    <div>
                        <x-input-label for="g-name" :value="__('Full name')" />
                        <x-text-input wire:model="guardian_name" id="g-name" type="text" class="mt-1.5 block w-full text-sm" required />
                        <x-input-error :messages="$errors->get('guardian_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-rel" :value="__('Relationship')" />
                        <x-select-input wire:model="guardian_relationship" id="g-rel" class="mt-1.5 block w-full text-sm" required>
                            <option value="">{{ __('Select relationship…') }}</option>
                            <option value="father">{{ __('Father') }}</option>
                            <option value="mother">{{ __('Mother') }}</option>
                            <option value="brother">{{ __('Brother') }}</option>
                            <option value="sister">{{ __('Sister') }}</option>
                            <option value="guardian">{{ __('Guardian') }}</option>
                            <option value="uncle">{{ __('Uncle') }}</option>
                            <option value="aunt">{{ __('Aunt') }}</option>
                            <option value="sponsor">{{ __('Sponsor') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('guardian_relationship')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-phone" :value="__('Phone number')" />
                        <x-text-input wire:model="guardian_phone_number" id="g-phone" type="tel" class="mt-1.5 block w-full text-sm font-mono" required />
                        <x-input-error :messages="$errors->get('guardian_phone_number')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-email" :value="__('Email (optional)')" />
                        <x-text-input wire:model="guardian_email" id="g-email" type="email" class="mt-1.5 block w-full text-sm" />
                        <x-input-error :messages="$errors->get('guardian_email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-addr" :value="__('Address (optional)')" />
                        <x-textarea-input wire:model="guardian_address" id="g-addr" rows="2" class="mt-1.5 block w-full text-sm" />
                        <x-input-error :messages="$errors->get('guardian_address')" class="mt-1" />
                    </div>

                    <div class="flex items-center pt-2">
                        <x-college-form-submit target="saveGuardian" class="w-full justify-center">
                            {{ __('Update Guardian') }}
                        </x-college-form-submit>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Right Column: Student Profile Details -->
        <div class="lg:col-span-2">
            <form wire:submit="save" class="space-y-6">
                <input type="hidden" wire:model="user_id" />

                <!-- Card 1: Academic & Enrollment Status (Read-only inputs) -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Academic & Enrollment Status') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label :value="__('Program of Study')" />
                            <x-text-input value="{{ auth()->user()->student?->program?->name ?? '—' }}" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400" readonly />
                        </div>
                        <div>
                            <x-input-label :value="__('Department')" />
                            <x-text-input value="{{ auth()->user()->student?->department?->name ?? '—' }}" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400" readonly />
                        </div>
                        <div>
                            <x-input-label :value="__('Hall of Residence')" />
                            <x-text-input value="{{ auth()->user()->student?->hall?->name ?? '—' }}" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400" readonly />
                        </div>
                        <div>
                            <x-input-label :value="__('Gender')" />
                            <x-text-input value="{{ ucfirst($gender) ?: '—' }}" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400" readonly />
                        </div>
                    </div>
                </x-card>

                <!-- Card 2: Personal Details -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Personal Details') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="p-index_number" :value="__('Index / Admission Number')" />
                            <x-text-input wire:model="index_number" id="p-index_number" type="text" class="mt-1 block w-full font-mono" required />
                            <x-input-error :messages="$errors->get('index_number')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-lastname" :value="__('Last name')" />
                            <x-text-input wire:model="lastname" id="p-lastname" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-firstname" :value="__('First name')" />
                            <x-text-input wire:model="firstname" id="p-firstname" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('firstname')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="p-othernames" :value="__('Other names')" />
                            <x-text-input wire:model="othernames" id="p-othernames" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-date_of_birth" :value="__('Date of birth')" />
                            <x-text-input wire:model="date_of_birth" id="p-date_of_birth" type="date" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-nationality" :value="__('Nationality')" />
                            <x-text-input wire:model="nationality" id="p-nationality" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('nationality')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-religion" :value="__('Religion')" />
                            <x-text-input wire:model="religion" id="p-religion" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('religion')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-denomination" :value="__('Denomination')" />
                            <x-text-input wire:model="denomination" id="p-denomination" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('denomination')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Card 3: Identification & Welfare -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Identification & Welfare') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="p-ghana_card" :value="__('Ghana Card number')" />
                            <x-text-input wire:model="ghana_card" id="p-ghana_card" type="text" placeholder="GHA-XXXXXXXXX-X" class="mt-1 block w-full font-mono" required />
                            <x-input-error :messages="$errors->get('ghana_card')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-insurance_number" :value="__('National Insurance number')" />
                            <x-text-input wire:model="insurance_number" id="p-insurance_number" type="text" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('insurance_number')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-blood_group" :value="__('Blood Group')" />
                            @if (!is_null(auth()->user()->student?->blood_group))
                                <x-select-input id="p-blood_group" class="mt-1 block w-full bg-gray-150 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed" disabled>
                                    <option value="">{{ auth()->user()->student->blood_group }}</option>
                                </x-select-input>
                                <p class="text-3xs text-gray-400 mt-1"><i class="fa-solid fa-circle-info"></i> {{ __('Blood group cannot be changed once saved.') }}</p>
                            @else
                                <x-select-input wire:model="blood_group" id="p-blood_group" class="mt-1 block w-full">
                                    <option value="">{{ __('Select blood group…') }}</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </x-select-input>
                            @endif
                            <x-input-error :messages="$errors->get('blood_group')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-disability_status" :value="__('Disability status')" />
                            <x-select-input wire:model.live="disability_status" id="p-disability_status" class="mt-1 block w-full">
                                <option value="no">{{ __('No') }}</option>
                                <option value="yes">{{ __('Yes') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('disability_status')" class="mt-1" />
                        </div>

                        @if ($disability_status === 'yes')
                            <div>
                                <x-input-label for="p-disability_type" :value="__('Disability type')" />
                                <x-text-input wire:model="disability_type" id="p-disability_type" type="text" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('disability_type')" class="mt-1" />
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Card 4: Contact Information -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Contact Information') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="p-phone_number" :value="__('Mobile phone')" />
                            <x-text-input wire:model="phone_number" id="p-phone_number" type="tel" class="mt-1 block w-full font-mono" required />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="p-contact_address" :value="__('Contact address')" />
                            <x-textarea-input wire:model="contact_address" id="p-contact_address" rows="3" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('contact_address')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                <!-- Card 5: Bank & Financial Details -->
                <x-card class="p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">{{ __('Bank & Financial Details') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="p-account_bank" :value="__('Bank name')" />
                            <x-text-input wire:model="account_bank" id="p-account_bank" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('account_bank')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="p-account_number" :value="__('Account number')" />
                            <x-text-input wire:model="account_number" id="p-account_number" type="text" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('account_number')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="p-ssnit_number" :value="__('SSNIT number')" />
                            <x-text-input wire:model="ssnit_number" id="p-ssnit_number" type="text" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('ssnit_number')" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 pt-6 dark:border-gray-700 mt-6">
                        <x-college-form-submit target="save" class="inline-flex justify-center px-6">
                            {{ __('Save Profile Details') }}
                        </x-college-form-submit>
                    </div>
                </x-card>
            </form>
        </div>
    </div>
</div>
