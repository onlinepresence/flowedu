<div
    x-data="{
        isOpen: false,
        query: '',
        selectedIndex: -1,
        allPages: [],

        init() {
            const src = this.$refs.pagesData;
            if (src) {
                try { this.allPages = JSON.parse(src.textContent); } catch(e) {}
            }
        },

        get results() {
            if (this.query.trim() === '') return this.allPages;
            const q = this.query.toLowerCase();
            return this.allPages.filter(p => p.label.toLowerCase().includes(q));
        },

        openSearch() {
            this.isOpen = true;
            this.selectedIndex = -1;
            this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
        },
        closeSearch() {
            this.isOpen = false;
            this.query = '';
            this.selectedIndex = -1;
        },
        moveDown() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            this.$nextTick(() => this.scrollToSelected());
        },
        moveUp() {
            if (this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            this.$nextTick(() => this.scrollToSelected());
        },
        selectItem() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].url;
            }
        },
        scrollToSelected() {
            const el = this.$refs.resultsContainer && this.$refs.resultsContainer.querySelector('[data-selected=\'true\']');
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    }"
    @keydown.window.ctrl.k.prevent="openSearch()"
    @keydown.window.meta.k.prevent="openSearch()"
    class="flex flex-1 justify-center lg:mr-32"
>
    {{-- Pages data — read once by Alpine init, never touched by Livewire --}}
    <script type="application/json" x-ref="pagesData" style="display:none;">@json($allPages)</script>

    {{-- Trigger Button --}}
    <div class="relative mr-0 w-full max-w-xl lg:mr-6 flex items-center justify-end sm:justify-start">
        <!-- Wide search bar on larger screens -->
        <button
            type="button"
            @click="openSearch()"
            class="hidden sm:flex w-full items-center justify-between rounded-md border-0 bg-gray-100 py-2.5 pl-8 pr-2 text-sm text-gray-500 shadow-none ring-0 hover:bg-gray-200/60 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600/70 text-left focus:outline-none transition-colors"
        >
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400 dark:text-gray-500">
                <i class="fa-solid fa-magnifying-glass text-sm opacity-80" aria-hidden="true"></i>
            </div>
            <span>{{ __('Search... (Ctrl+K)') }}</span>
            <kbd class="rounded bg-gray-200/80 px-1.5 py-0.5 text-xs font-semibold text-gray-500 dark:bg-gray-600 dark:text-gray-400">Ctrl K</kbd>
        </button>

        <!-- Compact circular search button on mobile screens -->
        <button
            type="button"
            @click="openSearch()"
            class="flex sm:hidden items-center justify-center h-9 w-9 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200/60 dark:hover:bg-gray-650 transition-colors focus:outline-none"
            title="{{ __('Search') }}"
        >
            <i class="fa-solid fa-magnifying-glass text-sm opacity-80" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Command Palette Overlay --}}
    <div
        x-show="isOpen"
        class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4"
        style="display: none;"
        @keydown.escape.window="closeSearch()"
    >
        {{-- Backdrop --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 backdrop-blur-sm"
            @click="closeSearch()"
        ></div>

        {{-- Palette Box --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/10"
            @keydown.arrow-down.prevent="moveDown()"
            @keydown.arrow-up.prevent="moveUp()"
            @keydown.enter.prevent="selectItem()"
        >
            {{-- Search Input --}}
            <div class="relative flex items-center">
                <i class="fa-solid fa-magnifying-glass absolute left-4 text-gray-400 dark:text-gray-500 text-sm" aria-hidden="true"></i>
                <input
                    x-ref="searchInput"
                    x-model="query"
                    type="text"
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm dark:text-white dark:placeholder:text-gray-500"
                    placeholder="{{ __('Search pages...') }}"
                    autocomplete="off"
                />
            </div>

            {{-- Results --}}
            <div
                x-ref="resultsContainer"
                class="border-t border-gray-100 dark:border-gray-700"
            >
                <ul
                    x-show="results.length > 0"
                    class="max-h-80 scroll-py-1 overflow-y-auto py-1"
                >
                    <template x-for="(page, index) in results" :key="page.url">
                        <li>
                            <a
                                :href="page.url"
                                :data-selected="selectedIndex === index ? 'true' : 'false'"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer select-none transition-colors"
                                :class="selectedIndex === index
                                    ? 'bg-purple-600 text-white'
                                    : 'text-gray-800 dark:text-gray-200 hover:bg-purple-50 dark:hover:bg-purple-900/20'"
                                @mouseenter="selectedIndex = index"
                                @mouseleave="selectedIndex = -1"
                            >
                                <i
                                    class="fa-solid text-sm w-4 text-center flex-shrink-0"
                                    :class="[
                                        'fa-' + (page.icon || 'file'),
                                        selectedIndex === index ? 'text-white/80' : 'text-purple-500 dark:text-purple-400'
                                    ]"
                                    aria-hidden="true"
                                ></i>
                                <span class="flex-1 font-medium text-sm" x-text="page.label"></span>
                                <i
                                    x-show="selectedIndex === index"
                                    class="fa-solid fa-arrow-right text-xs text-white/60"
                                    aria-hidden="true"
                                ></i>
                            </a>
                        </li>
                    </template>
                </ul>

                {{-- No results for current query --}}
                <div
                    x-show="results.length === 0 && query.trim() !== ''"
                    class="py-12 px-6 text-center"
                >
                    <i class="fa-solid fa-magnifying-glass mx-auto text-2xl text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true"></i>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('No pages found') }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`No pages matching &quot;${query}&quot; in your navigation.`"></p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-4 py-2">
                <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <kbd class="rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 font-semibold shadow-sm">↑</kbd>
                    <kbd class="rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 font-semibold shadow-sm">↓</kbd>
                    <span class="ml-0.5">{{ __('navigate') }}</span>
                    <span class="mx-1.5">·</span>
                    <kbd class="rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 font-semibold shadow-sm">↵</kbd>
                    <span class="ml-0.5">{{ __('select') }}</span>
                    <span class="mx-1.5">·</span>
                    <kbd class="rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 font-semibold shadow-sm">Esc</kbd>
                    <span class="ml-0.5">{{ __('close') }}</span>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500" x-text="`${results.length} page${results.length !== 1 ? 's' : ''}`"></span>
            </div>
        </div>
    </div>
</div>
