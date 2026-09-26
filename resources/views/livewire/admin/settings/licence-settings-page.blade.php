<div class="mx-auto max-w-5xl space-y-6">
    <!-- Header title (redundant in linked mode: the layout title above already says it) -->
    @unless($isLinked)
    <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Licence & Subscription Settings') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage Core features and modular extensions for this installation.') }}</p>
        </div>
    </div>
    @endunless

    @if($isLinked)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ __('Managed by ControlDesk (ref: :ref). Modules are frozen to your plan — core settings below can still be changed.', ['ref' => $external_ref]) }}
        </div>
    @endif
    @php($locked = $isLinked)
    @if($isProvisional)
        <p>
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('PROVISIONAL LICENCE') }}</span>
            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Core-only until ControlDesk redemption succeeds.') }}</span>
        </p>
    @endif
    @if($errors->has('form'))
        <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300" role="alert">
            {{ $errors->first('form') }}
        </p>
    @endif
    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <!-- Left 2 Cols: Features and Modules -->
        <div class="space-y-6 lg:col-span-2">
            
            <!-- Section A: Core Features -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Core Academic System') }}</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($coreCatalog as $key => $feat)
                        <div class="flex items-start justify-between py-4" wire:key="core-{{ $key }}">
                            <div class="space-y-1 pr-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __($feat['label']) }}</span>
                                    @if ($feat['locked'])
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-500/10 dark:text-purple-400 dark:ring-purple-500/20">
                                            <i class="fa-solid fa-lock mr-1 text-[10px]"></i>{{ __('Always Included') }}
                                        </span>
                                    @elseif (($showGrantBadges ?? false))
                                        @if (($coreGrants[$key] ?? true))
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">{{ __('On your plan') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-700/10 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">{{ __('Not included — contact ops') }}</span>
                                        @endif
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __($feat['description']) }}</p>
                            </div>
                            <div class="flex items-center">
                                @if ($feat['locked'])
                                    <div class="flex h-6 w-11 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </div>
                                @else
                                    <!-- Toggle Switch (display truth: ungranted boxes disabled at render on linked installs; saving stays permissive) -->
                                    <label class="relative inline-flex items-center {{ ($showGrantBadges ?? false) && ! ($coreGrants[$key] ?? true) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                                        <input type="checkbox" wire:model.live="coreStates.{{ $key }}" class="peer sr-only" @disabled(($showGrantBadges ?? false) && ! ($coreGrants[$key] ?? true))>
                                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                                    </label>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section B: Add-on Modules -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-puzzle-piece text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Modular Extensions') }}</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($modulesCatalog as $key => $feat)
                        <div class="relative flex flex-col justify-between rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700/50 dark:bg-gray-900/40" wire:key="mod-{{ $key }}">
                            <div class="mb-3 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __($feat['label']) }}</span>
                                    <label class="relative inline-flex {{ $locked ?? false ? 'cursor-not-allowed' : 'cursor-pointer' }} items-center">
                                        <input type="checkbox" wire:model.live="moduleStates.{{ $key }}" class="peer sr-only" @disabled($locked ?? false)>
                                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                                    </label>
                                </div>
                                <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">{{ __($feat['description']) }}</p>
                            </div>
                            <div class="mt-2 border-t border-gray-200/50 pt-2 flex items-center justify-between text-xs text-gray-400 dark:border-gray-700/50">
                                <span>{{ __('Base annual price') }}</span>
                                <span class="font-semibold font-mono text-gray-700 dark:text-gray-300">
                                    {{ number_format((float)$feat['base_price'], 2) }} {{ config('licence.currency', 'GHS') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section C: Subscription Terms -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-contract text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Subscription Metadata') }}</h2>
                </div>
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="licence-start" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Licence start date') }}</label>
                        <input wire:model="licence_start" id="licence-start" type="date" @disabled($locked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                        @error('licence_start') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support-until" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Support expiration date') }}</label>
                        <input wire:model="support_until" id="support-until" type="date" @disabled($locked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                        @error('support_until') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Pricing & Caps -->
        <div class="space-y-6 lg:col-span-1">
            <!-- Student Capacity Cap -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-gear text-purple-600 dark:text-purple-400 text-lg"></i>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Capacity & Reference') }}</h2>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="max-students" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Max Active Students') }}</label>
                        <input wire:model.live="max_active_students" id="max-students" type="number" min="0" placeholder="{{ __('No limit') }}" @disabled($locked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                        @error('max_active_students') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="external-ref" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Reference / Invoice ID') }}</label>
                        <input wire:model="external_ref" id="external-ref" type="text" @disabled($locked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                        @error('external_ref') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Dynamic Pricing Panel -->
            <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-6 shadow-sm dark:border-purple-900/50 dark:bg-purple-950/20">
                <h3 class="text-base font-bold text-purple-900 dark:text-purple-300 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-calculator"></i>
                    {{ __('Pricing Projection') }}
                </h3>
                <div class="space-y-3 text-xs text-purple-800 dark:text-purple-400">
                    <div class="flex justify-between">
                        <span>{{ __('Student pricing band') }}:</span>
                        <span class="font-bold text-purple-900 dark:text-purple-200">{{ $pricingPreview['band_label'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ __('Multiplier') }}:</span>
                        <span class="font-mono font-bold text-purple-900 dark:text-purple-200">{{ number_format((float)$pricingPreview['multiplier'], 2) }}x</span>
                    </div>
                    <div class="border-t border-purple-200/50 my-2 dark:border-purple-800/50"></div>
                    <div class="flex justify-between">
                        <span>{{ __('Core Annual Fee') }}:</span>
                        <span class="font-mono text-purple-900 dark:text-purple-200">
                            {{ number_format((float)$pricingPreview['core_annual'], 2) }} {{ config('licence.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ __('Modules Annual (x:count)', ['count' => $pricingPreview['active_modules_count']]) }}:</span>
                        <span class="font-mono text-purple-900 dark:text-purple-200">
                            {{ number_format((float)$pricingPreview['modules_annual'], 2) }} {{ config('licence.currency', 'GHS') }}
                        </span>
                    </div>
                    @if($pricingPreview['discount'] > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400 font-semibold">
                            <span>{{ __('Bundle Discount (:pct%)', ['pct' => rtrim(rtrim(number_format((float) config('licence.bundle_discount', 0.12) * 100, 2), '0'), '.')]) }}:</span>
                            <span class="font-mono">
                                -{{ number_format((float)$pricingPreview['discount'], 2) }} {{ config('licence.currency', 'GHS') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>{{ __('Hosting Fee (Annual)') }}:</span>
                        <span class="font-mono text-purple-900 dark:text-purple-200">
                            {{ number_format((float)$pricingPreview['hosting'], 2) }} {{ config('licence.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="border-t border-purple-200/50 my-2 dark:border-purple-800/50"></div>
                    <div class="flex justify-between font-bold text-sm text-purple-950 dark:text-purple-200">
                        <span>{{ __('Total Annual recurring') }}:</span>
                        <span class="font-mono">
                            {{ number_format((float)$pricingPreview['total_annual'], 2) }} {{ config('licence.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-purple-800/80 dark:text-purple-400/80">
                        <span>{{ __('Total Setup & onboarding') }}:</span>
                        <span class="font-mono">
                            {{ number_format((float)$pricingPreview['total_setup'], 2) }} {{ config('licence.currency', 'GHS') }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 border-t border-purple-200/60 pt-4 dark:border-purple-800/60">
                    @if($locked ?? false)
                        <button
                            type="button"
                            wire:click="saveCoreFeatures"
                            wire:loading.attr="disabled"
                            wire:target="saveCoreFeatures"
                            class="inline-flex w-full justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="saveCoreFeatures" class="inline-flex items-center gap-2">
                                {{ __('Save core settings') }}
                            </span>
                            <span wire:loading.delay.200ms wire:target="saveCoreFeatures" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                {{ __('Please wait…') }}
                            </span>
                        </button>
                        <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Modules frozen by ControlDesk.') }}</p>
                    @else
                        <x-college-form-submit target="save" class="w-full justify-center">
                            {{ __('Save licensing') }}
                        </x-college-form-submit>
                    @endif
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="mt-2 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>

</div>
