{{--
    Reusable Demo Reset button with inline Alpine confirmation dialog.

    Usage:  <x-college.demo-reset-button />

    Shows a styled button that opens a self-contained confirm dialog before
    POSTing to `route('demo.reset')`.  Only visible in demo mode.
--}}

@if(config('college.demo_mode') || session('demo_mode'))
    <div
        x-data="{ open: false, loading: false }"
        class="{{ $attributes->get('class', '') }}"
    >
        {{-- Trigger --}}
        <button
            type="button"
            @click="open = true"
            class="inline-flex items-center gap-2 rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400/50"
        >
            <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i>
            {{ __('Reset Sandbox Data') }}
        </button>

        {{-- Confirmation Dialog --}}
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4"
        >
            {{-- Backdrop --}}
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500/70 dark:bg-gray-900/80 backdrop-blur-sm"
                @click="open = false"
            ></div>

            {{-- Dialog --}}
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md rounded-xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/10"
                @keydown.escape.window="if (!loading) open = false"
            >
                {{-- Icon header --}}
                <div class="flex items-start gap-4 p-6">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/40">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 dark:text-red-400" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ __('Reset sandbox data?') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('This will permanently delete all current demo data — including anything added during this session — and regenerate fresh records on the next page load. This cannot be undone.') }}
                        </p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <button
                        type="button"
                        @click="open = false"
                        :disabled="loading"
                        :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-gray-600'"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 transition"
                    >
                        {{ __('Cancel') }}
                    </button>

                    <form
                        action="{{ route('demo.reset') }}"
                        method="POST"
                        @submit.prevent="loading = true; $el.submit()"
                    >
                        @csrf
                        <button
                            type="submit"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition disabled:opacity-75 disabled:cursor-not-allowed"
                        >
                            {{-- Spinner (visible while loading) --}}
                            <svg x-show="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{-- Icon (visible when idle) --}}
                            <i x-show="!loading" class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i>
                            <span x-text="loading ? '{{ __('Resetting...') }}' : '{{ __('Yes, reset data') }}'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
