<div class="mx-auto max-w-7xl space-y-6">
    <div>
        <a href="{{ route('teacher.memos.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-purple-600 hover:text-purple-500 dark:text-purple-400 dark:hover:text-purple-300">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            {{ __('Back to Memos') }}
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-200" role="status">
            {{ session('status') }}
        </div>
    @endif

    @error('download')
        <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-200" role="alert">
            {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Memo Content Column -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-6">
                <!-- Header details -->
                <div class="border-b border-gray-100 dark:border-gray-700 pb-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <!-- Confidentiality Badge -->
                            @if ($memo->confidentiality_level === 'public')
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-950/40 dark:text-green-300">
                                    {{ __('Public') }}
                                </span>
                            @elseif ($memo->confidentiality_level === 'internal')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                    {{ __('Internal') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                    {{ __('Confidential') }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ $memo->created_at->format('F d, Y H:i') }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $memo->title }}
                    </h1>

                    <!-- Metadata Table -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-md text-sm">
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Sender') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $memo->sender_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Recipient') }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $memo->recipient_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Memo Body -->
                <div class="text-gray-800 dark:text-gray-250 leading-relaxed font-sans text-sm whitespace-pre-line">
                    {!! nl2br(e($memo->content)) !!}
                </div>

                <!-- Attachments -->
                @if ($memo->attachments->count() > 0)
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ __('Attachments') }} ({{ $memo->attachments->count() }})</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($memo->attachments as $file)
                                <div class="flex items-center justify-between p-3 rounded-md border border-gray-150 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800">
                                    <div class="flex items-center gap-2 overflow-hidden mr-2">
                                        <i class="fa-solid fa-file text-purple-500 dark:text-purple-400 shrink-0"></i>
                                        <div class="text-xs truncate">
                                            <p class="font-medium text-gray-900 dark:text-white truncate" title="{{ $file->file_name }}">
                                                {{ $file->file_name }}
                                            </p>
                                            <p class="text-gray-500 dark:text-gray-400">
                                                {{ $file->formatted_size }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="downloadAttachment({{ $file->id }})"
                                        class="text-purple-600 hover:text-purple-500 dark:text-purple-400 dark:hover:text-purple-300 font-semibold text-xs shrink-0"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Action card -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Actions') }}</h3>
                <div class="flex flex-wrap gap-3">
                    @php
                        $userReceipt = $memo->readReceipts()->where('user_id', auth()->id())->first();
                        $showAcknowledge = $userReceipt && is_null($userReceipt->acknowledged_at) && $memo->status === 'sent';
                    @endphp

                    @if ($showAcknowledge)
                        <button
                            type="button"
                            wire:click="acknowledgeMemo"
                            class="inline-flex items-center gap-1.5 justify-center rounded-md border border-green-300 bg-green-50/50 px-4 py-2 text-sm font-medium text-green-700 shadow-sm hover:bg-green-100 dark:border-green-800 dark:bg-green-950/20 dark:text-green-350 dark:hover:bg-green-950/40"
                        >
                            <i class="fa-solid fa-check-double"></i>
                            {{ __('Acknowledge Receipt') }}
                        </button>
                    @endif

                    @if ($userReceipt && $userReceipt->acknowledged_at)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-3 py-2 text-xs font-semibold text-green-800 border border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/50">
                            <i class="fa-solid fa-circle-check text-green-600"></i>
                            {{ __('Acknowledged on') }}: {{ $userReceipt->acknowledged_at->format('M d, Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar / Signatures approvals -->
        <div class="space-y-6">
            @if ($memo->signatories->count() > 0)
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                        {{ __('Official Signatures') }}
                    </h3>
                    <div class="space-y-2">
                        @foreach ($memo->signatories as $sig)
                            <div class="flex items-center gap-2 p-2 rounded bg-gray-50 dark:bg-gray-900 border border-gray-150 dark:border-gray-700 text-xs">
                                @if ($sig->status === 'signed')
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                    </span>
                                @elseif ($sig->status === 'rejected')
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                    </span>
                                @else
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-750 dark:bg-amber-950/40 dark:text-amber-400">
                                        <i class="fa-solid fa-clock text-[10px]"></i>
                                    </span>
                                @endif
                                <div class="truncate">
                                    <p class="font-semibold text-gray-800 dark:text-gray-255 truncate">{{ $sig->user->name }}</p>
                                    @if ($sig->remarks)
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate italic">"{{ $sig->remarks }}"</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
