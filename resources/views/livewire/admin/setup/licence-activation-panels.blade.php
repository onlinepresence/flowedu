{{-- Shared licence activation switcher: one mode form at a time.
     Hosts (setup wizard + settings activation modal) expose the same
     wire:model names (mode, enrollCode, elevationCode, offlineCode,
     importBlob) and action methods (redeemCode, activateWithExistingData,
     continueOffline, importLicence, continueSetup).
     Expected vars: $mode, $idPrefix, $enrollError, $manualLines,
     $manualPath, $hasExistingData, $showContinueSetup. --}}
@if(($enrollError ?? '') !== '')
    <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300" role="alert">
        {{ $enrollError }}
    </p>
@endif

@if(! empty($manualLines ?? []))
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900/50 dark:bg-amber-950/20">
        <h2 class="text-base font-bold text-amber-900 dark:text-amber-200">{{ __('Licence accepted — one manual step left') }}</h2>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
            {{ __('The licence row is seeded, but the server settings could not be written. Paste these exact lines into :path, then continue.', ['path' => $manualPath ?? base_path('.env')]) }}
        </p>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 font-mono text-xs text-green-300">@foreach($manualLines as $line){{ $line }}{{ "\n" }}@endforeach</pre>
        @if($showContinueSetup ?? false)
            <button
                type="button"
                wire:click="continueSetup"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none"
            >
                {{ __('Continue setup') }}
            </button>
        @endif
    </div>
@endif

<div class="flex justify-center">
    <div class="inline-flex w-full max-w-lg rounded-xl border border-gray-200 bg-gray-100 p-1 dark:border-gray-700 dark:bg-gray-900/60" role="tablist" aria-label="{{ __('Activation method') }}">
        <button
            type="button"
            role="tab"
            aria-selected="{{ ($mode ?? 'redeem') === 'redeem' ? 'true' : 'false' }}"
            wire:click="$set('mode', 'redeem')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ ($mode ?? 'redeem') === 'redeem' ? 'bg-white text-purple-700 shadow dark:bg-gray-800 dark:text-purple-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
        >
            {{ __('Redeem code') }}
        </button>
        <button
            type="button"
            role="tab"
            aria-selected="{{ ($mode ?? '') === 'import' ? 'true' : 'false' }}"
            wire:click="$set('mode', 'import')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ ($mode ?? '') === 'import' ? 'bg-white text-purple-700 shadow dark:bg-gray-800 dark:text-purple-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
        >
            {{ __('Import file') }}
        </button>
        <button
            type="button"
            role="tab"
            aria-selected="{{ ($mode ?? '') === 'offline' ? 'true' : 'false' }}"
            wire:click="$set('mode', 'offline')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition {{ ($mode ?? '') === 'offline' ? 'bg-white text-purple-700 shadow dark:bg-gray-800 dark:text-purple-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
        >
            {{ __('Continue offline') }}
        </button>
    </div>
</div>

@if(($mode ?? 'redeem') === 'redeem')
    <div class="space-y-6" role="tabpanel">
        @if($hasExistingData ?? false)
            <div class="rounded-xl border-2 border-emerald-200 bg-emerald-50/60 p-6 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <h2 class="text-base font-bold text-emerald-900 dark:text-emerald-200">{{ __('Continue with existing data and activate') }}</h2>
                <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">{{ __('For installs already holding real data. Takes a database backup first, then redeems your code and clears provisional status.') }}</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label for="{{ $idPrefix }}elevation-code" class="block text-xs font-semibold text-emerald-700 uppercase tracking-wider dark:text-emerald-400">{{ __('Enrollment code') }}</label>
                        <input wire:model="elevationCode" id="{{ $idPrefix }}elevation-code" type="text" autocomplete="off" placeholder="{{ __('e.g. APEX-2026-XXXX') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono" />
                        @error('elevationCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="activateWithExistingData"
                        wire:loading.attr="disabled"
                        wire:target="activateWithExistingData"
                        class="inline-flex w-full justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="activateWithExistingData" class="inline-flex items-center gap-2">
                            {{ __('Back up & activate with my data') }}
                        </span>
                        <span wire:loading.delay.200ms wire:target="activateWithExistingData" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                            {{ __('Backing up…') }}
                        </span>
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Start fresh: Reinstall a fresh copy of FlowEdu separately, then redeem your code at this same licence step.') }}</p>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Redeem code now') }}</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Enter the enrollment code ops gave you. Needs internet.') }}</p>
            <div class="mt-4 space-y-3">
                <div>
                    <label for="{{ $idPrefix }}enroll-code" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Enrollment code') }}</label>
                    <input wire:model="enrollCode" id="{{ $idPrefix }}enroll-code" type="text" autocomplete="off" placeholder="{{ __('e.g. APEX-2026-XXXX') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono" />
                    @error('enrollCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button
                    type="button"
                    wire:click="redeemCode"
                    wire:loading.attr="disabled"
                    wire:target="redeemCode"
                    class="inline-flex w-full justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="redeemCode" class="inline-flex items-center gap-2">
                        {{ __('Redeem & continue') }}
                    </span>
                    <span wire:loading.delay.200ms wire:target="redeemCode" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                        {{ __('Redeeming…') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
@elseif(($mode ?? '') === 'import')
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" role="tabpanel">
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
                <label for="{{ $idPrefix }}import-blob" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Signed blob') }}</label>
                <textarea wire:model="importBlob" id="{{ $idPrefix }}import-blob" rows="4" spellcheck="false" placeholder='{"payload": {...}, "signature": "..."}' class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs font-mono"></textarea>
                @error('importBlob') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button
                type="button"
                wire:click="importLicence"
                wire:loading.attr="disabled"
                wire:target="importLicence"
                class="inline-flex w-full justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="importLicence" class="inline-flex items-center gap-2">
                    {{ __('Verify & continue') }}
                </span>
                <span wire:loading.delay.200ms wire:target="importLicence" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                    {{ __('Verifying…') }}
                </span>
            </button>
        </div>
    </div>
@else
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" role="tabpanel">
        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Continue offline') }}</h2>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Start with a provisional core-only licence. Optionally leave a code to redeem automatically when online.') }}</p>
        <div class="mt-4 space-y-3">
            <div>
                <label for="{{ $idPrefix }}offline-code" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">{{ __('Code (optional)') }}</label>
                <input wire:model="offlineCode" id="{{ $idPrefix }}offline-code" type="text" autocomplete="off" placeholder="{{ __('Saved for silent retry') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono" />
            </div>
            <button
                type="button"
                wire:click="continueOffline"
                wire:loading.attr="disabled"
                wire:target="continueOffline"
                class="inline-flex w-full justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="continueOffline" class="inline-flex items-center gap-2">
                    {{ __('Continue offline') }}
                </span>
                <span wire:loading.delay.200ms wire:target="continueOffline" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                    {{ __('Please wait…') }}
                </span>
            </button>
        </div>
    </div>
@endif
