<div class="mx-auto max-w-7xl space-y-6">
    <!-- Breadcrumb & Back button -->
    <div>
        <a href="{{ route('admin.memos.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-purple-600 hover:text-purple-500 dark:text-purple-400 dark:hover:text-purple-300">
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

                            <!-- Status Badge -->
                            @if ($memo->status === 'draft')
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ __('Draft') }}
                                </span>
                            @elseif ($memo->status === 'pending_signature')
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                    {{ __('Pending Signature') }}
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
                        @if ($memo->signatories->count() > 0)
                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Signatures & Approvals') }}</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($memo->signatories as $sig)
                                        <div class="flex items-center gap-2 p-2 rounded bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 text-xs">
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
                                                <p class="font-semibold text-gray-800 dark:text-gray-250 truncate">{{ $sig->user->name }}</p>
                                                @if ($sig->remarks)
                                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate italic">"{{ $sig->remarks }}"</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($memo->signingUser)
                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned Signatory') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $memo->signingUser->name }}</span>
                            </div>
                        @endif
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

            <!-- Contextual Actions Panel -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Actions') }}</h3>
                <div class="flex flex-wrap gap-3">
                    @php
                        $isPendingSignatory = $memo->signatories()->where('user_id', auth()->id())->where('status', 'pending')->exists()
                            || ($memo->status === 'pending_signature' && $memo->signing_user_id === auth()->id() && !$memo->signatories()->where('user_id', auth()->id())->exists());
                    @endphp

                    @if ($memo->status === 'pending_signature' && $isPendingSignatory)
                        <div class="w-full space-y-3 p-4 bg-purple-50/50 dark:bg-purple-950/10 border border-purple-100 dark:border-purple-900/50 rounded-lg">
                            <h4 class="text-xs font-bold text-purple-900 dark:text-purple-400 uppercase tracking-wider">
                                <i class="fa-solid fa-file-signature mr-1.5"></i>
                                {{ __('Your Signature is Requested') }}
                            </h4>
                            
                            <div>
                                <label for="signature_remarks" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('Signature Remarks / Approval Note (Optional)') }}</label>
                                <x-text-input wire:model="signature_remarks" id="signature_remarks" type="text" class="block w-full text-xs" placeholder="{{ __('e.g., Verified and approved.') }}" />
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    wire:click="signMemo"
                                    class="inline-flex items-center gap-1.5 justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-500"
                                >
                                    <i class="fa-solid fa-signature"></i>
                                    {{ __('Approve & Sign') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="openReturn"
                                    class="inline-flex items-center gap-1.5 justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-500"
                                >
                                    <i class="fa-solid fa-reply"></i>
                                    {{ __('Reject & Return') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Manual Forwarding -->
                    @if (auth()->user()?->hasAdminPermission('forward_memo') && $memo->status === 'sent')
                        <button
                            type="button"
                            wire:click="openForward"
                            class="inline-flex items-center gap-1.5 justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700"
                        >
                            <i class="fa-solid fa-share-nodes"></i>
                            {{ __('Forward Memo') }}
                        </button>
                    @endif

                    <!-- Acknowledge receipt -->
                    @php
                        $userReceipt = $memo->readReceipts()->where('user_id', auth()->id())->first();
                        $showAcknowledge = $userReceipt && is_null($userReceipt->acknowledged_at) && $memo->status === 'sent';
                    @endphp

                    @if ($showAcknowledge)
                        <button
                            type="button"
                            wire:click="acknowledgeMemo"
                            class="inline-flex items-center gap-1.5 justify-center rounded-md border border-green-305 bg-green-50/50 px-4 py-2 text-sm font-medium text-green-700 shadow-sm hover:bg-green-100 dark:border-green-800 dark:bg-green-950/20 dark:text-green-350 dark:hover:bg-green-950/40"
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

        <!-- Tracking Log Timeline Column -->
        <div class="space-y-6">
            <!-- Recipient Read Receipts Feed -->
            @if ($memo->sender_id === auth()->id() || auth()->user()?->hasAdminPermission('view_all_memos'))
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b border-gray-150 dark:border-gray-700 pb-3">
                        <i class="fa-solid fa-users-viewfinder mr-1.5 text-purple-600"></i>
                        {{ __('Recipient Tracking') }}
                    </h3>
                    
                    @php
                        $activeReceipts = $memo->readReceipts()
                            ->whereNotNull('viewed_at')
                            ->with('user')
                            ->get();
                    @endphp
                    
                    <div class="flow-root">
                        <ul class="divide-y divide-gray-150 dark:divide-gray-700 -my-4">
                            @forelse ($activeReceipts as $receipt)
                                <li class="py-3 flex items-center justify-between gap-4">
                                    <div class="truncate">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $receipt->user->name }}
                                        </p>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                            {{ __('Viewed') }}: {{ $receipt->viewed_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        @if ($receipt->acknowledged_at)
                                            <span class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-950/40 dark:text-green-400">
                                                <i class="fa-solid fa-check-double text-[8px]"></i>
                                                {{ __('Acknowledged') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-750 dark:bg-amber-950/40 dark:text-amber-400">
                                                <i class="fa-solid fa-eye text-[8px]"></i>
                                                {{ __('Viewed Only') }}
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="text-xs text-gray-500 dark:text-gray-400 text-center py-6">
                                    <i class="fa-solid fa-eye-slash text-gray-300 dark:text-gray-600 block text-lg mb-1"></i>
                                    {{ __('No views or acknowledgements yet.') }}
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b border-gray-150 dark:border-gray-700 pb-3">
                    {{ __('Routing & Tracking') }}
                </h3>

                <!-- Timeline list -->
                <div class="flow-root">
                    <ul class="-mb-8">
                        @forelse ($memo->trackingLogs as $index => $log)
                            <li>
                                <div class="relative pb-8">
                                    @if ($index < $memo->trackingLogs->count() - 1)
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <!-- Action Indicator Icon -->
                                            @if ($log->action === 'sent')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-50 text-purple-600 ring-8 ring-white dark:bg-purple-950/40 dark:text-purple-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-paper-plane text-xs"></i>
                                                </span>
                                            @elseif ($log->action === 'signed')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-50 text-green-600 ring-8 ring-white dark:bg-green-950/40 dark:text-green-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-file-signature text-xs"></i>
                                                </span>
                                            @elseif ($log->action === 'forwarded')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-8 ring-white dark:bg-blue-950/40 dark:text-blue-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-arrows-spin text-xs"></i>
                                                </span>
                                            @elseif ($log->action === 'returned')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600 ring-8 ring-white dark:bg-rose-950/40 dark:text-rose-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-arrow-rotate-left text-xs"></i>
                                                </span>
                                            @elseif ($log->action === 'acknowledged')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-50 text-teal-600 ring-8 ring-white dark:bg-teal-950/40 dark:text-teal-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-circle-check text-xs"></i>
                                                </span>
                                            @else
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-600 ring-8 ring-white dark:bg-gray-950/40 dark:text-gray-400 dark:ring-gray-800">
                                                    <i class="fa-solid fa-clock text-xs"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-semibold text-gray-900 dark:text-white">
                                                    {{ ucfirst($log->action) }}
                                                </p>
                                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    {{ $log->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ __('By') }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $log->forwardedByUser ? ($log->forwardedByUser->name ?? $log->forwardedByUser->username) : 'System' }}</span>
                                            </p>
                                            @if ($log->remarks)
                                                <div class="mt-2 text-xs italic text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 p-2 rounded border-l-2 border-gray-300 dark:border-gray-700">
                                                    {{ $log->remarks }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-xs text-gray-500 dark:text-gray-400 text-center py-4">
                                {{ __('No tracking logs registered.') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Forward Memo Modal -->
    @if ($showForwardModal)
        <x-college.modal
            name="forward-memo-form"
            :title="__('Forward Memo')"
            :show="true"
            maxWidth="xl"
            livewireSynced
        >
            <form id="forward-memo-form-fields" wire:submit="forwardMemo" class="space-y-6">
                <!-- Recipient Type & Target -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="forward_recipient_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Forward to') }}</label>
                        <select wire:model.live="forward_recipient_type" id="forward_recipient_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="department">{{ __('Department') }}</option>
                            <option value="faculty">{{ __('Faculty') }}</option>
                            <option value="role">{{ __('Staff Role') }}</option>
                            <option value="user">{{ __('Individual Staff member') }}</option>
                        </select>
                    </div>

                    <div>
                        @if ($forward_recipient_type === 'department')
                            <label for="forward_recipient_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select Department') }}</label>
                            <select wire:model="forward_recipient_entity_id" id="forward_recipient_entity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="">{{ __('Choose Department...') }}</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('forward_recipient_entity_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @elseif ($forward_recipient_type === 'faculty')
                            <label for="forward_recipient_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select Faculty') }}</label>
                            <select wire:model="forward_recipient_entity_id" id="forward_recipient_entity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="">{{ __('Choose Faculty...') }}</option>
                                @foreach ($faculties as $fac)
                                    <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                                @endforeach
                            </select>
                            @error('forward_recipient_entity_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @elseif ($forward_recipient_type === 'role')
                            <label for="forward_recipient_role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select Staff Role') }}</label>
                            <select wire:model="forward_recipient_role_id" id="forward_recipient_role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="">{{ __('Choose Role...') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                            @error('forward_recipient_role_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @elseif ($forward_recipient_type === 'user')
                            <label for="forward_recipient_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select User') }}</label>
                            <select wire:model="forward_recipient_entity_id" id="forward_recipient_entity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <option value="">{{ __('Choose User...') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->adminRoleSlug() }})</option>
                                @endforeach
                            </select>
                            @error('forward_recipient_entity_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @endif
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <label for="forward_remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Remarks / Message') }}</label>
                    <textarea wire:model="forward_remarks" id="forward_remarks" rows="4" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="{{ __('Add any routing notes...') }}"></textarea>
                    @error('forward_remarks') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeForward" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="forward-memo-form-fields" class="rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700">{{ __('Forward') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif

    <!-- Return Memo Modal -->
    @if ($showReturnModal)
        <x-college.modal
            name="return-memo-form"
            :title="__('Return Memo to Sender')"
            :show="true"
            maxWidth="md"
            livewireSynced
        >
            <form id="return-memo-form-fields" wire:submit="returnMemo" class="space-y-6">
                <!-- Remarks -->
                <div>
                    <label for="return_remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reason / Corrections Required') }}</label>
                    <textarea wire:model="return_remarks" id="return_remarks" required rows="4" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="{{ __('Describe what corrections are needed...') }}"></textarea>
                    @error('return_remarks') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>
            <x-slot:footer>
                <button type="button" wire:click="closeReturn" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                <button type="submit" form="return-memo-form-fields" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-500">{{ __('Return') }}</button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
