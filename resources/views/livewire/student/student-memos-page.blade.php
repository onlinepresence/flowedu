<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Memos & Announcements') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Official communications dispatched to you.') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-200" role="status">
            {{ session('status') }}
        </div>
    @endif

    <!-- Tabs & Search -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            @foreach(['all' => 'All Memos', 'unread' => 'Pending Action', 'acknowledged' => 'Acknowledged'] as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="border-b-2 py-3 px-1 text-sm font-medium transition duration-150 whitespace-nowrap {{ $activeTab === $tab ? 'border-purple-500 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    {{ __($label) }}
                </button>
            @endforeach
        </nav>

        <div class="relative w-full md:max-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="fa-solid fa-search text-gray-400 text-xs"></i>
            </div>
            <x-text-input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search memos...') }}"
                class="block w-full pl-9 text-sm"
            />
        </div>
    </div>

    <!-- Memos Grid -->
    <div class="grid grid-cols-1 gap-4">
        @forelse ($memos as $memo)
            @php
                $receipt = $memo->readReceipts()->where('user_id', auth()->id())->first();
                $isRead = $receipt && !is_null($receipt->viewed_at);
                $isAcknowledged = $receipt && !is_null($receipt->acknowledged_at);
            @endphp
            <div class="bg-white shadow-sm rounded-lg border {{ !$isRead ? 'border-l-4 border-l-purple-600 dark:border-l-purple-500 border-gray-200 dark:border-gray-700' : 'border-gray-200 dark:border-gray-700' }} dark:bg-gray-800 p-6 transition hover:shadow-md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Confidentiality Badge -->
                            @if ($memo->confidentiality_level === 'public')
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-950/40 dark:text-green-300">
                                    {{ __('Public') }}
                                </span>
                            @elseif ($memo->confidentiality_level === 'internal')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                    {{ __('Internal') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                    {{ __('Confidential') }}
                                </span>
                            @endif

                            @if (!$isRead)
                                <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-750 dark:bg-purple-950/40 dark:text-purple-300">
                                    {{ __('New') }}
                                </span>
                            @endif

                            @if ($isAcknowledged)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-750 dark:bg-green-950/40 dark:text-green-300">
                                    <i class="fa-solid fa-check mr-1 text-[10px]"></i>
                                    {{ __('Acknowledged') }}
                                </span>
                            @endif
                        </div>

                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            <a href="{{ route('student.memos.show', $memo->id) }}" wire:navigate class="hover:text-purple-750 dark:hover:text-purple-400 transition">
                                {{ $memo->title }}
                            </a>
                        </h2>

                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 pr-6">
                            {{ Str::limit(strip_tags($memo->content), 180) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Sender') }}:</span>
                                {{ $memo->sender_name }}
                            </span>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('To') }}:</span>
                                {{ $memo->recipient_name }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ $memo->updated_at->format('M d, Y H:i') }}
                        </span>
                        <a
                            href="{{ route('student.memos.show', $memo->id) }}"
                            wire:navigate
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('View Memo') }}
                            <i class="fa-solid fa-chevron-right ml-2 text-[0.65rem] opacity-70"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <x-college.empty-state
                title="{{ __('No memos found') }}"
                description="{{ __('Try searching or matching other filter configurations.') }}"
            >
                <x-slot name="icon">
                    <i class="fa-solid fa-envelope-open text-3xl text-purple-500"></i>
                </x-slot>
            </x-college.empty-state>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $memos->links() }}
    </div>
</div>
