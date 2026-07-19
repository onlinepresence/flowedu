<div>
    @if ($showSwitcher)
        <div class="flex items-center">
            @if ($activeRole === 'admin')
                <button
                    type="button"
                    wire:click="switchPortal('teacher')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-700/30 dark:hover:bg-indigo-900/40 transition"
                    title="{{ __('Switch to Lecturer Portal') }}"
                >
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span class="hidden md:inline">{{ __('Lecturer View') }}</span>
                </button>
            @else
                <button
                    type="button"
                    wire:click="switchPortal('admin')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-3.5 py-1.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 dark:ring-purple-700/30 dark:hover:bg-purple-900/40 transition"
                    title="{{ __('Switch to Admin Portal') }}"
                >
                    <i class="fa-solid fa-user-shield"></i>
                    <span class="hidden md:inline">{{ __('Admin View') }}</span>
                </button>
            @endif
        </div>
    @endif
</div>
