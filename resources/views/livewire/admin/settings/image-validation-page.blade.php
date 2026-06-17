<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        tab: 'config',
        r: @entangle('passport_bg_color_r').live,
        g: @entangle('passport_bg_color_g').live,
        b: @entangle('passport_bg_color_b').live,
        aspectRatio: @entangle('passport_aspect_ratio').live,
        aspectRatios: { '7:9': [7, 9], '3:4': [3, 4], '1:1': [1, 1] },
        get aspectStyle() {
            let w = 150;
            let ratioArr = this.aspectRatios[this.aspectRatio] || [7, 9];
            let h = Math.round(w * (ratioArr[1] / ratioArr[0]));
            return 'width:' + w + 'px; height:' + h + 'px; background-color:rgb(' + this.r + ',' + this.g + ',' + this.b + ');';
        },
        setPresetAndInputs(r, g, b) {
            this.r = r; this.g = g; this.b = b;
        }
    }"
>
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-905/40 dark:bg-green-950/40 dark:text-green-200 shadow-sm" role="status">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <!-- Tabs Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
                type="button"
                @click="tab = 'config'"
                :class="tab === 'config' ? 'border-purple-650 text-purple-650 dark:border-purple-400 dark:text-purple-400 border-b-2' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap pb-2 px-1 text-sm font-semibold transition"
            >
                <i class="fa-solid fa-sliders mr-2"></i>{{ __('Configuration Settings') }}
            </button>
            <button
                type="button"
                @click="tab = 'demo'"
                :class="tab === 'demo' ? 'border-purple-650 text-purple-650 dark:border-purple-400 dark:text-purple-400 border-b-2' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap pb-2 px-1 text-sm font-semibold transition"
            >
                <i class="fa-solid fa-vial-circle-check mr-2"></i>{{ __('Demo Sandbox') }}
            </button>
        </nav>
    </div>

    <!-- Configuration Tab Content -->
    <div x-show="tab === 'config'" x-transition class="space-y-6">
        <form wire:submit="saveSettings" class="space-y-6">
            <!-- Panel 1: Background Color -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-palette text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Expected Background Color (RGB)') }}</h2>
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label :value="__('Red Channel (0–255)')" />
                        <x-text-input wire:model.blur="passport_bg_color_r" id="passport_bg_color_r" type="number" min="0" max="255" required class="mt-1 block w-full font-mono text-sm" />
                        @error('passport_bg_color_r') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-input-label :value="__('Green Channel (0–255)')" />
                        <x-text-input wire:model.blur="passport_bg_color_g" id="passport_bg_color_g" type="number" min="0" max="255" required class="mt-1 block w-full font-mono text-sm" />
                        @error('passport_bg_color_g') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-input-label :value="__('Blue Channel (0–255)')" />
                        <x-text-input wire:model.blur="passport_bg_color_b" id="passport_bg_color_b" type="number" min="0" max="255" required class="mt-1 block w-full font-mono text-sm" />
                        @error('passport_bg_color_b') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Panel 2: Color Tolerance & Thresholds -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-sliders text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Tolerance & Matching Thresholds') }}</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Color Distance Tolerance (0–441)')" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Higher values accept more shadow or lighting deviations.') }}</p>
                        <x-text-input wire:model="passport_tolerance" id="passport_tolerance" type="number" min="0" max="441" required class="block w-full font-mono text-sm" />
                        @error('passport_tolerance') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-input-label :value="__('Sample Match Threshold (%)')" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Minimum percentage of background pixels along edge samples.') }}</p>
                        <x-text-input wire:model="passport_match_percentage" id="passport_match_percentage" type="number" min="0" max="100" required class="block w-full font-mono text-sm" />
                        @error('passport_match_percentage') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Panel 3: Dimensions & Aspect Ratio -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-crop text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Dimensions & Aspect Ratio') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label :value="__('Minimum Width (Pixels)')" />
                            <x-text-input wire:model="passport_min_width" id="passport_min_width" type="number" min="1" required class="mt-1 block w-full font-mono text-sm" />
                            @error('passport_min_width') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label :value="__('Minimum Height (Pixels)')" />
                            <x-text-input wire:model="passport_min_height" id="passport_min_height" type="number" min="1" required class="mt-1 block w-full font-mono text-sm" />
                            @error('passport_min_height') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 dark:border-gray-750">
                        <x-input-label :value="__('Required Aspect Ratio Aspect')" />
                        <x-select-input wire:model.live="passport_aspect_ratio" id="passport_aspect_ratio" class="mt-1.5 block w-full">
                            <option value="7:9">7:9 (Standard Passport)</option>
                            <option value="3:4">3:4 Ratio</option>
                            <option value="1:1">1:1 Square Ratio</option>
                        </x-select-input>
                        @error('passport_aspect_ratio') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Toggle Switch for Skip Aspect Ratio -->
                    <div class="flex items-center justify-between mt-3 bg-gray-50 dark:bg-gray-900/40 p-3 rounded-lg border border-gray-150 dark:border-gray-700">
                        <div class="space-y-0.5">
                            <label for="passport_skip_ratio" class="text-sm font-semibold text-gray-750 dark:text-gray-300">{{ __('Skip Aspect Ratio Checks') }}</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Only enforce the minimum size boundaries without evaluating aspect ratios.') }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model="passport_skip_ratio" id="passport_skip_ratio" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:bg-gray-700"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Panel 4: Advanced Properties -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-gears text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Advanced Math Validation') }}</h2>
                </div>

                <div>
                    <x-input-label :value="__('Edge Sample Divisor (10–500)')" />
                    <x-text-input wire:model="passport_edge_sample_divisor" id="passport_edge_sample_divisor" type="number" min="10" max="500" required class="mt-1 block w-full font-mono text-sm" />
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Determines granularity of perimeter analysis. Higher values sample fewer edge pixels to improve upload performance.') }}</p>
                    @error('passport_edge_sample_divisor') <p class="mt-1 text-xs text-red-650 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <x-college-form-submit target="saveSettings" class="rounded-lg px-6 py-2.5">
                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>{{ __('Save Settings') }}
                </x-college-form-submit>
            </div>
        </form>
    </div>

    <!-- Demo Sandbox Tab Content -->
    <div x-show="tab === 'demo'" x-transition class="flex flex-col gap-6 lg:flex-row">
        <!-- Left Side: Color Preview & Presets -->
        <div class="w-full shrink-0 lg:w-1/3 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 flex flex-col items-center">
                <div class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-3 w-full justify-center dark:border-gray-700">
                    <i class="fa-solid fa-eye text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Real-time Preview') }}</h2>
                </div>

                <div
                    class="mb-5 flex items-center justify-center rounded-xl border border-gray-300 shadow-inner transition-all duration-300 dark:border-gray-650"
                    :style="aspectStyle"
                >
                    <span class="text-xs font-bold uppercase tracking-wider text-white mix-blend-difference bg-black/35 px-2.5 py-1 rounded-full">{{ __('Background') }}</span>
                </div>
                
                <p class="mb-5 text-center text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Adjust the RGB sliders or click a preset below to change the expected background backdrop.') }}
                </p>

                <!-- Color Presets -->
                <div class="grid grid-cols-2 gap-2 w-full">
                    <button type="button" @click="setPresetAndInputs(255,0,0)" class="inline-flex items-center justify-center gap-1 rounded-lg border border-red-200 bg-red-50 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100/60 dark:border-red-950 dark:bg-red-950/40 dark:text-red-400 transition">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>{{ __('Red') }}
                    </button>
                    <button type="button" @click="setPresetAndInputs(0,0,255)" class="inline-flex items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100/60 dark:border-blue-950 dark:bg-blue-950/40 dark:text-blue-400 transition">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>{{ __('Blue') }}
                    </button>
                    <button type="button" @click="setPresetAndInputs(0,128,0)" class="inline-flex items-center justify-center gap-1 rounded-lg border border-green-200 bg-green-50 py-1.5 text-xs font-bold text-green-705 hover:bg-green-100/60 dark:border-green-950 dark:bg-green-950/40 dark:text-green-400 transition">
                        <span class="h-2.5 w-2.5 rounded-full bg-green-600"></span>{{ __('Green') }}
                    </button>
                    <button type="button" @click="setPresetAndInputs(255,255,255)" class="inline-flex items-center justify-center gap-1 rounded-lg border border-gray-250 bg-gray-50 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-105 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300 transition">
                        <span class="h-2.5 w-2.5 rounded-full bg-white border border-gray-300"></span>{{ __('White') }}
                    </button>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-4 w-full text-center text-xs text-gray-400 dark:border-gray-750">
                    {{ __('Expected RGB Hex Color') }}:
                    <span class="font-mono text-sm font-bold text-gray-800 dark:text-gray-100" x-text="'#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase()"></span>
                </div>
            </div>
        </div>

        <!-- Right Side: Sandbox Test Upload -->
        <div class="flex-1 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <i class="fa-solid fa-vial-circle-check text-purple-600 dark:text-purple-400"></i>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Sandbox Test Upload') }}</h2>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('Upload an image to dry run passport analysis checks against the currently configured settings.') }}</p>
                
                <div class="grid gap-6 md:grid-cols-2 items-start">
                    <div class="space-y-4">
                        <x-filepond
                            field="testPhotoPond"
                            purpose="passport_photo"
                            :label="__('Test photo')"
                            accept="image/jpeg,image/png,image/webp,image/avif"
                        />
                        <button 
                            type="button" 
                            wire:click="runTestUpload" 
                            class="inline-flex items-center justify-center rounded-lg bg-purple-650 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-750 focus:outline-none transition"
                        >
                            <i class="fa-solid fa-bolt mr-2"></i>{{ __('Validate Image') }}
                        </button>
                    </div>
                    
                    <div class="rounded-lg bg-gray-50 p-4 border border-gray-200 dark:bg-gray-900/40 dark:border-gray-700 min-h-[120px]">
                        <span class="font-bold text-gray-500 uppercase tracking-wider text-xs block mb-2">{{ __('Validation Reports') }}</span>
                        @if ($testMessages !== [])
                            <ul class="list-disc space-y-1.5 pl-5 text-sm text-gray-700 dark:text-gray-300">
                                @foreach ($testMessages as $line)
                                    <li class="font-semibold" wire:key="test-msg-{{ $loop->index }}">{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-gray-400 italic">{{ __('No test validation run yet.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
