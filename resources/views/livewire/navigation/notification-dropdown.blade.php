<li class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button
        type="button"
        class="relative rounded-md align-middle focus:outline-none focus:ring-2 focus:ring-purple-500/40 text-purple-600 dark:text-purple-300"
        @click="open = !open"
        aria-label="{{ __('Notifications') }}"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <i class="fa-solid fa-bell h-5 w-5" aria-hidden="true"></i>
        @if ($this->unreadCount > 0)
            <span
                class="absolute right-0 top-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[0.65rem] font-bold leading-none text-white bg-red-600 rounded-full translate-x-1 -translate-y-1 border-2 border-white dark:border-gray-800"
                aria-hidden="true"
            >
                {{ $this->unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 rounded-md border border-gray-150 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 focus:outline-none z-50 overflow-hidden"
        style="display: none;"
        role="menu"
    >
        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Notifications') }}
            </span>
            @if ($this->unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs text-purple-600 hover:text-purple-500 dark:text-purple-400 dark:hover:text-purple-300 font-medium"
                >
                    {{ __('Mark all read') }}
                </button>
            @endif
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($this->notifications as $notification)
                <a
                    href="#"
                    wire:click.prevent="markAsRead('{{ $notification->id }}')"
                    class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 {{ $notification->read_at ? 'opacity-60' : 'bg-purple-50/10 dark:bg-purple-950/5' }}"
                >
                    <div class="flex items-start">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
                                {{ data_get($notification->data, 'title', 'New Notification') }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">
                                {{ data_get($notification->data, 'message') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        @if (! $notification->read_at)
                            <span class="h-2 w-2 rounded-full bg-purple-500 shrink-0 ml-2 mt-1.5"></span>
                        @endif
                    </div>
                </a>
            @empty
                <x-college.empty-state
                    :title="__('All caught up!')"
                    :description="__('No new notifications received.')"
                    class="border-none bg-transparent p-6"
                >
                    <x-slot:icon>
                        <i class="fa-solid fa-circle-check text-3xl text-green-500 dark:text-green-400 block"></i>
                    </x-slot:icon>
                </x-college.empty-state>
            @endforelse
        </div>
    </div>
</li>
