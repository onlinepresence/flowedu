<div class="mx-auto max-w-7xl">

    @if (!$isLicensed)
        <!-- Premium Upsell Marketing Card -->
        <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-purple-100 bg-white shadow-xl dark:border-purple-950/40 dark:bg-gray-800">
            <div class="relative bg-gradient-to-r from-purple-600 to-indigo-700 px-6 py-12 text-center text-white dark:from-purple-950 dark:to-indigo-950">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent)]"></div>
                <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/10 backdrop-blur-md mb-4">
                    <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1.75 0zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1.75 0zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                </div>
                <h2 class="relative text-3xl font-extrabold tracking-tight">{{ __('Unlock CoE Secure Messenger') }}</h2>
                <p class="relative mt-2 text-sm text-purple-100 max-w-xl mx-auto">
                    {{ __('Bridge the gap between staff, students, and administration. A dedicated, encrypted system-wide chat framework tailored for college structures.') }}
                </p>
            </div>

            <div class="p-8 space-y-6">
                <!-- Features Breakdown Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-650 dark:bg-purple-950/40 dark:text-purple-400">
                            <i class="fa-solid fa-users text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('System-Wide Directory') }}</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-normal">{{ __('Search and connect with students or lecturers instantly.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-650 dark:bg-purple-950/40 dark:text-purple-400">
                            <i class="fa-solid fa-bell text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Real-Time Alerts') }}</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-normal">{{ __('Email alerts and visual markers keep you updated offline.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-650 dark:bg-purple-950/40 dark:text-purple-400">
                            <i class="fa-solid fa-shield-halved text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Audit & Logs') }}</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-normal">{{ __('Maintains messaging logs for grade reviews and welfares.') }}</p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100 dark:border-gray-700" />

                <!-- Pricing band and Call to Action -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-gray-50 dark:bg-gray-900/40 rounded-xl p-6 border border-gray-100 dark:border-gray-750">
                    <div class="text-center md:text-left space-y-1">
                        <div class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                            {{ __('Premium Add-On Module') }}
                        </div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $pricingDetails['band_label'] ?? 'GHS 1,500.00 / year' }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Setup fee: GHS') }} {{ number_format($pricingDetails['setup_fee'] ?? 500.00, 2) }} · {{ __('License: GHS') }} {{ number_format($pricingDetails['annual_fee'] ?? 1500.00, 2) }}/{{ __('yr') }}
                        </p>
                    </div>
                    <div class="shrink-0 w-full md:w-auto">
                        <a href="mailto:admin@college.edu?subject=Upgrade%20Secure%20Messaging%20Module" class="inline-flex w-full md:w-auto items-center justify-center rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-755 transition-colors">
                            <i class="fa-solid fa-cart-shopping mr-2 text-xs"></i>
                            {{ __('Contact Registrar to Upgrade') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Fully Implemented Messaging Dashboard Console -->
        <div class="flex rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-755 dark:bg-gray-800 h-[600px] overflow-hidden">
            
            <!-- Left Side: Conversations & Contacts List -->
            <div class="w-full md:w-1/3 flex flex-col border-r border-gray-200 dark:border-gray-750 {{ $mobileShowChat ? 'hidden md:flex' : 'flex' }}">
                <div class="p-4 border-b border-gray-200 dark:border-gray-750 space-y-3 shrink-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Conversations') }}</h3>
                        <button type="button" x-on:click="$dispatch('open-modal', 'new-chat-search-modal')" class="inline-flex items-center justify-center rounded-lg bg-purple-50 p-2 text-purple-600 hover:bg-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:hover:bg-purple-900/50 transition-colors" title="{{ __('Start New Chat') }}">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Chat Threads List -->
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-750">
                    @forelse ($conversations as $convo)
                        @php
                            $recipient = $convo->getOtherParticipant(auth()->id());
                            $hasUnread = $convo->hasUnreadMessages(auth()->id());
                        @endphp
                        @if ($recipient)
                            <button type="button" wire:click="selectConversation({{ $convo->id }})" class="w-full flex items-center gap-3 px-4 py-3.5 text-left hover:bg-gray-50 dark:hover:bg-gray-750 transition {{ $activeConversationId === $convo->id ? 'bg-purple-50/40 dark:bg-purple-950/20' : '' }}">
                                <x-college.avatar :name="$recipient->name" size="sm" class="shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $recipient->name }}
                                        </p>
                                        <span class="text-[10px] font-mono text-gray-500 dark:text-gray-400">
                                            {{ $convo->last_message_at ? $convo->last_message_at->format('H:i') : '' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5 {{ $hasUnread ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                                        {{ $convo->last_message_text ?? __('No messages yet') }}
                                    </p>
                                </div>
                                @if ($hasUnread)
                                    <span class="h-2.5 w-2.5 rounded-full bg-purple-600 shrink-0"></span>
                                @endif
                            </button>
                        @endif
                    @empty
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-comments text-3xl text-gray-300 dark:text-gray-600 block mb-2"></i>
                            <p class="text-sm font-medium">{{ __('No chats active') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ __('Click the plus button to search staff or students and start typing.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Side: Chatbox Workspace -->
            <div class="w-full md:w-2/3 flex flex-col bg-gray-50 dark:bg-gray-900/10 {{ $mobileShowChat ? 'flex' : 'hidden md:flex' }}">
                @if ($activeConversationId && $activeRecipient)
                    <!-- Active Chat Header -->
                    <div class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-750 px-6 py-4 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="closeChat" class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white mr-1">
                                <i class="fa-solid fa-arrow-left text-sm"></i>
                            </button>
                            <x-college.avatar :name="$activeRecipient->name" size="sm" class="shrink-0" />
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $activeRecipient->name }}</h4>
                                <span class="inline-flex items-center text-[10px] text-emerald-600 dark:text-emerald-450 font-semibold mt-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                    {{ __('Online support channel') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Messages Window (Infinite Scroll / Prepend Support) -->
                    <div 
                        id="messages-container" 
                        x-data="{
                            scrollContainer: null,
                            init() {
                                this.scrollContainer = $el;
                                this.scrollToBottom();
                                
                                Livewire.on('scroll-to-bottom', () => {
                                    this.scrollToBottom();
                                });
                            },
                            scrollToBottom() {
                                this.$nextTick(() => {
                                    this.scrollContainer.scrollTop = this.scrollContainer.scrollHeight;
                                });
                            },
                            loadMore() {
                                let oldHeight = this.scrollContainer.scrollHeight;
                                $wire.loadMoreMessages().then(() => {
                                    this.$nextTick(() => {
                                        this.scrollContainer.scrollTop = this.scrollContainer.scrollHeight - oldHeight;
                                    });
                                });
                            }
                        }"
                        class="flex-1 p-6 overflow-y-auto space-y-4 flex flex-col"
                    >
                        <!-- Scroll Sentinel Element (Prepend loader) -->
                        @if ($hasMoreMessages)
                            <div x-data="{
                                init() {
                                    const observer = new IntersectionObserver(entries => {
                                        if (entries[0].isIntersecting) {
                                            this.loadMore();
                                        }
                                    }, {
                                        root: this.scrollContainer,
                                        threshold: 0.1
                                    });
                                    observer.observe($el);
                                }
                            }" class="h-8 flex items-center justify-center py-2 shrink-0">
                                <div wire:loading wire:target="loadMoreMessages" class="text-xs text-gray-505 dark:text-gray-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-notch animate-spin text-purple-600"></i>
                                    <span>{{ __('Loading older messages...') }}</span>
                                </div>
                            </div>
                        @endif

                        @forelse ($messages as $msg)
                            @php
                                $isMe = (int) $msg->sender_id === (int) auth()->id();
                            @endphp
                            <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} max-w-[85%] {{ $isMe ? 'self-end' : 'self-start' }}">
                                <div class="rounded-2xl px-4 py-2.5 text-sm {{ $isMe ? 'bg-purple-600 text-white rounded-br-none' : 'bg-white border border-gray-200 text-gray-800 dark:bg-gray-800 dark:border-gray-750 dark:text-gray-200 rounded-bl-none' }} shadow-sm">
                                    @if ($msg->body)
                                        <p class="leading-relaxed whitespace-pre-wrap">{{ $msg->body }}</p>
                                    @endif

                                    @if ($msg->attachment_path)
                                        <div class="mt-2 flex items-center gap-2 rounded-lg bg-black/5 dark:bg-white/5 p-2.5 border border-black/10 dark:border-white/10 text-left">
                                            <i class="fa-solid fa-file-signature text-lg text-purple-600 dark:text-purple-400 shrink-0"></i>
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ Storage::url($msg->attachment_path) }}" target="_blank" class="text-xs font-semibold underline truncate block text-gray-900 dark:text-white hover:text-purple-650 dark:hover:text-purple-300">
                                                    {{ $msg->attachment_name }}
                                                </a>
                                                <span class="text-[9px] text-gray-500 dark:text-gray-400 block font-mono">
                                                    {{ number_format($msg->attachment_size / 1024, 1) }} KB
                                                </span>
                                            </div>
                                            <a href="{{ Storage::url($msg->attachment_path) }}" download class="text-gray-550 hover:text-gray-755 dark:text-gray-300 dark:hover:text-white shrink-0 ml-1" title="{{ __('Download') }}">
                                                <i class="fa-solid fa-download text-xs"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                <span class="text-[9px] text-gray-400 dark:text-gray-500 font-mono mt-1 px-1">
                                    {{ $msg->created_at->format('M d, H:i') }}
                                </span>
                            </div>
                        @empty
                            <div class="m-auto text-center text-gray-400 dark:text-gray-500">
                                <p class="text-xs">{{ __('No history. Say hello to') }} {{ $activeRecipient->name }}!</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Send Message Text Box Form -->
                    <div class="bg-white border-t border-gray-200 dark:bg-gray-800 dark:border-gray-750 p-4 shrink-0">
                        <form wire:submit.prevent="sendMessage" class="flex flex-col gap-2">
                            <!-- Display temporary attachment preview with clear button -->
                            @if ($attachment)
                                <div class="flex items-center justify-between rounded-lg bg-purple-50 px-3 py-1.5 text-xs text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                    <span class="truncate font-semibold flex items-center">
                                        <i class="fa-solid fa-paperclip mr-1.5"></i>
                                        {{ $attachment->getClientOriginalName() }}
                                    </span>
                                    <button type="button" wire:click="$set('attachment', null)" class="text-purple-500 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 ml-2">
                                        <i class="fa-solid fa-xmark text-sm"></i>
                                    </button>
                                </div>
                            @endif

                            <!-- Message Sending Progress Feedback Indicator -->
                            <div wire:loading wire:target="sendMessage" class="text-xs text-purple-650 dark:text-purple-400 font-semibold mb-1 flex items-center gap-1.5 shrink-0">
                                <i class="fa-solid fa-paper-plane animate-pulse"></i>
                                <span>{{ __('Sending message...') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- File upload label button -->
                                <label class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-750 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white cursor-pointer shrink-0 transition" title="{{ __('Attach File') }}">
                                    <i class="fa-solid fa-paperclip text-sm"></i>
                                    <input type="file" wire:model="attachment" class="hidden" />
                                </label>

                                <x-text-input wire:model="messageBody" wire:loading.attr="disabled" wire:target="sendMessage" type="text" placeholder="{{ __('Type your message here…') }}" class="flex-1 py-2 text-sm shadow-none disabled:opacity-50 disabled:bg-gray-50" autocomplete="off" />
                                
                                <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-purple-600 text-white hover:bg-purple-700 shadow-sm shrink-0 transition-colors disabled:opacity-50">
                                    <!-- Normal state -->
                                    <svg wire:loading.remove wire:target="sendMessage" class="h-5 w-5 rotate-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                    <!-- Loading state -->
                                    <i wire:loading wire:target="sendMessage" class="fa-solid fa-circle-notch animate-spin text-sm"></i>
                                </button>
                            </div>
                            
                            <!-- File uploading indicator -->
                            <div wire:loading wire:target="attachment" class="text-[10px] text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-circle-notch animate-spin mr-1"></i>
                                {{ __('Uploading attachment...') }}
                            </div>
                            @error('attachment')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>
                @else
                    <!-- No Active Chat Empty State View -->
                    <div class="m-auto text-center p-8 max-w-sm">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 mb-4">
                            <i class="fa-solid fa-paper-plane text-lg"></i>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Start collaborating') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-normal">
                            {{ __('Select an existing thread from the sidebar list, or start a new conversation with a contact.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- New Chat Search Modal popup -->
    <x-college.modal name="new-chat-search-modal" :livewireSynced="true" title="{{ __('Start Conversation') }}" maxWidth="md">
        <div class="space-y-4">
            <div>
                <label for="search-user" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search Contacts Directory') }}</label>
                <x-text-input wire:model.live.debounce.300ms="searchQuery" id="search-user" type="search" placeholder="{{ __('Type student or staff name…') }}" class="w-full" />
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-750 max-h-60 overflow-y-auto">
                @forelse ($searchResults as $user)
                    <button type="button" wire:click="startNewChat({{ $user->id }})" x-on:click="$dispatch('close-modal', 'new-chat-search-modal')" class="w-full flex items-center gap-3 py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-750 text-left rounded-lg transition mt-1">
                        <x-college.avatar :name="$user->name" size="sm" class="shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        </div>
                    </button>
                @empty
                    @if ($searchQuery !== '')
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-4">{{ __('No matching users found.') }}</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-4">{{ __('Type a name in search bar above to look up system users.') }}</p>
                    @endif
                @endforelse
            </div>

            <div class="flex items-center justify-end border-t border-gray-100 dark:border-gray-700 pt-4 mt-6">
                <button type="button" x-on:click="$dispatch('close-modal', 'new-chat-search-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </x-college.modal>

</div>
