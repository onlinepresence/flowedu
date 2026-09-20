<div class="mx-auto max-w-5xl space-y-6">
    @if($enrollmentChoice === 'pending')
        @if($enrollError !== '')
            <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300" role="alert">
                {{ $enrollError }}
            </p>
        @endif

        @if(! empty($manualLines))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900/50 dark:bg-amber-950/20">
                <h2 class="text-base font-bold text-amber-900 dark:text-amber-200">{{ __('Licence accepted — one manual step left') }}</h2>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                    {{ __('The licence row is seeded, but the server settings could not be written. Paste these exact lines into :path, then continue.', ['path' => $manualPath]) }}
                </p>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 font-mono text-xs text-green-300">@foreach($manualLines as $line){{ $line }}{{ "\n" }}@endforeach</pre>
                <button
                    type="button"
                    wire:click="continueSetup"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none"
                >
                    {{ __('Continue setup') }}
                </button>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Exit (a): redeem code -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Redeem code now') }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Enter the enrollment code ops gave you. Needs internet.') }}</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label for="enroll-code" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Enrollment code') }}</label>
                        <input wire:model="enrollCode" id="enroll-code" type="text" autocomplete="off" placeholder="{{ __('e.g. APEX-2026-XXXX') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono" />
                        @error('enrollCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="redeemCode"
                        class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none"
                    >
                        {{ __('Redeem & continue') }}
                    </button>
                </div>
            </div>

            <!-- Exit (b): continue offline -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Continue offline') }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Start with a provisional core-only licence. Optionally leave a code to redeem automatically when online.') }}</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label for="offline-code" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Code (optional)') }}</label>
                        <input wire:model="offlineCode" id="offline-code" type="text" autocomplete="off" placeholder="{{ __('Saved for silent retry') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono" />
                    </div>
                    <button
                        type="button"
                        wire:click="continueOffline"
                        class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ __('Continue offline') }}
                    </button>
                </div>
            </div>

            <!-- Exit (c): import licence file -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Import licence file') }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Paste the signed blob, or pick the file — it is read locally and verified.') }}</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <input
                            type="file"
                            accept=".json,.txt,application/json"
                            x-data
                            x-on:change="const f = $event.target.files[0]; if (!f) return; const r = new FileReader(); r.onload = (e) => $wire.set('importBlob', e.target.result); r.readAsText(f);"
                            class="block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:rounded-md file:border-0 file:bg-purple-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-950/40 dark:file:text-purple-300"
                        />
                    </div>
                    <div>
                        <label for="import-blob" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Signed blob') }}</label>
                        <textarea wire:model="importBlob" id="import-blob" rows="4" spellcheck="false" placeholder='{"payload": {...}, "signature": "..."}' class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs font-mono"></textarea>
                        @error('importBlob') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="importLicence"
                        class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ __('Verify & continue') }}
                    </button>
                </div>
            </div>
        </div>
    @else
    @if($enrollmentChoice === 'linked')
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
        <i class="fa-solid fa-circle-check mr-2"></i>{{ __('Managed by ControlDesk (ref: :ref). Modules are frozen to your plan — core settings below can still be changed.', ['ref' => $external_ref]) }}
    </div>
    @else
    <p class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-100">
        @if($enrollmentChoice === 'provisional')
            <span class="mr-2 inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('PROVISIONAL') }}</span>
        @endif
        {{ __('Select the features and modules that match your agreement or trial. You can change this later under System Settings → Licence & subscription (super-admin only).') }}
    </p>
    @endif
    @php($locked = $enrollmentChoice === 'linked')

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
                                    <!-- Toggle Switch -->
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" wire:model.live="coreStates.{{ $key }}" class="peer sr-only">
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
                                    {{ number_format((float)$feat['base_price'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
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

        <!-- Right Col: Pricing & Caps (sticky on large screens) -->
        <div class="space-y-6 self-start lg:col-span-1 lg:sticky lg:top-8">
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
                            {{ number_format((float)$pricingPreview['core_annual'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ __('Modules Annual (x:count)', ['count' => $pricingPreview['active_modules_count']]) }}:</span>
                        <span class="font-mono text-purple-900 dark:text-purple-200">
                            {{ number_format((float)$pricingPreview['modules_annual'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
                        </span>
                    </div>
                    @if($pricingPreview['discount'] > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400 font-semibold">
                            <span>{{ __('Bundle Discount (:pct%)', ['pct' => rtrim(rtrim(number_format($bundleRate * 100, 2), '0'), '.')]) }}:</span>
                            <span class="font-mono">
                                -{{ number_format((float)$pricingPreview['discount'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>{{ __('Hosting Fee (Annual)') }}:</span>
                        <span class="font-mono text-purple-900 dark:text-purple-200">
                            {{ number_format((float)$pricingPreview['hosting'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="border-t border-purple-200/50 my-2 dark:border-purple-800/50"></div>
                    <div class="flex justify-between font-bold text-sm text-purple-950 dark:text-purple-200">
                        <span>{{ __('Total Annual recurring') }}:</span>
                        <span class="font-mono">
                            {{ number_format((float)$pricingPreview['total_annual'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-purple-800/80 dark:text-purple-400/80">
                        <span>{{ __('Total Setup & onboarding') }}:</span>
                        <span class="font-mono">
                            {{ number_format((float)$pricingPreview['total_setup'], 2) }} {{ config('licence.pricing.currency', 'GHS') }}
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
                        <button
                            type="button"
                            wire:click="continueSetup"
                            class="mt-2 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            {{ __('Continue setup') }}
                        </button>
                    @else
                        <x-college-form-submit target="save" class="w-full justify-center">
                            {{ __('Continue to faculties') }}
                        </x-college-form-submit>
                    @endif
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
