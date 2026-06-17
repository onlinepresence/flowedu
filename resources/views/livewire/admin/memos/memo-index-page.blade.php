<div class="mx-auto max-w-7xl space-y-6">
    @if (auth()->user()?->hasAdminPermission('create_memo'))
        <x-slot name="headerActions">
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-create-memo')"
                class="inline-flex justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <i class="fa-solid fa-pen-to-square mr-2 mt-0.5"></i>
                {{ __('Write Memo') }}
            </button>
        </x-slot>
    @endif

    @if (session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-200" role="status">
            {{ session('status') }}
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            @foreach(['inbox' => 'Inbox', 'outbox' => 'Outbox', 'pending' => 'Pending Signature', 'drafts' => 'Drafts'] as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="border-b-2 py-4 px-1 text-sm font-medium transition duration-150 whitespace-nowrap {{ $activeTab === $tab ? 'border-purple-500 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    {{ __($label) }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Filters and Search -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xs">
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

        <div class="w-full sm:w-48">
            <x-select-input
                wire:model.live="confidentiality"
                class="block w-full text-sm"
            >
                <option value="all">{{ __('All Levels') }}</option>
                <option value="public">{{ __('Public') }}</option>
                <option value="internal">{{ __('Internal') }}</option>
                <option value="confidential">{{ __('Confidential') }}</option>
            </x-select-input>
        </div>
    </div>

    <!-- Memo Listing Grid -->
    <x-card class="overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-gray-700 -mx-6 -my-5">
            @forelse ($memos as $memo)
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition duration-150 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
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

                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <a href="{{ route('admin.memos.show', $memo->id) }}" wire:navigate class="hover:text-purple-600 dark:hover:text-purple-400">
                                {{ $memo->title }}
                            </a>
                        </h2>

                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 pr-6">
                            {{ Str::limit(strip_tags($memo->content), 180) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('From') }}:</span>
                                {{ $memo->sender_name }}
                            </span>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('To') }}:</span>
                                {{ $memo->recipient_name }}
                            </span>
                            @if ($memo->signingUser)
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Signatory') }}:</span>
                                    {{ $memo->signingUser->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between md:justify-end gap-4 shrink-0">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ $memo->updated_at->format('M d, Y H:i') }}
                        </span>
                        <a
                            href="{{ route('admin.memos.show', $memo->id) }}"
                            wire:navigate
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('Open') }}
                            <i class="fa-solid fa-chevron-right ml-2 text-[0.65rem] opacity-70"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-6">
                    <x-college.empty-state
                        :title="__('No memos found')"
                        :description="__('Try searching or matching other filter configurations.')"
                        class="border-none bg-transparent p-6"
                    >
                        <x-slot:icon>
                            <i class="fa-solid fa-envelope-open text-4xl text-gray-300 dark:text-gray-600 block"></i>
                        </x-slot:icon>
                    </x-college.empty-state>
                </div>
            @endforelse
        </div>
    </x-card>

    <!-- Create Memo Modal -->
    <x-college.modal
        name="create-memo-form"
        :title="__('Write New Memo')"
        maxWidth="2xl"
    >
            <form id="create-memo-form-fields" wire:submit="saveMemo('send')" class="space-y-6">
                <!-- Title -->
                <div>
                    <x-input-label for="memo_title" :value="__('Title')" />
                    <x-text-input wire:model="title" id="memo_title" type="text" required maxlength="255" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <!-- Recipient Type & Target -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="recipient_type" :value="__('Send to')" />
                        <x-select-input wire:model.live="recipient_type" id="recipient_type" class="mt-1 block w-full">
                            <option value="department">{{ __('Department') }}</option>
                            <option value="faculty">{{ __('Faculty') }}</option>
                            <option value="role">{{ __('Staff Role') }}</option>
                            <option value="user">{{ __('Individual Staff member') }}</option>
                        </x-select-input>
                    </div>

                    <div>
                        @if ($recipient_type === 'department')
                            <x-input-label for="recipient_entity_id" :value="__('Select Department')" />
                            <x-select-input wire:model="recipient_entity_id" id="recipient_entity_id" class="mt-1 block w-full">
                                <option value="">{{ __('Choose Department...') }}</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('recipient_entity_id')" class="mt-1" />
                        @elseif ($recipient_type === 'faculty')
                            <x-input-label for="recipient_entity_id" :value="__('Select Faculty')" />
                            <x-select-input wire:model="recipient_entity_id" id="recipient_entity_id" class="mt-1 block w-full">
                                <option value="">{{ __('Choose Faculty...') }}</option>
                                @foreach ($faculties as $fac)
                                    <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('recipient_entity_id')" class="mt-1" />
                        @elseif ($recipient_type === 'role')
                            <x-input-label for="recipient_role_id" :value="__('Select Staff Role')" />
                            <x-select-input wire:model="recipient_role_id" id="recipient_role_id" class="mt-1 block w-full">
                                <option value="">{{ __('Choose Role...') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('recipient_role_id')" class="mt-1" />
                        @elseif ($recipient_type === 'user')
                            <x-input-label for="recipient_entity_id" :value="__('Select User')" />
                            <x-select-input wire:model="recipient_entity_id" id="recipient_entity_id" class="mt-1 block w-full">
                                <option value="">{{ __('Choose User...') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->adminRoleSlug() }})</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('recipient_entity_id')" class="mt-1" />
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Confidentiality -->
                    <div>
                        <x-input-label for="confidentiality_level" :value="__('Confidentiality Level')" />
                        <x-select-input wire:model="confidentiality_level" id="confidentiality_level" class="mt-1 block w-full">
                            <option value="public">{{ __('Public (Visible to students and staff)') }}</option>
                            <option value="internal">{{ __('Internal (Visible to all staff members)') }}</option>
                            <option value="confidential">{{ __('Confidential (Restrict to recipient/HOD/Dean)') }}</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('confidentiality_level')" class="mt-1" />
                    </div>

                    <!-- Signatory review -->
                    <div>
                        <x-input-label :value="__('Sign-off / Signatures')" />
                        
                        @if ($memos_require_signature)
                            <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400 font-medium">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                {{ __('Official signature is required before dispatch.') }}
                            </p>
                        @else
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Optional approvals needed before final release.') }}
                            </p>
                        @endif

                        @if (auth()->user()?->hasAdminPermission('self_sign_memo'))
                            <div class="mt-2 mb-3">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="self_sign" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700">
                                    <span class="ml-2 text-xs font-semibold text-purple-700 dark:text-purple-400">
                                        <i class="fa-solid fa-file-signature mr-1"></i>
                                        {{ __('Self-sign this memo') }}
                                    </span>
                                </label>
                            </div>
                        @endif

                        @if (!$self_sign || $memos_multiple_signatories)
                            @if ($memos_multiple_signatories)
                                <div class="mt-2 block w-full rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 max-h-40 overflow-y-auto space-y-2">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Select Signatories:') }}</p>
                                    @foreach ($users as $u)
                                        <label class="flex items-center text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 p-1 rounded">
                                            <input type="checkbox" wire:model="selected_signatories" value="{{ $u->id }}" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:bg-gray-900 dark:border-gray-700">
                                            <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $u->name }} ({{ $u->adminRoleSlug() }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <x-select-input wire:model="selected_signatories.0" id="signing_user_id" class="mt-2 block w-full">
                                    <option value="">{{ __('Select Signatory...') }}</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->adminRoleSlug() }})</option>
                                    @endforeach
                                </x-select-input>
                            @endif
                            <x-input-error :messages="$errors->get('selected_signatories')" class="mt-1" />
                        @endif
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <x-input-label for="memo_content" :value="__('Memo Content')" />
                    <x-textarea-input wire:model="content" id="memo_content" rows="8" required class="mt-1 block w-full font-sans text-sm" placeholder="{{ __('Type official memo context here...') }}" />
                    <x-input-error :messages="$errors->get('content')" class="mt-1" />
                </div>

                <!-- File attachments -->
                <div>
                    <x-input-label class="mb-1" :value="__('Attachments')" />
                    <input type="file" wire:model="attachments" multiple class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-gray-700 dark:file:text-gray-200" />
                    <x-input-error :messages="$errors->get('attachments.*')" class="mt-1" />
                </div>
            </form>

            <x-slot:footer>
                <x-secondary-button type="button" wire:click="closeCreate" class="px-4 py-2">{{ __('Cancel') }}</x-secondary-button>
                <x-secondary-button type="button" wire:click="saveMemo('draft')" class="px-4 py-2">{{ __('Save Draft') }}</x-secondary-button>
                @php
                    $isReview = false;
                    if ($memos_require_signature && !$self_sign) {
                        $isReview = true;
                    } elseif (!empty(array_filter($selected_signatories))) {
                        $isReview = true;
                    }
                @endphp
                <button type="submit" form="create-memo-form-fields" class="rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    {{ $isReview ? __('Submit for Review') : __('Send Memo') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
</div>
