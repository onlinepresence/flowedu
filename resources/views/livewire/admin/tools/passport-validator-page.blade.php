<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
            <i class="fa-solid fa-address-card text-purple-600 dark:text-purple-400 text-lg"></i>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Verify Photo Dimensions') }}</h2>
        </div>
        
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
            {{ __('Upload any image below. The tool will parse the photo metadata to retrieve dimensions and check if it satisfies printable size bounds.') }}
        </p>

        <div class="space-y-5">
            <x-filepond
                field="passportPond"
                purpose="passport_photo"
                :label="__('Passport photo')"
                accept="image/jpeg,image/png,image/webp,image/avif"
            />
            
            <div class="flex justify-start">
                <button 
                    type="button" 
                    wire:click="validatePassport" 
                    class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none transition"
                >
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ __('Analyze Dimensions') }}
                </button>
            </div>
        </div>

        @if ($messages !== [])
            <div class="mt-6 rounded-lg bg-gray-50 p-4 border border-gray-200 dark:bg-gray-900/40 dark:border-gray-700">
                <span class="font-bold text-gray-500 uppercase tracking-wider text-xs block mb-3">{{ __('Image Analysis Result') }}</span>
                <ul class="list-disc space-y-1.5 pl-5 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($messages as $line)
                        <li class="font-semibold" wire:key="pv-{{ $loop->index }}">{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
