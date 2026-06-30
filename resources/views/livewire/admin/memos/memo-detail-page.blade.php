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
            @if ($isEditing)
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Edit Draft Memo') }}</h2>
                    
                    <form wire:submit="resubmitMemo('send')" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <x-input-label for="editTitle" :value="__('Title')" />
                            <x-text-input wire:model="editTitle" id="editTitle" type="text" class="mt-1 block w-full text-sm font-sans" required />
                            <x-input-error :messages="$errors->get('editTitle')" class="mt-1" />
                        </div>

                        <!-- Content -->
                        <div>
                            <x-input-label for="editContent" :value="__('Content')" />
                            <x-textarea-input wire:model="editContent" id="editContent" rows="10" class="mt-1 block w-full text-sm font-sans" required />
                            <x-input-error :messages="$errors->get('editContent')" class="mt-1" />
                        </div>

                        <!-- Signatory review -->
                        @php
                            $memos_require_signature = filter_var(
                                \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_require_signature')->value('setting_value') ?? false,
                                FILTER_VALIDATE_BOOLEAN
                            );
                            $memos_multiple_signatories = filter_var(
                                \App\Models\Setting::query()->where('setting_key', 'system_preferences.memos_multiple_signatories')->value('setting_value') ?? false,
                                FILTER_VALIDATE_BOOLEAN
                            );
                        @endphp
                        <div class="space-y-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                            <x-input-label :value="__('Sign-off / Signatures')" />
                            
                            @if (auth()->user()?->hasAdminPermission('self_sign_memo'))
                                <div>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="editSelfSign" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700">
                                        <span class="ml-2 text-xs font-semibold text-purple-700 dark:text-purple-400">
                                            <i class="fa-solid fa-file-signature mr-1"></i>
                                            {{ __('Self-sign this memo') }}
                                        </span>
                                    </label>
                                </div>
                            @endif

                            @if (!$editSelfSign || $memos_multiple_signatories)
                                <div class="space-y-2">
                                    <!-- Selected signatories badges -->
                                    @if (!empty($editSelectedSignatories))
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            @foreach ($editSelectedSignatories as $index => $sigId)
                                                @php $sigUser = \App\Models\User::find($sigId); @endphp
                                                @if ($sigUser)
                                                    <span class="inline-flex items-center gap-1.5 rounded bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                        {{ $index + 1 }}. {{ $sigUser->name }}
                                                        <button type="button" wire:click="removeSignatory({{ $sigId }})" class="hover:text-purple-900 text-purple-400 font-bold">&times;</button>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    <!-- Search input -->
                                    <div class="relative">
                                        <input type="text" wire:model.live="signatorySearch" placeholder="Search staff to add as signatory..." class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        @if ($signatorySearch !== '')
                                            <div class="absolute z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg text-sm">
                                                @php
                                                    $searchResults = \App\Models\User::query()
                                                        ->where('active', true)
                                                        ->where('type', 'admin')
                                                        ->where('id', '!=', auth()->id())
                                                        ->where('name', 'like', '%' . $signatorySearch . '%')
                                                        ->whereNotIn('id', $editSelectedSignatories)
                                                        ->limit(5)
                                                        ->get();
                                                @endphp
                                                @forelse($searchResults as $u)
                                                    <button type="button" wire:click="addSignatory({{ $u->id }})" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white">
                                                        {{ $u->name }} ({{ $u->adminRoleSlug() }})
                                                    </button>
                                                @empty
                                                    <div class="px-3 py-2 text-gray-500">No results found</div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('editSelectedSignatories')" class="mt-1" />
                                </div>
                            @endif
                        </div>

                        <!-- CC Recipients Section -->
                        <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <x-input-label :value="__('CC (Carbon Copy) Recipients')" />
                            
                            <!-- CC Users search/badges -->
                            <div class="space-y-2">
                                @if (!empty($editCcUsers))
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach ($editCcUsers as $ccId)
                                            @php $ccUser = \App\Models\User::find($ccId); @endphp
                                            @if ($ccUser)
                                                <span class="inline-flex items-center gap-1.5 rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                                    {{ $ccUser->name }}
                                                    <button type="button" wire:click="removeCcUser({{ $ccId }})" class="hover:text-blue-900 text-blue-400 font-bold">&times;</button>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                <div class="relative">
                                    <input type="text" wire:model.live="ccSearch" placeholder="Search staff to CC..." class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
                                    @if ($ccSearch !== '')
                                        <div class="absolute z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg text-sm">
                                            @php
                                                $ccSearchResults = \App\Models\User::query()
                                                    ->where('active', true)
                                                    ->where('type', 'admin')
                                                    ->where('id', '!=', auth()->id())
                                                    ->where('name', 'like', '%' . $ccSearch . '%')
                                                    ->whereNotIn('id', $editCcUsers)
                                                    ->limit(5)
                                                    ->get();
                                            @endphp
                                            @forelse($ccSearchResults as $u)
                                                <button type="button" wire:click="addCcUser({{ $u->id }})" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white">
                                                    {{ $u->name }} ({{ $u->adminRoleSlug() }})
                                                </button>
                                            @empty
                                                <div class="px-3 py-2 text-gray-500">No results found</div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- CC Departments & Roles -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label :value="__('CC Departments')" class="mb-1" />
                                    <div x-data="{
                                        search: '',
                                        open: false,
                                        options: @js($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])),
                                        selected: @entangle('editCcDepartments').live
                                    }" class="relative">
                                        <!-- Selected Badges -->
                                        <div class="flex flex-wrap gap-1.5 mb-2">
                                            <template x-for="id in selected" :key="id">
                                                <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                    <span x-text="options.find(o => o.id == id)?.name || id"></span>
                                                    <button type="button" @click="selected = selected.filter(x => x != id)" class="hover:text-purple-900 font-bold">&times;</button>
                                                </span>
                                            </template>
                                        </div>
                                        <!-- Search Input -->
                                        <input
                                            type="text"
                                            x-model="search"
                                            @focus="open = true"
                                            @click.away="open = false"
                                            placeholder="Search departments to CC..."
                                            class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
                                        />
                                        <!-- Dropdown -->
                                        <div
                                            x-show="open"
                                            x-transition
                                            class="absolute自动 z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg text-xs"
                                            style="display: none;"
                                        >
                                            <template x-for="opt in options.filter(o => o.name.toLowerCase().includes(search.toLowerCase()) && !selected.includes(o.id))" :key="opt.id">
                                                <button
                                                    type="button"
                                                    @click="selected.push(opt.id); search = '';"
                                                    class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-750 dark:text-white"
                                                    x-text="opt.name"
                                                ></button>
                                            </template>
                                            <div x-show="options.filter(o => o.name.toLowerCase().includes(search.toLowerCase()) && !selected.includes(o.id)).length === 0" class="px-3 py-2 text-gray-500 italic">
                                                No departments found
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <x-input-label :value="__('CC Staff Roles')" class="mb-1" />
                                    <div x-data="{
                                        search: '',
                                        open: false,
                                        options: @js($roles->map(fn($r) => ['id' => $r->id, 'name' => $r->display_name])),
                                        selected: @entangle('editCcRoles').live
                                    }" class="relative">
                                        <!-- Selected Badges -->
                                        <div class="flex flex-wrap gap-1.5 mb-2">
                                            <template x-for="id in selected" :key="id">
                                                <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                                    <span x-text="options.find(o => o.id == id)?.name || id"></span>
                                                    <button type="button" @click="selected = selected.filter(x => x != id)" class="hover:text-blue-900 font-bold">&times;</button>
                                                </span>
                                            </template>
                                        </div>
                                        <!-- Search Input -->
                                        <input
                                            type="text"
                                            x-model="search"
                                            @focus="open = true"
                                            @click.away="open = false"
                                            placeholder="Search roles to CC..."
                                            class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
                                        />
                                        <!-- Dropdown -->
                                        <div
                                            x-show="open"
                                            x-transition
                                            class="absolute自动 z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg text-xs"
                                            style="display: none;"
                                        >
                                            <template x-for="opt in options.filter(o => o.name.toLowerCase().includes(search.toLowerCase()) && !selected.includes(o.id))" :key="opt.id">
                                                <button
                                                    type="button"
                                                    @click="selected.push(opt.id); search = '';"
                                                    class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-750 dark:text-white"
                                                    x-text="opt.name"
                                                ></button>
                                            </template>
                                            <div x-show="options.filter(o => o.name.toLowerCase().includes(search.toLowerCase()) && !selected.includes(o.id)).length === 0" class="px-3 py-2 text-gray-500 italic">
                                                No roles found
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attachments Section (Edit Mode) -->
                        <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <x-input-label :value="__('Attachments')" />
                            
                            <!-- Existing Attachments -->
                            @if ($memo->attachments->count() > 0)
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Current Attachments') }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach ($memo->attachments as $file)
                                            @php
                                                $isStagedForDeletion = in_array($file->id, $deletedAttachmentIds);
                                            @endphp
                                            <div class="flex items-center justify-between p-3 rounded-md border {{ $isStagedForDeletion ? 'border-red-300 bg-red-50/50 dark:border-red-900/50 dark:bg-red-950/10' : 'border-gray-150 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800' }}">
                                                <div class="flex items-center gap-2 overflow-hidden mr-2">
                                                    <i class="fa-solid fa-file {{ $isStagedForDeletion ? 'text-red-500' : 'text-purple-500 dark:text-purple-400' }} shrink-0"></i>
                                                    <div class="text-xs truncate {{ $isStagedForDeletion ? 'line-through text-red-700 dark:text-red-400' : '' }}">
                                                        <p class="font-medium truncate" title="{{ $file->file_name }}">
                                                            {{ $file->file_name }}
                                                        </p>
                                                        <p class="{{ $isStagedForDeletion ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                            {{ $file->formatted_size }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @if ($isStagedForDeletion)
                                                    <button type="button" wire:click="unstageAttachmentDeletion({{ $file->id }})" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 shrink-0">
                                                        <i class="fa-solid fa-rotate-left mr-1"></i>{{ __('Undo') }}
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="stageAttachmentDeletion({{ $file->id }})" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400 shrink-0">
                                                        <i class="fa-solid fa-trash-can mr-1"></i>{{ __('Remove') }}
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Add New Attachments -->
                            <div class="space-y-2 pt-2">
                                <x-input-label :value="__('Add New Attachments')" class="text-xs text-gray-500 dark:text-gray-400" />
                                <input type="file" wire:model="attachments" multiple class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-gray-700 dark:file:text-gray-200" />
                                <x-input-error :messages="$errors->get('attachments.*')" class="mt-1" />
                            </div>
                        </div>

                        <!-- Resubmission choice if rejected -->
                        @if ($memo->signatories()->where('status', 'rejected')->exists())
                            <div class="space-y-3 border-t border-gray-150 dark:border-gray-700 pt-4">
                                <x-input-label :value="__('Resubmission Option')" />
                                <div class="flex flex-col gap-2">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" wire:model="resubmissionChoice" value="restart" class="text-purple-600 focus:ring-purple-500 dark:bg-gray-950/20">
                                        <span class="ml-2 text-xs font-semibold text-gray-805 dark:text-gray-250">
                                            {{ __('Restart from beginning (resets all signature records)') }}
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" wire:model="resubmissionChoice" value="resume" class="text-purple-600 focus:ring-purple-500 dark:bg-gray-950/20">
                                        <span class="ml-2 text-xs font-semibold text-gray-805 dark:text-gray-250">
                                            {{ __('Resume from rejected signatory (preserves prior signatures)') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="toggleEditing" class="rounded-md border border-gray-350 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                                {{ __('Cancel') }}
                            </button>
                            <button type="button" wire:click="resubmitMemo('draft')" class="rounded-md border border-purple-300 bg-purple-50/50 px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-100 dark:bg-purple-950/20 dark:text-purple-400">
                                {{ __('Save Changes (Draft)') }}
                            </button>
                            <button type="submit" class="rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                                {{ __('Submit Memo') }}
                            </button>
                        </div>
                    </form>
                </div>
            @else
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
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-green-300">
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
            @endif

            <!-- Contextual Actions Panel -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Actions') }}</h3>
                <div class="flex flex-wrap gap-3">
                    @php
                        $isPendingSignatory = $memo->signatories()->where('user_id', auth()->id())->where('status', 'pending')->exists()
                            || ($memo->status === 'pending_signature' && $memo->signing_user_id === auth()->id() && !$memo->signatories()->where('user_id', auth()->id())->exists());
                    @endphp

                    <!-- Edit draft option -->
                    @if ($memo->status === 'draft' && $memo->sender_id === auth()->id() && !$isEditing)
                        <button
                            type="button"
                            wire:click="toggleEditing"
                            class="inline-flex items-center gap-1.5 justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700"
                        >
                            <i class="fa-solid fa-pen-to-square"></i>
                            {{ __('Edit Draft') }}
                        </button>
                    @endif

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
                    @if (auth()->user()?->hasAdminPermission('forward_memo') && $memo->status === 'sent' && $memo->confidentiality_level !== 'public')
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
