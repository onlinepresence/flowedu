<div class="mx-auto max-w-7xl space-y-6">
    <x-slot name="headerActions">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-create-session')"
            class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
        >
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
            {{ __('Add session') }}
        </button>
    </x-slot>

    <div wire:key="session-cards" class="space-y-6">
        @forelse ($sessions as $session)
            <div
                wire:key="sess-card-{{ $session->id }}"
                class="overflow-hidden rounded-lg border bg-white shadow-sm dark:bg-gray-800 {{ $session->is_current ? 'border-purple-500 border-l-4' : 'border-gray-200 dark:border-gray-700' }}"
            >
                <div class="flex flex-col gap-4 border-b border-gray-200 p-6 dark:border-gray-700 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $session->name ?? __('Session #:id', ['id' => $session->id]) }}</h2>
                            @if ($session->is_current)
                                <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-850 dark:bg-purple-900/40 dark:text-purple-200">{{ __('Current session') }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-mono">
                            {{ $session->start_date?->format('jS F, Y') ?? '—' }} – {{ $session->end_date?->format('jS F, Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @unless ($session->is_current)
                            @if ($this->isOldSession($session))
                                <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed" title="{{ __('Old/completed academic year cannot be reactivated') }}">
                                    <i class="fa-solid fa-star text-base"></i>
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="setCurrent({{ $session->id }})"
                                    class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform"
                                    title="{{ __('Set current') }}"
                                >
                                    <i class="fa-solid fa-star text-base"></i>
                                </button>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                                <i class="fa-solid fa-circle-check text-xs"></i>
                                {{ __('Current') }}
                            </span>
                        @endunless

                        @if ($this->isOldSession($session))
                            <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed" title="{{ __('Completed session cannot be edited') }}">
                                <i class="fa-solid fa-pen text-base"></i>
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="openEdit({{ $session->id }})"
                                class="text-purple-600 hover:text-purple-500 hover:scale-110 transition-transform"
                                title="{{ __('Edit') }}"
                            >
                                <i class="fa-solid fa-pen text-base"></i>
                            </button>
                        @endif

                        @if (!$this->canDeleteSession($session))
                            <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed" title="{{ __('Session has already started or is completed and cannot be deleted') }}">
                                <i class="fa-solid fa-trash text-base"></i>
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="confirmDeleteSession({{ $session->id }})"
                                class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform"
                                title="{{ __('Delete') }}"
                            >
                                <i class="fa-solid fa-trash text-base"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="p-6 pt-4">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Semesters / terms') }}</h3>
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($session->semesters as $semester)
                            <div
                                wire:key="sem-{{ $semester->id }}"
                                class="rounded-md border p-3 text-sm {{ $semester->is_active ? 'border-purple-300 bg-purple-50/50 dark:border-purple-700 dark:bg-purple-950/20' : 'border-gray-200 dark:border-gray-600' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $semester->name ?? '—' }}</span>
                                    @if ($semester->is_active)
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-purple-500" title="{{ __('Active term') }}"></span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $semester->start_date?->format('jS F, Y') ?? '—' }} – {{ $semester->end_date?->format('jS F, Y') ?? '—' }}
                                </p>
                            </div>
                        @empty
                            <p class="text-sm italic text-gray-400 dark:text-gray-500">{{ __('No terms defined.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <x-college.empty-state
                :title="__('No academic sessions found')"
                :description="__('Add an academic session using the button above.')"
            >
                <x-slot:icon>
                    <i class="fa-solid fa-calendar-days text-4xl text-gray-300 dark:text-gray-600 block"></i>
                </x-slot:icon>
            </x-college.empty-state>
        @endforelse
    </div>

    @if ($sessions->isNotEmpty())
        <div class="pt-4">{{ $sessions->links() }}</div>
    @endif

    <x-modal name="manage-session" focusable maxWidth="2xl">
        <form wire:submit="saveSession" class="max-h-[85vh] space-y-4 overflow-y-auto p-6">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $editingId ? __('Edit academic session') : __('New academic session') }}
                </h2>
                <button type="button" wire:click="cancelSessionModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="{{ __('Close') }}">
                    <i class="fa-solid fa-times text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="session-name" :value="__('Session name')" />
                    <x-text-input
                        wire:model="formName"
                        id="session-name"
                        type="text"
                        maxlength="20"
                        class="mt-1 block w-full"
                        placeholder="e.g. 2026/2027"
                    />
                    <x-input-error :messages="$errors->get('formName')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="session-start" :value="__('Start date')" />
                    <x-text-input
                        wire:model="formStartDate"
                        id="session-start"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('formStartDate')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="session-end" :value="__('End date')" />
                    <x-text-input
                        wire:model="formEndDate"
                        id="session-end"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('formEndDate')" class="mt-1" />
                </div>
                <div class="md:col-span-2 py-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.boolean="formIsCurrent" id="form-is-current" class="sr-only peer" />
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('Set as current academic session') }}</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Semesters / terms') }}</h3>
                    <button type="button" wire:click="addSemesterRow" class="text-xs font-semibold text-purple-600 hover:text-purple-500 dark:text-purple-400">
                        {{ __('Add term') }}
                    </button>
                </div>
                <div class="space-y-3">
                    @foreach ($semesterRows as $i => $semesterRow)
                        <div wire:key="sem-row-{{ $i }}" class="relative rounded-md border border-gray-200 p-3 dark:border-gray-600">
                            @if (count($semesterRows) > 1)
                                <button
                                    type="button"
                                    wire:click="removeSemesterRow({{ $i }})"
                                    class="absolute right-2 top-2 text-gray-400 hover:text-red-500"
                                    aria-label="{{ __('Remove') }}"
                                >
                                    <i class="fa-solid fa-times text-sm" aria-hidden="true"></i>
                                </button>
                            @endif
                            <div class="grid grid-cols-1 gap-3 pr-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <x-input-label :value="__('Name')" />
                                    <x-text-input
                                        type="text"
                                        wire:model="semesterRows.{{ $i }}.name"
                                        maxlength="50"
                                        class="mt-1 block w-full text-sm"
                                        placeholder="e.g. First Semester"
                                    />
                                    <x-input-error :messages="$errors->get('semesterRows.'.$i.'.name')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Start')" />
                                    <x-text-input type="date" wire:model="semesterRows.{{ $i }}.start_date" class="mt-1 block w-full text-sm" />
                                    <x-input-error :messages="$errors->get('semesterRows.'.$i.'.start_date')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label :value="__('End')" />
                                    <x-text-input type="date" wire:model="semesterRows.{{ $i }}.end_date" class="mt-1 block w-full text-sm" />
                                    <x-input-error :messages="$errors->get('semesterRows.'.$i.'.end_date')" class="mt-1" />
                                </div>
                                <div class="md:col-span-2 py-1">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.boolean="semesterRows.{{ $i }}.is_active" id="sem-active-{{ $i }}" class="sr-only peer" />
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                        <span class="ms-3 text-xs font-semibold text-gray-750 dark:text-gray-400">{{ __('Active term') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="button"
                    wire:click="cancelSessionModal"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >{{ __('Cancel') }}</button>
                <button
                    type="submit"
                    class="rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                >{{ __('Save') }}</button>
            </div>
        </form>
    </x-modal>

    <!-- Delete Confirm Modal -->
    <x-college.confirm-modal
        name="confirm-delete-session-modal"
        type="danger"
        :title="__('Delete Academic Session')"
        confirmText="{{ __('Delete') }}"
        wireConfirm="deleteSession"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Are you sure you want to delete this session and its terms? Related data may be removed by the database.') }}
        </p>
    </x-college.confirm-modal>
</div>
