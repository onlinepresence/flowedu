<div class="mx-auto max-w-7xl space-y-6" x-data="{ showPhotoEdit: false }">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-905/40 dark:bg-green-950/40 dark:text-green-200 shadow-sm" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if (session('admin_register') && $username === '')
        <div class="rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm text-purple-900 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-200 shadow-sm">
            <i class="fa-solid fa-circle-info mr-2"></i>{{ __('Complete this form to finish your Super Admin registration.') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Sidebar profile card -->
        <div class="space-y-6 lg:col-span-1">
            <x-card class="text-center p-6 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl">
                <div class="flex flex-col items-center">
                    <!-- Photo container -->
                    <div class="relative group">
                        <x-college.avatar :src="$existingProfileUrl" :name="$othernames . ' ' . $lastname" size="h-28 w-28" />
                        
                        <!-- Hover Edit Overlay -->
                        <button
                            type="button"
                            @click="showPhotoEdit = !showPhotoEdit"
                            class="absolute inset-0 flex h-28 w-28 items-center justify-center rounded-full bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
                        >
                            <i class="fa-solid fa-camera text-xl"></i>
                        </button>
                    </div>

                    <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                        {{ trim($othernames . ' ' . $lastname) ?: __('Your Name') }}
                    </h2>
                    <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">@<span>{{ $username ?: 'username' }}</span></p>
                    
                    <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">
                        {{ $userType === 'admin' ? __('Administrator') : __('Non-Teaching Staff') }}
                    </span>
                </div>

                <div class="mt-6 border-t border-gray-150 dark:border-gray-700/50 pt-5 text-left space-y-4">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Primary Phone') }}</span>
                        <span class="text-sm text-gray-900 dark:text-white font-semibold">{{ $phone_number ?: '—' }}</span>
                    </div>
                    @if ($position_title)
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Position / Role') }}</span>
                            <span class="text-sm text-gray-905 dark:text-white font-semibold">{{ $position_title }}</span>
                        </div>
                    @endif
                </div>

                <!-- Toggle Photo Upload Field -->
                <div x-show="showPhotoEdit" x-transition class="mt-6 border-t border-gray-150 dark:border-gray-700/50 pt-5 text-left">
                    <x-filepond
                        field="profilePhotoPond"
                        purpose="admin_profile_photo"
                        :label="__('Choose Profile Photo')"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                    />
                    
                    @if ($profilePhotoPond)
                        <button
                            type="button"
                            wire:click="saveProfilePhoto"
                            class="mt-3 w-full inline-flex items-center justify-center gap-1.5 rounded-lg bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-700 focus:outline-none transition-all"
                        >
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            {{ __('Save Photo Only') }}
                        </button>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Main Details Form area -->
        <div class="space-y-6 lg:col-span-2">
            <x-card class="border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl p-6">
                <div class="border-b border-gray-150 dark:border-gray-700/50 pb-3 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Profile details') }}</h3>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <!-- Section: Personal Info -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="adm-username" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Username') }}</label>
                            <input wire:model="username" id="adm-username" type="text" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                            @error('username') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="adm-lastname" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Last Name') }}</label>
                            <input wire:model="lastname" id="adm-lastname" type="text" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                            @error('lastname') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="adm-othernames" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Other Names') }}</label>
                            <input wire:model="othernames" id="adm-othernames" type="text" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                            @error('othernames') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="adm-phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Phone Number') }}</label>
                            <input wire:model="phone_number" id="adm-phone" type="tel" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                            @error('phone_number') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        @if ($userType === 'admin')
                            <div>
                                <label for="adm-ghana" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Ghana Card number') }}</label>
                                <input wire:model="ghana_card" id="adm-ghana" type="text" required minlength="6" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="GHA-XXXXXXXXX-X" />
                                @error('ghana_card') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            @if ($schoolReady)
                                <div>
                                    <label for="adm-gender" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Gender') }}</label>
                                    <select wire:model="gender" id="adm-gender" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                        <option value="">{{ __('Select…') }}</option>
                                        <option value="male">{{ __('Male') }}</option>
                                        <option value="female">{{ __('Female') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </select>
                                    @error('gender') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        @endif

                        @if ($userType === 'staff')
                            <div>
                                <label for="adm-dept" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Department') }}</label>
                                <select wire:model="department_id" id="adm-dept" required class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <option value="">{{ __('Select Department…') }}</option>
                                    @foreach ($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Section: Academic & Work Info (Hidden for Owner/Setup flow) -->
                    @if (! $isOwner)
                        <div class="border-t border-gray-150 dark:border-gray-700/50 pt-5 space-y-6">
                            <h4 class="text-sm font-bold uppercase tracking-wider text-purple-650 dark:text-purple-400">{{ __('Work & Organization') }}</h4>
                            
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="adm-position" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Position title') }}</label>
                                    <input wire:model="position_title" id="adm-position" type="text" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                    @error('position_title') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>

                                @if ($userType === 'admin')
                                    @if ($roleSlug === 'hod')
                                        <div class="sm:col-span-2">
                                            <label for="adm-dept2" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Department') }}</label>
                                            <select wire:model="department_id" id="adm-dept2" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                                <option value="">{{ __('Select…') }}</option>
                                                @foreach ($departments as $d)
                                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('department_id') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                    
                                    @if ($roleSlug === 'dean')
                                        <div class="sm:col-span-2">
                                            <label for="adm-fac" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Faculty') }}</label>
                                            <select wire:model="faculty_id" id="adm-fac" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                                <option value="">{{ __('Select…') }}</option>
                                                @foreach ($faculties as $f)
                                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('faculty_id') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                    @endif

                                    <div>
                                        <label for="adm-doa" class="block text-sm font-semibold text-gray-700 dark:text-gray-305">{{ __('Date of appointment') }}</label>
                                        <input wire:model="date_of_appointment" id="adm-doa" type="date" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-550 focus:ring-purple-550 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                        @error('date_of_appointment') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end border-t border-gray-100 dark:border-gray-700/50 pt-4 mt-6">
                        <x-college-form-submit target="save" class="rounded-lg px-6 py-2.5">
                            <i class="fa-solid fa-circle-check mr-2"></i>{{ $schoolReady ? __('Update Profile') : __('Set up Account') }}
                        </x-college-form-submit>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</div>
