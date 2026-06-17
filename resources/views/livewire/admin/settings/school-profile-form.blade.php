<div class="mx-auto max-w-5xl space-y-6">
    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <!-- Left 2 Columns: Profile Details -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Card 1: General Information -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-graduation-cap text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('General Information') }}</h2>
                </div>

                <div class="space-y-5">
                    <div>
                        <label for="school-name" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('School Name') }} <span class="text-red-500">*</span></label>
                        <input wire:model="name" id="school-name" type="text" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" required />
                        @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="school-motto" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Motto / Slogan') }}</label>
                            <input wire:model="motto" id="school-motto" type="text" placeholder="e.g. Knowledge & Excellence" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('motto') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="school-established" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Established Year') }}</label>
                            <input wire:model="established_year" id="school-established" type="number" min="1800" max="{{ date('Y') }}" placeholder="e.g. 2012" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('established_year') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="school-principal" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Principal / Head Name') }}</label>
                        <input wire:model="principal_name" id="school-principal" type="text" placeholder="e.g. Prof. John Doe" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                        @error('principal_name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="school-description" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Description') }}</label>
                        <textarea wire:model="description" id="school-description" rows="4" placeholder="Brief history or description of the institution..." class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Card 2: Contact & Social Links -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-address-book text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Contact & Social Links') }}</h2>
                </div>

                <div class="space-y-5">
                    <div>
                        <label for="school-address" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Physical Address') }} <span class="text-red-500">*</span></label>
                        <textarea wire:model="address" id="school-address" rows="2" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" required></textarea>
                        @error('address') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="school-email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Email') }}</label>
                            <input wire:model="email" id="school-email" type="email" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="school-phone" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Phone') }}</label>
                            <input wire:model="phone" id="school-phone" type="text" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('phone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="school-website" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Website') }}</label>
                        <input wire:model="website" id="school-website" type="url" placeholder="https://example.edu" class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                        @error('website') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Social Media Links -->
                    <div class="grid gap-4 sm:grid-cols-2 pt-2">
                        <div>
                            <label for="facebook-url" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <i class="fa-brands fa-facebook text-blue-600 mr-1 text-sm"></i>{{ __('Facebook Page URL') }}
                            </label>
                            <input wire:model="facebook_url" id="facebook-url" type="url" placeholder="https://facebook.com/..." class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('facebook_url') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="twitter-url" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <i class="fa-brands fa-x-twitter text-gray-900 dark:text-white mr-1 text-sm"></i>{{ __('Twitter / X URL') }}
                            </label>
                            <input wire:model="twitter_url" id="twitter-url" type="url" placeholder="https://twitter.com/..." class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('twitter_url') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="linkedin-url" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <i class="fa-brands fa-linkedin text-blue-700 mr-1 text-sm"></i>{{ __('LinkedIn Institution URL') }}
                            </label>
                            <input wire:model="linkedin_url" id="linkedin-url" type="url" placeholder="https://linkedin.com/school/..." class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('linkedin_url') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="instagram-url" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <i class="fa-brands fa-instagram text-pink-600 mr-1 text-sm"></i>{{ __('Instagram Page URL') }}
                            </label>
                            <input wire:model="instagram_url" id="instagram-url" type="url" placeholder="https://instagram.com/..." class="mt-1.5 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" />
                            @error('instagram_url') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings & Logo -->
        <div class="space-y-6 lg:col-span-1">
            <!-- Card 3: School Logo -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-image text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Institution Logo') }}</h2>
                </div>

                <div class="space-y-4">
                    @if ($logoUrl)
                        <div class="flex flex-col items-center justify-center p-4 rounded-xl bg-gray-50 border border-gray-150 dark:bg-gray-900/40 dark:border-gray-700">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ __('Current Logo') }}</span>
                            <img src="{{ $logoUrl }}" alt="{{ $name }}" class="h-28 w-auto max-w-full rounded-lg object-contain bg-white p-2 shadow-sm border border-gray-200 dark:border-gray-700" />
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center p-6 rounded-xl bg-gray-50 border border-dashed border-gray-300 dark:bg-gray-900/40 dark:border-gray-750">
                            <i class="fa-regular fa-image text-gray-400 text-3xl mb-2"></i>
                            <span class="text-xs font-semibold text-gray-500">{{ __('No logo uploaded') }}</span>
                        </div>
                    @endif

                    <div class="pt-2">
                        <x-filepond
                            field="logoFilepondPath"
                            purpose="school_logo"
                            :label="__('Upload New Logo')"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                        />
                        @error('logoFilepondPath') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Card 4: Settings & Submission -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-sliders text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Admissions Settings') }}</h2>
                </div>

                <div class="space-y-6">
                    <div class="flex items-start justify-between">
                        <div class="space-y-0.5 pr-4">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Enable Admissions') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Allow online student application submissions.') }}</p>
                        </div>
                        <div class="flex items-center">
                            <!-- Toggle Switch for is_admit -->
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model="is_admit" class="peer sr-only">
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 dark:border-gray-700">
                        <x-college-form-submit target="save" class="w-full justify-center rounded-lg py-2.5">
                            <i class="fa-solid fa-cloud-arrow-up mr-2"></i>{{ __('Save Profile') }}
                        </x-college-form-submit>
                        
                        @if(!$isSetupFlow)
                            <a href="{{ route('admin.dashboard') }}" wire:navigate class="mt-2.5 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                {{ __('Cancel') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
