<div class="mx-auto max-w-5xl space-y-6">
    <!-- Onboarding Stepper Component -->
    <x-college.stepper :current="2" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left: List of Added Guardians -->
        <div class="lg:col-span-1 space-y-6">
            <x-card class="p-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-200 pb-3 dark:border-gray-700">
                    {{ __('Added Guardians') }}
                </h3>

                <div class="divide-y divide-gray-200 dark:divide-gray-700 my-4">
                    @forelse ($guardians as $g)
                        <div class="flex items-center justify-between py-3 group" wire:key="guardian-setup-{{ $g->id }}">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $g->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center rounded-full bg-purple-50 dark:bg-purple-950 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-300 mr-1 capitalize">{{ $g->relationship }}</span>
                                    · {{ $g->phone_number }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <button type="button" wire:click="edit({{ $g->id }})" class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                                    {{ __('Edit') }}
                                </button>
                                <button type="button" wire:click="delete({{ $g->id }})" wire:confirm="{{ __('Are you sure you want to remove this guardian?') }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('No guardians added yet.') }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ __('Please add at least one guardian below to proceed.') }}</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Right: Guardian Form -->
        <div class="lg:col-span-2">
            <x-card class="p-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-200 pb-3 dark:border-gray-700">
                    {{ $guardian_id ? __('Edit Guardian details') : __('Add Guardian details') }}
                </h3>

                <form wire:submit="save" class="space-y-4 pt-4">
                    <input type="hidden" wire:model="student_id" />
                    <input type="hidden" wire:model="guardian_id" />

                    <div>
                        <x-input-label for="g-name" :value="__('Full name')" />
                        <x-text-input wire:model="name" id="g-name" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-rel" :value="__('Relationship')" />
                        <x-select-input wire:model="relationship" id="g-rel" class="mt-1 block w-full" required>
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
                        <x-input-error :messages="$errors->get('relationship')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-phone" :value="__('Phone number')" />
                        <x-text-input wire:model="phone_number" id="g-phone" type="tel" class="mt-1 block w-full font-mono" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-email" :value="__('Email (optional)')" />
                        <x-text-input wire:model="email" id="g-email" type="email" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="g-address" :value="__('Address (optional)')" />
                        <x-textarea-input wire:model="address" id="g-address" rows="2" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-college-form-submit target="save" class="px-6 justify-center">
                            {{ $guardian_id ? __('Save Changes') : __('Add Guardian') }}
                        </x-college-form-submit>
                        @if ($guardian_id)
                            <button type="button" wire:click="startNew" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                {{ __('Cancel Edit') }}
                            </button>
                        @endif
                    </div>
                </form>
            </x-card>
        </div>
    </div>

    <!-- Navigation Row -->
    <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
        <a href="{{ route('student.setup.personal') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('Back to Step 1') }}
        </a>

        @if ($guardians->isNotEmpty())
            <a href="{{ route('student.setup.status') }}" class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors duration-200">
                {{ __('Continue to Step 3') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        @else
            <span class="inline-flex items-center gap-2 rounded-md bg-gray-200 dark:bg-gray-700 px-4 py-2 text-sm font-bold text-gray-400 dark:text-gray-500 cursor-not-allowed" title="{{ __('Add at least one guardian to proceed') }}">
                {{ __('Continue to Step 3') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </span>
        @endif
    </div>
</div>
