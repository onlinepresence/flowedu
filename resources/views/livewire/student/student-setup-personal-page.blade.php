<div class="mx-auto max-w-4xl space-y-6">
    <!-- Onboarding Stepper Component -->
    <x-college.stepper :current="1" />

    <x-card class="overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Personal & Admission Details') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $hasStudent ? __('Update your details while your application is reviewed.') : __('Complete your details to submit your application for review.') }}
            </p>
        </div>

        <form wire:submit="save" class="space-y-8 p-6">
            <input type="hidden" wire:model="user_id" />

            <!-- Group 1: Academic & Setup Details -->
            <fieldset class="space-y-4">
                <legend class="text-sm font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-gray-100 dark:border-gray-700 pb-2 w-full">
                    {{ __('Admission & Account Setup') }}
                </legend>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="index_number" :value="__('Index / Application Number')" />
                        <x-text-input wire:model="index_number" id="index_number" type="text" class="mt-1 block w-full font-mono font-semibold" required />
                        <x-input-error :messages="$errors->get('index_number')" class="mt-1" />
                    </div>

                    @if (! $hasStudent)
                        <div class="sm:col-span-2">
                            <x-input-label for="program_id" :value="__('Program of Study')" />
                            <x-select-input wire:model="program_id" id="program_id" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select program…') }}</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }} @if($program->department) ({{ $program->department->name }}) @endif</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('program_id')" class="mt-1" />
                        </div>
                        
                        <div class="sm:col-span-2">
                            <x-input-label for="hall_id" :value="__('Hall of Residence')" />
                            <x-select-input wire:model="hall_id" id="hall_id" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select hall…') }}</option>
                                @foreach ($halls as $hall)
                                    <option value="{{ $hall->id }}">{{ $hall->name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('hall_id')" class="mt-1" />
                        </div>
                        
                        <div class="sm:col-span-2">
                            <x-input-label for="username" :value="__('Login Username')" />
                            <x-text-input wire:model="username" id="username" type="text" autocomplete="username" class="mt-1 block w-full font-mono" required />
                            <x-input-error :messages="$errors->get('username')" class="mt-1" />
                        </div>
                    @else
                        <!-- If student already created, show program/hall as read-only tags -->
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50">
                            <span class="text-xs font-semibold text-gray-500 uppercase dark:text-gray-400 block">{{ __('Program of Study') }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block">
                                {{ auth()->user()->student?->program?->name ?? '—' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50">
                            <span class="text-xs font-semibold text-gray-500 uppercase dark:text-gray-400 block">{{ __('Hall of Residence') }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block">
                                {{ auth()->user()->student?->hall?->name ?? '—' }}
                            </span>
                        </div>
                    @endif
                </div>
            </fieldset>

            <!-- Group 2: Personal details -->
            <fieldset class="space-y-4">
                <legend class="text-sm font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-gray-100 dark:border-gray-700 pb-2 w-full">
                    {{ __('Personal Information') }}
                </legend>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="lastname" :value="__('Last name')" />
                        <x-text-input wire:model="lastname" id="lastname" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="firstname" :value="__('First name')" />
                        <x-text-input wire:model="firstname" id="firstname" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('firstname')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="othernames" :value="__('Other names')" />
                        <x-text-input wire:model="othernames" id="othernames" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('othernames')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                        <x-text-input wire:model="date_of_birth" id="date_of_birth" type="date" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        @if (! $hasStudent)
                            <x-select-input wire:model="gender" id="gender" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select…') }}</option>
                                <option value="male">{{ __('Male') }}</option>
                                <option value="female">{{ __('Female') }}</option>
                            </x-select-input>
                            <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                        @else
                            <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-950 px-2.5 py-1 text-sm font-semibold text-purple-700 dark:text-purple-300 capitalize mt-2">
                                {{ $gender ?: '—' }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="nationality" :value="__('Nationality')" />
                        <x-text-input wire:model="nationality" id="nationality" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('nationality')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="religion" :value="__('Religion')" />
                        <x-text-input wire:model="religion" id="religion" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('religion')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="denomination" :value="__('Denomination')" />
                        <x-text-input wire:model="denomination" id="denomination" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('denomination')" class="mt-1" />
                    </div>
                </div>
            </fieldset>

            <!-- Group 3: Welfare & Contact details -->
            <fieldset class="space-y-4">
                <legend class="text-sm font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-gray-100 dark:border-gray-700 pb-2 w-full">
                    {{ __('Contact & Welfare Details') }}
                </legend>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="ghana_card" :value="__('Ghana Card number')" />
                        <x-text-input wire:model="ghana_card" id="ghana_card" type="text" placeholder="GHA-XXXXXXXXX-X" class="mt-1 block w-full font-mono" required />
                        <x-input-error :messages="$errors->get('ghana_card')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="insurance_number" :value="__('National Insurance number')" />
                        <x-text-input wire:model="insurance_number" id="insurance_number" type="text" class="mt-1 block w-full font-mono" />
                        <x-input-error :messages="$errors->get('insurance_number')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="blood_group" :value="__('Blood Group')" />
                        @if (!is_null(auth()->user()->student?->blood_group))
                            <x-select-input id="blood_group" class="mt-1 block w-full bg-gray-150 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed" disabled>
                                <option value="">{{ auth()->user()->student->blood_group }}</option>
                            </x-select-input>
                            <p class="text-3xs text-gray-400 mt-1"><i class="fa-solid fa-circle-info"></i> {{ __('Blood group cannot be changed once saved.') }}</p>
                        @else
                            <x-select-input wire:model="blood_group" id="blood_group" class="mt-1 block w-full">
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
                        <x-input-label for="disability_status" :value="__('Disability status')" />
                        <x-select-input wire:model.live="disability_status" id="disability_status" class="mt-1 block w-full">
                            <option value="no">{{ __('No') }}</option>
                            <option value="yes">{{ __('Yes') }}</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('disability_status')" class="mt-1" />
                    </div>

                    @if ($disability_status === 'yes')
                        <div class="sm:col-span-2">
                            <x-input-label for="disability_type" :value="__('Disability type')" />
                            <x-text-input wire:model="disability_type" id="disability_type" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('disability_type')" class="mt-1" />
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <x-input-label for="phone_number" :value="__('Mobile phone')" />
                        <x-text-input wire:model="phone_number" id="phone_number" type="tel" class="mt-1 block w-full font-mono" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="contact_address" :value="__('Contact address')" />
                        <x-textarea-input wire:model="contact_address" id="contact_address" rows="3" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('contact_address')" class="mt-1" />
                    </div>
                </div>
            </fieldset>

            @if ($hasStudent)
                <!-- Group 4: Financial details (only shown once Student record is generated) -->
                <fieldset class="space-y-4">
                    <legend class="text-sm font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-gray-100 dark:border-gray-700 pb-2 w-full">
                        {{ __('Financial & Banking Details') }}
                    </legend>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="account_bank" :value="__('Bank name')" />
                            <x-text-input wire:model="account_bank" id="account_bank" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('account_bank')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="account_number" :value="__('Account number')" />
                            <x-text-input wire:model="account_number" id="account_number" type="text" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('account_number')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="ssnit_number" :value="__('SSNIT number')" />
                            <x-text-input wire:model="ssnit_number" id="ssnit_number" type="text" class="mt-1 block w-full font-mono" />
                            <x-input-error :messages="$errors->get('ssnit_number')" class="mt-1" />
                        </div>
                    </div>
                </fieldset>
            @endif

            <!-- Group 5: Passport Photo Upload -->
            <fieldset class="space-y-4">
                <legend class="text-sm font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 border-b border-gray-100 dark:border-gray-700 pb-2 w-full">
                    {{ __('Passport Photo') }}
                </legend>
                
                <div class="space-y-2">
                    <x-filepond
                        field="profilePicPond"
                        purpose="passport_photo"
                        :label="__('Upload passport photo')"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Required file formats: JPG, PNG, WebP, AVIF. Max size 5MB.') }}
                    </p>
                    <x-input-error :messages="$errors->get('profilePicPond')" class="mt-1" />
                </div>
            </fieldset>

            <!-- Navigation & Submit Row -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    * {{ __('All fields in sections above are required unless marked optional.') }}
                </p>
                <div class="flex items-center gap-3">
                    <x-college-form-submit target="save" class="inline-flex justify-center px-6">
                        {{ __('Save & Continue') }}
                    </x-college-form-submit>
                </div>
            </div>
        </form>
    </x-card>
</div>
