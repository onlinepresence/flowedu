@php
    $bg = $requirements['bg_color'];
    $bgLabel = sprintf('RGB(%d, %d, %d)', $bg['r'], $bg['g'], $bg['b']);
@endphp

<div class="space-y-6">
    <div class="rounded-xl border border-purple-100 bg-purple-50/70 p-5 dark:border-purple-900/50 dark:bg-purple-950/20">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-600 text-white">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-sm font-bold uppercase tracking-wider text-purple-800 dark:text-purple-300">{{ __('Current passport photo requirements') }}</h2>
                @if ($requirements['enabled'])
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Minimum resolution: :width x :height px.', ['width' => $requirements['min_width'], 'height' => $requirements['min_height']]) }}
                        @if ($requirements['skip_ratio'])
                            {{ __('Aspect ratio checking is currently disabled.') }}
                        @else
                            {{ __('Aspect ratio must be close to :ratio (tolerance +/-:tolerance).', ['ratio' => $requirements['aspect_ratio'], 'tolerance' => number_format($requirements['aspect_tolerance'], 3)]) }}
                        @endif
                    </p>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <span>{{ __('Background must match :color with :tolerance colour tolerance and at least :match% edge match.', ['color' => $bgLabel, 'tolerance' => $requirements['bg_tolerance'], 'match' => $requirements['edge_match_percent']]) }}</span>
                        <span class="inline-block h-4 w-4 rounded border border-gray-400" style="background-color: {{ $bgLabel }};"></span>
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">
                        {{ __('Image validation is currently disabled by the school. The validator will still read your image, but no passport checks will be enforced when you submit it.') }}
                    </p>
                @endif
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    {{ __('Test your photo here first. Upload the same file when you return to the registration form.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
            <i class="fa-solid fa-address-card text-lg text-purple-600 dark:text-purple-400" aria-hidden="true"></i>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Test your passport photo') }}</h2>
        </div>

        <p class="mb-5 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Choose a JPG, PNG, WebP, or AVIF image up to 5MB. We will check the same settings used when your registration photo is submitted and show exactly what passed or failed.') }}
        </p>

        <div class="space-y-4">
            <div>
                <label for="passport-validator-photo" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Passport photo') }}</label>
                <input
                    id="passport-validator-photo"
                    type="file"
                    wire:model="photo"
                    accept="image/jpeg,image/png,image/webp,image/avif"
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-purple-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-purple-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300"
                />
                @error('photo')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" wire:loading wire:target="photo">{{ __('Uploading image...') }}</p>
            </div>

            @if ($photo)
                @php
                    $previewable = in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true);
                @endphp
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    @if ($previewable)
                        <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Selected passport photo preview') }}" class="h-16 w-16 rounded-md border border-gray-200 object-cover dark:border-gray-700">
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $photo->getClientOriginalName() }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Ready to analyze') }}</p>
                    </div>
                </div>
            @endif

            <div class="flex justify-start">
                <button
                    type="button"
                    wire:click="analyze"
                    wire:loading.attr="disabled"
                    wire:target="photo,analyze"
                    @disabled(! $photo)
                    class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
                >
                    <i class="fa-solid fa-circle-check mr-2" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="analyze">{{ __('Check my photo') }}</span>
                    <span wire:loading wire:target="analyze">{{ __('Checking...') }}</span>
                </button>
            </div>
        </div>
    </div>

    @if ($result !== null)
        @php
            $bytes = $result['metadata']['size_bytes'];
            if ($bytes === null) {
                $displaySize = __('Unknown');
            } elseif ($bytes < 1024) {
                $displaySize = $bytes.' B';
            } elseif ($bytes < 1048576) {
                $displaySize = number_format($bytes / 1024, 1).' KB';
            } else {
                $displaySize = number_format($bytes / 1048576, 1).' MB';
            }
            $stateClasses = [
                'pass' => 'text-green-600 dark:text-green-400',
                'fail' => 'text-red-600 dark:text-red-400',
                'skip' => 'text-gray-400 dark:text-gray-500',
            ];
            $stateIcons = [
                'pass' => 'fa-circle-check',
                'fail' => 'fa-circle-xmark',
                'skip' => 'fa-circle-minus',
            ];
        @endphp

        <div class="overflow-hidden rounded-xl border shadow-sm {{ $result['accepted'] ? 'border-green-200 dark:border-green-900/60' : 'border-red-200 dark:border-red-900/60' }}">
            <div class="flex items-start gap-3 p-5 {{ $result['accepted'] ? 'bg-green-50 dark:bg-green-950/30' : 'bg-red-50 dark:bg-red-950/30' }}">
                <i class="fa-solid {{ $result['accepted'] ? 'fa-circle-check text-green-600 dark:text-green-400' : 'fa-circle-xmark text-red-600 dark:text-red-400' }} mt-0.5 text-2xl" aria-hidden="true"></i>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold {{ $result['accepted'] ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                        {{ $result['accepted'] ? __('Photo accepted') : __('Photo not accepted') }}
                    </h2>
                    <p class="mt-1 text-sm {{ $result['accepted'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ $result['accepted'] ? __('This image meets the current passport photo requirements.') : __('Fix the failed checks below, then test the image again before uploading it.') }}
                    </p>
                    @if (! $result['enabled'])
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ $result['summary'] }}</p>
                    @endif
                </div>
            </div>

            <div class="border-b border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('File') }}</dt>
                        <dd class="mt-1 break-all font-medium text-gray-900 dark:text-white">{{ $result['metadata']['filename'] ?? __('Uploaded image') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Dimensions') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $result['metadata']['width'] !== null ? $result['metadata']['width'].' x '.$result['metadata']['height'].' px' : __('Unknown') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('File size') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $displaySize }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Format') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $result['metadata']['mime'] ?? __('Unknown') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white p-5 dark:bg-gray-800">
                <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Check results') }}</h3>
                <ul class="space-y-4">
                    @foreach ($result['checks'] as $check)
                        <li class="flex items-start gap-3" wire:key="passport-check-{{ $check['key'] }}">
                            <i class="fa-solid {{ $stateIcons[$check['state']] ?? 'fa-circle-info' }} {{ $stateClasses[$check['state']] ?? 'text-gray-400' }} mt-0.5 text-lg" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $check['label'] }}</p>
                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $check['detail'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
