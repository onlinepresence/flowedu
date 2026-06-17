<div class="mx-auto max-w-7xl space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left Side: List of Assignments (spanning 2 columns on large screens) -->
        <div class="space-y-4 lg:col-span-2">
            <!-- Search & Filters -->
            <x-college.filter-card cols="2" class="mb-4">
                <div>
                    <x-input-label for="search" :value="__('Search')" />
                    <x-text-input id="search" type="text" class="mt-1 block w-full text-sm" placeholder="{{ __('Search assignments by teacher, course...') }}" wire:model.live.debounce.300ms="search" />
                </div>
                <div>
                    <x-input-label for="filterSessionId" :value="__('Academic Session')" />
                    <select id="filterSessionId" wire:model.live="filterSessionId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">{{ __('All Academic Sessions') }}</option>
                        @foreach ($allSessions as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} @if($s->is_current) ({{ __('Current') }}) @endif</option>
                        @endforeach
                    </select>
                </div>
            </x-college.filter-card>

            <!-- Bulk Migration Alert Bar -->
            @if ($currentSession && $filterSessionId && (int)$filterSessionId !== $currentSession->id && !empty($selectedIds))
                <div class="flex items-center justify-between rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200">
                    <span class="font-medium">{{ __(':count assignments selected.', ['count' => count($selectedIds)]) }}</span>
                    <button
                        type="button"
                        wire:click="moveToCurrentSession"
                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none"
                    >
                        <i class="fa-solid fa-angles-right animate-pulse"></i>
                        {{ __('Move to current session (:session)', ['session' => $currentSession->name]) }}
                    </button>
                </div>
            @endif

            <!-- Table Card -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="w-10 px-4 py-3 text-left">
                                    <input
                                        wire:model.live="selectAll"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Teacher') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Course / Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Level / Session') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($rows as $row)
                                <tr wire:key="ta-{{ $row->id }}" class="{{ $editingId === $row->id ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : '' }}">
                                    <td class="w-10 px-4 py-4">
                                        <input
                                            wire:model.live="selectedIds"
                                            value="{{ $row->id }}"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $row->teacher?->lastname }} {{ $row->teacher?->othernames }}
                                        @if($row->teacher?->staff_id)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $row->teacher->staff_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-gray-900 dark:text-white font-medium">{{ $row->course?->code }} — {{ $row->course?->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->program?->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-gray-900 dark:text-white">{{ __('Level') }} {{ $row->level * 100 }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->session?->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <button
                                                type="button"
                                                wire:click="startEdit({{ $row->id }})"
                                                title="{{ __('Edit') }}"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                <i class="fa-solid fa-pencil fa-lg"></i>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openDeleteModal({{ $row->id }})"
                                                title="{{ __('Remove Assignment') }}"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <i class="fa-solid fa-trash-can fa-lg"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No course assignments found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">{{ $rows->links() }}</div>
            </div>
        </div>

        <!-- Right Side: Create/Edit Form Card -->
        <div class="lg:col-span-1">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white font-semibold">
                    {{ $isEditing ? __('Update Assignment') : __('Assign Lecturer') }}
                </h2>

                <form wire:submit.prevent="save" class="space-y-4">
                    <!-- Teacher select -->
                    <div>
                        <x-input-label for="teacher_id" :value="__('Teacher')" />
                        <select id="teacher_id" wire:model="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">{{ __('Select Lecturer…') }}</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->lastname }} {{ $t->othernames }} @if($t->staff_id) ({{ $t->staff_id }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('teacher_id')" class="mt-1" />
                    </div>

                    <!-- Program select -->
                    <div>
                        <x-input-label for="program_id" :value="__('Program')" />
                        <select id="program_id" wire:model.live="program_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">{{ __('Select Program…') }}</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('program_id')" class="mt-1" />
                    </div>

                    <!-- Level select -->
                    <div>
                        <x-input-label for="level" :value="__('Level')" />
                        <select id="level" wire:model="level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach (range(1, $maxLevels) as $lv)
                                <option value="{{ $lv }}">{{ __('Level') }} {{ $lv * 100 }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('level')" class="mt-1" />
                    </div>

                    <!-- Course select (disabled until Program is selected) -->
                    <div>
                        <x-input-label for="course_id" :value="__('Course')" />
                        <select id="course_id" wire:model="course_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" @disabled(! $program_id)>
                            <option value="">{{ __('Select Course…') }}</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
                    </div>

                    <!-- Session select -->
                    <div>
                        <x-input-label for="session_id" :value="__('Academic Session')" />
                        <select id="session_id" wire:model="session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">{{ __('Select Session…') }}</option>
                            @foreach ($formSessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} @if($s->is_current) ({{ __('Current') }}) @endif</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('session_id')" class="mt-1" />
                    </div>

                    <!-- Form actions -->
                    <div class="flex items-center justify-end gap-2 pt-2">
                        @if ($isEditing)
                            <button
                                type="button"
                                wire:click="cancelEdit"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                {{ __('Cancel') }}
                            </button>
                        @endif
                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 flex items-center gap-1"
                        >
                            <i class="fa-solid fa-check"></i>
                            {{ $isEditing ? __('Update') : __('Assign') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <x-college.modal name="ta-delete" :title="__('Remove Assignment?')" :show="true" maxWidth="md" livewireSynced>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Are you sure you want to remove this lecturer assignment? This will decouple this lecturer from this course immediately.') }}
            </p>
            <x-slot:footer>
                <button type="button" wire:click="closeDeleteModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-250">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    {{ __('Remove Assignment') }}
                </button>
            </x-slot:footer>
        </x-college.modal>
    @endif
</div>
