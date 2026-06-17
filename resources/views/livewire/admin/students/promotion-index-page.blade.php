<div class="mx-auto max-w-7xl space-y-6">
    @if (! $currentSession)
        <div class="overflow-hidden rounded-lg border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/30">
            <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-100">{{ __('Promotion unavailable') }}</h2>
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">{{ __('Set a current academic session before managing student promotion. Go to Academic Sessions and mark the active year as current.') }}</p>
            <a href="{{ route('admin.academic.sessions') }}" wire:navigate class="mt-4 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">{{ __('Manage academic sessions') }}</a>
        </div>
    @else
        <!-- Current Academic Context Banner -->
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-blue-50/50 p-4 border border-blue-200 dark:bg-blue-950/10 dark:border-blue-900/40">
            <div class="flex items-center gap-2.5 text-sm text-blue-800 dark:text-blue-300">
                <i class="fa-solid fa-circle-info text-blue-500"></i>
                <span><strong>{{ __('Academic session') }}:</strong> {{ $currentSession->name ?? '—' }}</span>
                @if ($activeSemester)
                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $activeSemester->name }}</span>
                @endif
            </div>
            
            <button
                type="button"
                x-on:click="$dispatch('open-modal', 'promotion-settings-modal')"
                class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-300 dark:border-blue-900 transition-colors"
            >
                <i class="fa-solid fa-sliders"></i>
                {{ __('Promotion settings') }}
            </button>
        </div>

        <!-- Automatic Promotion Info -->
        @if ($promotionMode === 'auto')
            <div class="rounded-lg bg-gray-50 p-6 border border-gray-200 dark:bg-gray-900/30 dark:border-gray-700">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <i class="fa-solid fa-robot text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Automatic Promotion is active') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('The scheduled background tasks will handle the transitions of students at the end of academic semesters automatically. You do not need to manually trigger promotions unless necessary.') }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <!-- Manual Promotion Active State Summary -->
            <div class="rounded-lg bg-gray-50 p-6 border border-gray-200 dark:bg-gray-900/30 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Manual Promotion is active') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Transition student levels manually by previewing specific programs or pinning individuals for bulk transitions.') }}
                    </p>
                </div>
                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'manual-promotion-modal')"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none"
                >
                    <i class="fa-solid fa-graduation-cap"></i>
                    {{ __('Run Manual Promotion') }}
                </button>
            </div>
        @endif
    @endif

    <!-- Promotion History Batches Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Promotion History') }}</h2>
            <div class="flex items-center gap-2">
                <label for="history-session" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Session') }}:</label>
                <select wire:model.live="historySessionFilter" id="history-session" class="rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500">
                    <option value="">{{ __('All Sessions') }}</option>
                    @foreach ($sessions as $sess)
                        <option value="{{ $sess->id }}">{{ $sess->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Session') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Transition') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Students Promoted') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($batches as $batch)
                        <tr wire:key="batch-row-{{ $batch->promotion_date?->toDateString() }}-{{ $batch->academic_session_id }}-{{ $batch->from_level }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $batch->promotion_date?->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $batch->academicSession?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">
                                {{ __('Lvl :from', ['from' => $batch->from_level]) }} &rarr; {{ __('Lvl :to', ['to' => $batch->to_level]) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    {{ __(':count student(s)', ['count' => $batch->student_count]) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="viewBatch('{{ $batch->promotion_date?->toDateString() }}', {{ $batch->academic_session_id }}, {{ $batch->from_level }}, {{ $batch->to_level }})"
                                        class="text-purple-650 hover:text-purple-500 hover:scale-110 transition-transform"
                                        title="{{ __('View Batch Students') }}"
                                    >
                                        <i class="fa-solid fa-eye text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmRevertBatch('{{ $batch->promotion_date?->toDateString() }}', {{ $batch->academic_session_id }}, {{ $batch->from_level }}, {{ $batch->to_level }})"
                                        class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform"
                                        title="{{ __('Revert Batch Promotion') }}"
                                    >
                                        <i class="fa-solid fa-rotate-left text-base"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No promotions recorded.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $batches->links() }}
        </div>
    </div>

    <!-- Modals -->

    <!-- Manual Bulk Promotion Modal -->
    <x-college.modal name="manual-promotion-modal" :title="__('Manual Student Promotion Run')" maxWidth="4xl">
        <!-- Important Advice/Warning Banner -->
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
            <div class="flex gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 text-base"></i>
                <div class="text-sm text-amber-800 dark:text-amber-300">
                    <h4 class="font-bold">{{ __('Important Advice on Manual Promotion Hierarchy') }}</h4>
                    <p class="mt-1 text-xs">
                        {{ __('To avoid overlapping student year transitions, it is recommended to run promotions from highest level to lowest (e.g. Level 300 to 400 first, then Level 200 to 300, etc.).') }}
                    </p>
                    <p class="mt-1.5 text-xs font-semibold">
                        {{ __('Note: Ensure all eligible Level 400 students have been processed and marked as Graduated before starting the promotion run for Level 300s.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="promo-from" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From level') }}</label>
                    <select wire:model="fromLevel" id="promo-from" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        @foreach (['100' => __('Level 100'), '200' => __('Level 200'), '300' => __('Level 300'), '400' => __('Level 400')] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="promo-to" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('To level') }}</label>
                    <select wire:model="toLevel" id="promo-to" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        @foreach (['100' => __('Level 100'), '200' => __('Level 200'), '300' => __('Level 300'), '400' => __('Level 400')] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('toLevel')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="promo-program" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Program (optional)') }}</label>
                    <select wire:model.live="programFilter" id="promo-program" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('All programs') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t border-gray-150 pt-4 dark:border-gray-700">
                <label for="promo-student-search" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Pin specific students to preview (optional)') }}</label>
                <x-text-input
                    wire:model.live.debounce.300ms="studentSearch"
                    id="promo-student-search"
                    type="search"
                    class="block w-full text-sm"
                    placeholder="{{ __('Type index number or student name…') }}"
                />
                @if (count($studentSearchHits) > 0)
                    <ul class="mt-2 max-h-40 overflow-y-auto rounded-md border border-gray-250 dark:border-gray-700 bg-white dark:bg-gray-900 z-50 shadow-lg relative">
                        @foreach ($studentSearchHits as $hit)
                            <li class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-2 text-sm dark:border-gray-800">
                                <span class="text-gray-800 dark:text-gray-100">{{ $hit['label'] }}</span>
                                <button type="button" wire:click="addManualPick({{ $hit['id'] }})" class="text-xs font-semibold text-purple-600 hover:text-purple-500">{{ __('Pin') }}</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if (count($manualPickIds) > 0)
                <div class="mt-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Pinned Students') }}</p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($manualPickIds as $pid)
                            @php $st = $manualPickStudents->get($pid); @endphp
                            <li class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 border border-purple-200 px-3 py-1 text-xs text-purple-800 dark:bg-purple-950/20 dark:border-purple-900/50 dark:text-purple-300">
                                <i class="fa-solid fa-thumbtack text-[10px]"></i>
                                {{ $st?->index_number ?? $pid }}
                                <button type="button" wire:click="removeManualPick({{ $pid }})" class="text-purple-500 hover:text-red-650 font-bold ml-1" title="{{ __('Remove Pinned Student') }}">×</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @error('preview')<p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-150 dark:border-gray-700">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'manual-promotion-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ __('Close') }}
                </button>
                <button
                    type="button"
                    wire:click="previewPromotion"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-750 dark:bg-purple-500 dark:hover:bg-purple-600 focus:outline-none"
                >
                    <span wire:loading wire:target="previewPromotion" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    {{ __('Preview Promotion') }}
                </button>
            </div>

            <!-- Preview Results Inside Modal -->
            @if ($showPreview && count($previewList) > 0)
                <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Promotion Preview') }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Check or uncheck students to include or exclude from this batch transition.') }}</p>
                    </div>
                    <div class="overflow-x-auto max-h-60">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="w-10 px-6 py-3"></th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Index') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Current') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Next level') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Program') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($previewList as $row)
                                    <tr wire:key="promo-prev-{{ $row['id'] }}">
                                        <td class="px-6 py-3">
                                            <input type="checkbox" wire:model="previewStudentIds" value="{{ $row['id'] }}" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                                        </td>
                                        <td class="px-6 py-3 font-mono text-sm text-gray-900 dark:text-white">{{ $row['index_number'] }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white font-medium">{{ $row['fullname'] }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['current_year'] }}</td>
                                        <td class="px-6 py-3 text-sm text-purple-600 dark:text-purple-400 font-semibold">{{ $toLevel }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $row['program_name'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('previewStudentIds')<p class="px-6 pb-2 text-sm text-red-650 dark:text-red-400">{{ $message }}</p>@enderror
                    @error('confirm')<p class="px-6 pb-2 text-sm text-red-650 dark:text-red-400">{{ $message }}</p>@enderror
                    <div class="flex gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        <button type="button" wire:click="cancelPreview" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Cancel Preview') }}</button>
                        <button
                            type="button"
                            wire:click="confirmPromotion"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus:outline-none"
                        >
                            <span wire:loading wire:target="confirmPromotion" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            {{ __('Confirm & Apply Promotion') }}
                        </button>
                    </div>
                </div>
            @elseif ($showPreview)
                <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ __('No students match the current filters.') }}</div>
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        <button type="button" wire:click="cancelPreview" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ __('Cancel') }}</button>
                    </div>
                </div>
            @endif
        </div>
    </x-college.modal>

    <!-- Promotion Settings Modal -->
    <x-college.modal name="promotion-settings-modal" :title="__('Promotion Mode Settings')">
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Specify how student level promotions are managed in the system. Automatic runs through a scheduled worker; Manual requires administrative action.') }}
                </p>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <input type="radio" wire:model="promotionMode" value="auto" class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-500" />
                        <div>
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ __('Automatic Promotion') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Cron worker automatically handles level transition on active sessions.') }}</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <input type="radio" wire:model="promotionMode" value="manual" class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-500" />
                        <div>
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ __('Manual Promotion') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Admins select level shifts, preview, and apply bulk transitions.') }}</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <x-slot name="footer">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'promotion-settings-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    wire:click="savePromotionMode"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 focus:outline-none"
                >
                    <span wire:loading wire:target="savePromotionMode" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    {{ __('Save Changes') }}
                </button>
            </x-slot>
        </div>
    </x-college.modal>

    <!-- View Batch Students Modal -->
    <x-college.modal name="view-batch-modal" :title="__('Promoted Batch Students')">
        <div class="space-y-4">
            <div class="flex items-center justify-between text-sm bg-purple-50 p-3 rounded-lg dark:bg-purple-950/20 border border-purple-150 dark:border-purple-900/50">
                <div>
                    <span class="font-bold text-purple-800 dark:text-purple-300 block">
                        {{ __('Transition Level :from to :to', ['from' => $selectedBatchFromLevel, 'to' => $selectedBatchToLevel]) }}
                    </span>
                    <span class="text-xs text-purple-600 dark:text-purple-400">
                        {{ __('Date: :date', ['date' => $selectedBatchDate]) }}
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-purple-600 dark:text-purple-400 block">{{ __('Academic Session') }}</span>
                    <span class="font-bold text-purple-800 dark:text-purple-300">
                        {{ $selectedBatchSessionName }}
                    </span>
                </div>
            </div>

            <div class="max-h-80 overflow-y-auto border border-gray-150 rounded-lg dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">{{ __('Index Number') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">{{ __('Student Name') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($batchStudents as $item)
                            <tr wire:key="batch-student-{{ $item['promotion_id'] }}">
                                <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ $item['index_number'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['fullname'] }}</td>
                                <td class="px-4 py-2 text-right text-sm">
                                    <button
                                        type="button"
                                        wire:click="revertIndividualPromotion({{ $item['promotion_id'] }})"
                                        wire:confirm="{{ __('Are you sure you want to revert this individual student\'s promotion? They will be moved back to Level :lvl.', ['lvl' => $selectedBatchFromLevel]) }}"
                                        class="text-red-650 hover:text-red-500 transition-colors"
                                        title="{{ __('Revert Student Level') }}"
                                    >
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-400">
                                    {{ __('No student details found for this batch.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot name="footer">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'view-batch-modal')"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                {{ __('Close') }}
            </button>
        </x-slot>
    </x-college.modal>

    <!-- Revert Batch Confirm Modal -->
    <x-college.confirm-modal
        name="revert-batch-confirm-modal"
        type="warning"
        :title="__('Revert Promotion Batch')"
        confirmText="{{ __('Revert Batch') }}"
        wireConfirm="revertBatch"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to revert this student promotion batch? All students in this promotion record will be returned to Level :from, and the history logs for this transition will be deleted.', ['from' => $revertBatchFromLevel]) }}
        </p>
    </x-college.confirm-modal>
</div>
