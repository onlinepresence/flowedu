<div class="w-full space-y-6">
    @if(($notice ?? '') !== '')
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ $notice }}
        </div>
    @endif
    @if(($isDemo ?? false))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ __('Demo trial — full access. Every module is unlocked; no ControlDesk link needed.') }}
            @php
                $demoBannerHosts = [];
                if (is_array($demoStatus ?? null)) {
                    $rawHost = ($demoStatus ?? [])['host'] ?? null;
                    $demoBannerHosts = is_array($rawHost)
                        ? array_values(array_filter(array_map('strval', $rawHost)))
                        : (is_string($rawHost) && trim($rawHost) !== '' ? [trim($rawHost)] : []);
                }
            @endphp
            @if(is_array($demoStatus ?? null) && ! (($demoStatus ?? [])['never'] ?? true) && trim((string) (($demoStatus ?? [])['expires_at'] ?? '')) !== '')
                <span class="font-semibold">{{ __('Valid until :date', ['date' => ($demoStatus ?? [])['expires_at']]) }}</span>@if($demoBannerHosts !== []). {{ __('Locked to :hosts', ['hosts' => implode(', ', $demoBannerHosts)]) }}@endif
            @elseif(is_array($demoStatus ?? null) && (($demoStatus ?? [])['never'] ?? false))
                <span class="font-semibold">{{ __('No expiry.') }}</span>
            @endif
        </div>
    @elseif($isLinked && ($isLive ?? false))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ __('Managed by ControlDesk (ref: :ref). Only modules on your plan can be switched on here — core settings below can still be changed.', ['ref' => $external_ref]) }}
        </div>
    @elseif($isLinked)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
            <i class="fa-solid fa-circle-exclamation mr-2"></i><span class="font-bold">{{ __('LICENCE INACTIVE') }}</span>{{ __(' — your ControlDesk licence has expired or is inactive. Core-only until reactivated.') }}
        </div>
    @elseif(! $isProvisional && ! ($isDemo ?? false))
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
            <i class="fa-solid fa-circle-info mr-2"></i>{{ __('This install is not linked to ControlDesk. Activate your licence to unlock modular extensions.') }}
        </div>
    @endif
    @if($isProvisional)
        <div class="rounded-xl border border-dotted border-amber-400 bg-amber-100 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/60 dark:bg-amber-950/40 dark:text-amber-100">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i><span class="font-bold">{{ __('PROVISIONAL LICENCE') }}</span>{{ __(' — Core-only until ControlDesk redemption succeeds.') }}
        </div>
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
                @if(($isLive ?? false) || ($isDemo ?? false))
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($grantedModules as $key => $feat)
                            <div class="relative flex flex-col justify-between rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700/50 dark:bg-gray-900/40" wire:key="mod-{{ $key }}">
                                <div class="mb-3 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __($feat['label']) }}</span>
                                        <label class="relative inline-flex cursor-pointer items-center">
                                            <input type="checkbox" wire:model.live="moduleStates.{{ $key }}" class="peer sr-only">
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
                    @if(count($otherModules ?? []) > 0)
                        <div x-data="{ open: false }" class="mt-4">
                            <button
                                type="button"
                                @click="open = ! open"
                                :aria-expanded="open"
                                class="flex w-full items-center justify-between rounded-xl border border-dashed border-gray-300 bg-gray-50/50 px-4 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-200"
                            >
                                <span>
                                    <i class="fa-solid fa-layer-group mr-2 text-gray-400" aria-hidden="true"></i>{{ __('Other modules (:count) — not on your plan', ['count' => count($otherModules)]) }}
                                </span>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-transition class="mt-3 grid gap-4 sm:grid-cols-2" style="display: none;">
                                @foreach ($otherModules as $key => $feat)
                                    <div class="relative flex flex-col justify-between rounded-xl border border-gray-100 bg-gray-50 p-4 opacity-70 dark:border-gray-700/50 dark:bg-gray-900/40" wire:key="mod-other-{{ $key }}">
                                        <div class="mb-3 space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __($feat['label']) }}</span>
                                                <label class="relative inline-flex cursor-not-allowed items-center">
                                                    <input type="checkbox" class="peer sr-only" disabled @checked((bool) ($moduleStates[$key] ?? false))>
                                                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                                                </label>
                                            </div>
                                            <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">{{ __($feat['description']) }}</p>
                                            <p class="text-[11px] font-medium text-amber-600 dark:text-amber-400">{{ __('Not included — contact ops to add it.') }}</p>
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
                    @endif
                @else
                    <div class="py-8 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-900">
                            <i class="fa-solid fa-puzzle-piece text-xl text-gray-400 dark:text-gray-500" aria-hidden="true"></i>
                        </div>
                        <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">{{ __('Modular extensions unavailable') }}</h3>
                        <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            {{ __('Your licence is not active, so only the core system is available. Verify your installation, provide a licence, or reactivate an expired one to unlock modular extensions.') }}
                        </p>
                        <button
                            type="button"
                            wire:click="openActivationModal"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none"
                        >
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            {{ __('Activate licence') }}
                        </button>
                    </div>
                @endif
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
                        <input wire:model="licence_start" id="licence-start" type="date" @disabled($isLinked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                        @error('licence_start') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="support-until" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Support expiration date') }}</label>
                        <input wire:model="support_until" id="support-until" type="date" @disabled($isLinked ?? false) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
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
                        <input wire:model.live="max_active_students" id="max-students" type="number" min="0" placeholder="{{ __('No limit') }}" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                    </div>
                    <div>
                        <label for="external-ref" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Reference / Invoice ID') }}</label>
                        <input wire:model="external_ref" id="external-ref" type="text" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm disabled:opacity-70" />
                    </div>
                    <p class="text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">{{ __('Centrally managed — editing returns in a future update.') }}</p>
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
                    @if(($isLinked ?? false) && ! ($isLive ?? false))
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
                        <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Core-only until reactivation.') }}</p>
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

    <x-college.modal name="licence-activation-modal" :title="__('Activate licence')" maxWidth="2xl">
        @include('livewire.admin.setup.licence-activation-panels', [
            'mode' => $mode,
            'idPrefix' => 'modal-',
            'enrollError' => $enrollError,
            'manualLines' => $manualLines,
            'manualPath' => $manualPath,
            'hasExistingData' => $hasExistingData ?? false,
            'showContinueSetup' => false,
        ])
        <x-slot name="footer">
            <button
                type="button"
                wire:click="$dispatch('close-modal', 'licence-activation-modal')"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
                {{ __('Close') }}
            </button>
        </x-slot>
    </x-college.modal>

</div>
