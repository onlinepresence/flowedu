<x-slot name="headerActions">
    <div class="flex items-center gap-2" x-data>
        <button type="button" x-on:click="$dispatch('download-roster')" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors">
            <svg class="mr-2 h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            {{ __('Download Roster') }}
        </button>
    </div>
</x-slot>

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data
    x-on:download-roster.window="$wire.downloadRoster()"
>

    <!-- Filters Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="w-full sm:w-1/3">
            <label for="session-select" class="sr-only">{{ __('Academic Session') }}</label>
            <select id="session-select" wire:model.live="academicSessionId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }} {{ $session->is_current ? __('(Current)') : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-1/2">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search student trainee by name or email...') }}" class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Trainees Grid -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max table-auto text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900/50 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        <th class="px-6 py-4">{{ __('Student Trainee') }}</th>
                        <th class="px-6 py-4">{{ __('Partnership School') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Evaluation Status') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Evaluation Score') }}</th>
                        <th class="px-6 py-4">{{ __('Notes & Feedback') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-900 dark:text-gray-200">
                    @forelse($supervisions as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors align-top">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $s->student->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->student->index_number }} &bull; {{ $s->student->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                {{ $s->partnership_school }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($s->status === 'evaluated')
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400">
                                        {{ __('Evaluated') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400">
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($s->score !== null)
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format((float)$s->score, 2) }}%</span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate whitespace-normal">
                                @if($s->evaluation_notes)
                                    <span class="text-gray-600 dark:text-gray-400 text-xs line-clamp-2">{{ $s->evaluation_notes }}</span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No evaluation submitted.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" wire:click="openEvaluateModal({{ $s->id }})" class="inline-flex items-center text-purple-600 hover:text-purple-900 dark:hover:text-purple-400 font-semibold">
                                    {{ $s->status === 'evaluated' ? __('Edit Score') : __('Evaluate') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <x-college.empty-state
                                    title="{{ __('No trainees assigned') }}"
                                    description="{{ __('You are not assigned as a supervisor for any student trainees in this academic session.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($supervisions->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $supervisions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Evaluate Student -->
    @if ($showEvaluateModal)
        <x-college.modal name="evaluate-student-modal" title="{{ __('Submit Practicum Evaluation') }}" :show="true" livewireSynced="true">
            <form id="evaluate-student-form" wire:submit.prevent="saveEvaluation" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student Trainee') }}</label>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $studentName }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $partnershipSchool }}</div>
                </div>

                <div>
                    <label for="evaluation-score" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Evaluation Rating / Score (0 - 100)') }}</label>
                    <input type="number" id="evaluation-score" wire:model="score" placeholder="85" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" min="0" max="100">
                    @error('score')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="evaluation-notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Feedback, Observations & Rubrics Evaluation Notes') }}</label>
                    <textarea id="evaluation-notes" wire:model="evaluationNotes" rows="6" placeholder="{{ __('Record specific feedback on lesson preparation, class management, pedagogy application...') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                    @error('evaluationNotes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-slot:footer>
                    <button type="button" wire:click="closeEvaluateModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="evaluate-student-form" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition-colors" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Save Evaluation') }}</span>
                        <span wire:loading>{{ __('Saving...') }}</span>
                    </button>
                </x-slot:footer>
            </form>
        </x-college.modal>
    @endif

</div>
