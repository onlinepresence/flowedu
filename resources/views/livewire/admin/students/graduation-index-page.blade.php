<div class="mx-auto max-w-7xl space-y-6">

    <!-- Header Row / Context Banner -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-blue-50/50 p-4 border border-blue-200 dark:bg-blue-950/10 dark:border-blue-900/40">
        <div class="flex items-center gap-2.5 text-sm text-blue-800 dark:text-blue-300">
            <i class="fa-solid fa-circle-info text-blue-500"></i>
            <span><strong>{{ __('Academic context') }}:</strong> {{ $currentSession->name ?? __('No current session set') }}</span>
        </div>
        <button
            type="button"
            x-on:click="$dispatch('open-modal', 'clearance-settings-modal')"
            class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-300 dark:border-blue-900 transition-colors"
        >
            <i class="fa-solid fa-sliders"></i>
            {{ __('Clearance Settings') }}
        </button>
    </div>

    <!-- Process & Stats Grid -->
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Process Form (Left 2 cols) -->
        <div class="lg:col-span-2 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Process Graduation') }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('Graduate the eligible Level 400 student class.') }}</p>
            </div>
            <div class="space-y-4 p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="grad-level" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Student level') }}</label>
                        <x-text-input id="grad-level" type="text" value="{{ __('Level 400 only') }}" readonly class="block w-full bg-gray-50" />
                    </div>
                    <div>
                        <label for="grad-program" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Program (optional)') }}</label>
                        <select wire:model.live="processProgramId" id="grad-program" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">{{ __('All programs') }}</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="grad-session" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Academic session') }}</label>
                        <select wire:model="processSessionId" id="grad-session" class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">{{ __('Use current session') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name ?? __('Session #:id', ['id' => $s->id]) }}</option>
                            @endforeach
                        </select>
                        @error('processSessionId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="grad-date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Graduation date') }}</label>
                        <x-text-input wire:model="graduationDate" id="grad-date" type="date" class="block w-full" />
                        @error('graduationDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-lg bg-amber-50 p-4 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/50">
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        {{ __('Eligible students in Level 400: :count', ['count' => $eligiblePreview]) }}
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                        {{ __('This marks matching approved students as graduated. Confirm that all required academic and financial clearances have been obtained prior.') }}
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'confirm-graduation-modal')"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 focus:outline-none"
                >
                    {{ __('Run Graduation Process') }}
                </button>
            </div>
        </div>

        <!-- Stats (Right 1 col) -->
        <div class="lg:col-span-1 space-y-6">
            <x-card class="p-6 flex flex-col justify-between">
                <div class="space-y-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Graduation Statistics') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-lg bg-purple-50/50 p-4 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Graduated') }}</span>
                            <span class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 block mt-1">{{ $statsTotal }}</span>
                        </div>
                        <div class="rounded-lg bg-green-50/50 p-4 dark:bg-green-950/20 border border-green-100 dark:border-green-900/40">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('This Calendar Year') }}</span>
                            <span class="text-3xl font-extrabold text-green-600 dark:text-green-400 block mt-1">{{ $statsThisYear }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 border-t border-gray-150 pt-4 text-center">
                    <button
                        type="button"
                        wire:click="refreshStats"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-600 hover:text-purple-500 dark:text-purple-400 focus:outline-none"
                    >
                        <span wire:loading wire:target="refreshStats" class="h-3 w-3 animate-spin rounded-full border border-purple-600 border-t-transparent"></span>
                        <i wire:loading.remove wire:target="refreshStats" class="fa-solid fa-arrows-rotate"></i>
                        {{ __('Refresh Stats') }}
                    </button>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Graduated Directory Table -->
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-150 pb-4">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Graduated Directory') }}</h2>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="grad-list-session" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Session') }}</label>
                    <select wire:model.live="listSessionFilter" id="grad-list-session" class="block rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('All Sessions') }}</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label for="grad-list-program" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Program') }}</label>
                    <select wire:model.live="listProgramFilter" id="grad-list-program" class="block rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('All Programs') }}</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto -mx-6 -my-5 mt-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Graduation Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="grad-row-{{ $row->id }}">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium">
                                <div class="font-semibold">{{ $row->student ? trim(implode(' ', array_filter([$row->student->firstname, $row->student->lastname]))) : '—' }}</div>
                                <div class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5">{{ $row->student?->index_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->student?->program?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->graduation_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-200">
                                    {{ $row->status ?? 'graduated' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No graduations recorded.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $rows->links() }}
        </div>
    </x-card>

    <!-- Clearance Configuration Modal -->
    <x-college.modal name="clearance-settings-modal" :title="__('Student Clearance Configurations')">
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('Select which campus departments a student must successfully clear from before they are permitted to run the graduation process.') }}
            </p>

            <div class="grid gap-4 sm:grid-cols-2 max-h-96 overflow-y-auto pr-1">
                @foreach ($clearanceCatalog as $key => $label)
                    <div class="rounded-lg border border-gray-200 p-3.5 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30 space-y-2">
                        <label class="flex items-center gap-2.5 text-sm font-bold text-gray-900 dark:text-white cursor-pointer">
                            <input type="checkbox" wire:model="clearanceDepartments.{{ $key }}" class="rounded border-gray-300 text-purple-650 focus:ring-purple-500" />
                            {{ $label }}
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-gray-500 dark:text-gray-400 cursor-pointer pl-6">
                            <input type="checkbox" wire:model="clearanceNotRequired.{{ $key }}" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                            {{ __('Default as not required') }}
                        </label>
                    </div>
                @endforeach
            </div>

            @error('clearanceDepartments')
                <p class="text-xs text-red-600 dark:text-red-450 font-semibold mt-2">{{ $message }}</p>
            @enderror

            <x-slot name="footer">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'clearance-settings-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    wire:click="saveClearanceConfiguration"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 focus:outline-none"
                >
                    <span wire:loading wire:target="saveClearanceConfiguration" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    {{ __('Save Clearance Setup') }}
                </button>
            </x-slot>
        </div>
    </x-college.modal>

    <!-- Run Graduation Confirmation Modal -->
    <x-college.confirm-modal
        name="confirm-graduation-modal"
        type="warning"
        :title="__('Confirm Graduation Process')"
        confirmText="{{ __('Run Graduation') }}"
        wireConfirm="processGraduation"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to execute the bulk graduation process for eligible Level 400 students? This will process and move all cleared Level 400 students matching the program filter to Graduated status.') }}
        </p>
    </x-college.confirm-modal>

</div>
