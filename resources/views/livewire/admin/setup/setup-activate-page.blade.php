<div class="mx-auto max-w-2xl space-y-6 border border-gray-200 py-8 dark:border-gray-700">
    @if ($prerequisitesMet)
        <p class="px-4 text-center text-gray-700 dark:text-gray-200">
            {{ $schoolReady
                ? __('By clicking the button below, you agree that the system should be deactivated.')
                : __('By clicking the button below, you agree that the system should be activated.') }}
        </p>
        <div class="mx-auto grid max-w-xs grid-cols-1 items-center justify-items-center">
            @if ($schoolReady)
                <button type="button" wire:click="setReady(false)" wire:loading.attr="disabled" wire:target="setReady(false)" class="inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="setReady(false)">{{ __('Deactivate') }}</span>
                    <span wire:loading.delay.200ms wire:target="setReady(false)" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                        {{ __('Please wait…') }}
                    </span>
                </button>
            @else
                <button type="button" wire:click="setReady(true)" wire:loading.attr="disabled" wire:target="setReady(true)" class="inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="setReady(true)">{{ __('Activate') }}</span>
                    <span wire:loading.delay.200ms wire:target="setReady(true)" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                        {{ __('Please wait…') }}
                    </span>
                </button>
            @endif
        </div>
        @if ($schoolReady)
            <div class="flex justify-center">
                <a
                    href="{{ route('admin.dashboard') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                >
                    {{ __('Go to dashboard') }}
                </a>
            </div>
        @endif
    @else
        <ul class="space-y-2 text-sm">
            <li class="ml-4 {{ $checklist['school'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $checklist['school'] ? '✓' : '!' }} {{ __('Provided school details') }}
            </li>
            <li class="ml-4 {{ $checklist['departments'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $checklist['departments'] ? '✓' : '!' }} {{ __('Provided at least one department') }}
            </li>
            <li class="ml-4 {{ $checklist['programs'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $checklist['programs'] ? '✓' : '!' }} {{ __('Provided at least one program') }}
            </li>
            <li class="ml-4 {{ $checklist['halls'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $checklist['halls'] ? '✓' : '!' }} {{ __('Provided at least one hall') }}
            </li>
        </ul>
    @endif
</div>
