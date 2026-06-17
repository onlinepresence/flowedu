@php
    $v = $variant ?? 'toolbar';
    if (! in_array($v, ['sidebar', 'menu', 'toolbar'], true)) {
        $v = 'toolbar';
    }
@endphp
<button
    type="button"
    wire:click="logout"
    @class([
        'flex w-full items-center justify-between rounded-lg border border-transparent bg-purple-600 px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40 active:bg-purple-600 dark:focus:ring-offset-0' => $v === 'sidebar',
        'inline-flex w-full items-center rounded-md px-2 py-1 text-left text-sm font-semibold text-gray-600 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500/40 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-200' => $v === 'menu',
        'inline-flex items-center rounded-lg bg-college-accent px-4 py-2 text-sm font-medium text-college-on-accent shadow-sm transition hover:bg-college-accent-hover focus:outline-none focus:ring-2 focus:ring-purple-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800' => $v === 'toolbar',
    ])
>
    @if ($v === 'menu')
        <i class="fa-solid fa-door-open mr-3 w-4 text-center" aria-hidden="true"></i>
        <span>{{ __('Log out') }}</span>
    @else
        {{ __('Log out') }}
    @endif
</button>
