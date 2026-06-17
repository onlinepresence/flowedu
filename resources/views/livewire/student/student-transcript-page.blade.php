<div class="space-y-6">
    <!-- Eligibility & Action Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="space-y-1">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                @if ($student)
                    {{ $student->lastname }} {{ $student->firstname }} {{ $student->othernames }}
                @endif
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Student Index Number:') }} <span class="font-mono text-purple-600 font-bold">{{ $student?->index_number }}</span>
            </p>
        </div>
        <div>
            @if ($isEligible)
                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'request-transcript-modal')"
                    class="inline-flex items-center gap-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 text-xs font-bold transition shadow-sm focus:outline-none"
                >
                    <i class="fa-solid fa-file-signature"></i>
                    {{ __('Request Official Transcript') }}
                </button>
            @else
                <div class="rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 px-4 py-2.5 text-xs text-amber-700 dark:text-amber-300 flex items-center gap-2 font-medium">
                    <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                    <span>{{ __('Transcript requesting is restricted to final year & graduated students.') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left 2 Cols: Transcript Grades Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider text-purple-600">{{ __('Academic Performance Records') }}</h2>
                </div>
                @if ($redirectEnabled)
                    <div class="p-8 text-center space-y-4">
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
                            <i class="fa-solid fa-square-poll-vertical text-xl"></i>
                        </div>
                        <div class="space-y-1 max-w-md mx-auto">
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('External Grading Software Active') }}</h3>
                            <p class="text-2xs text-gray-500 dark:text-gray-400 leading-normal">
                                {{ __('Your institution manages student grading records on an external system. Please consult the external grading portal to check your full academic performance transcript details.') }}
                            </p>
                        </div>
                        @if ($externalGradingUrl)
                            <div>
                                <a 
                                    href="{{ $externalGradingUrl }}" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-2xs font-bold transition shadow-sm focus:outline-none"
                                >
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    {{ __('Access External Portal') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Course Code') }}</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Course Title') }}</th>
                                    <th class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Grade') }}</th>
                                    <th class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Grade Points') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($rows as $row)
                                    <tr wire:key="tr-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                        <td class="px-6 py-4 font-semibold text-gray-950 dark:text-white whitespace-nowrap">{{ $row->course?->code ?? '—' }}</td>
                                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">{{ $row->course?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 text-center font-bold text-purple-700 dark:text-purple-300 bg-purple-50/30 dark:bg-purple-950/10">{{ $row->grade ?? '—' }}</td>
                                        <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400 font-mono">{{ $row->grade_points ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <i class="fa-solid fa-graduation-cap text-gray-300 text-5xl mb-4"></i>
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No Records') }}</h3>
                                            <p class="mt-1 text-xs">{{ __('No academic records have been published yet.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right 1 Col: Requests List & History -->
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 uppercase tracking-wider text-purple-600">
                    {{ __('Official Requests History') }}
                </h2>
                <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
                    @forelse ($requests as $req)
                        <div wire:key="req-{{ $req->id }}" class="p-3.5 rounded-lg border border-gray-200 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-900/30 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-2xs font-mono text-gray-500 dark:text-gray-400">#{{ $req->id }} · {{ $req->created_at->format('M d, Y') }}</span>
                                @if ($req->status === 'pending')
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 dark:bg-yellow-950/20 px-2 py-0.5 text-2xs font-semibold text-yellow-700 dark:text-yellow-400">
                                        <i class="fa-solid fa-clock mr-1 animate-pulse"></i>
                                        {{ __('Pending') }}
                                    </span>
                                @elseif ($req->status === 'processed')
                                    <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-950/20 px-2 py-0.5 text-2xs font-semibold text-green-700 dark:text-green-400">
                                        <i class="fa-solid fa-circle-check mr-1"></i>
                                        {{ __('Processed') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 dark:bg-red-950/20 px-2 py-0.5 text-2xs font-semibold text-red-700 dark:text-red-400">
                                        <i class="fa-solid fa-circle-xmark mr-1"></i>
                                        {{ __('Rejected') }}
                                    </span>
                                @endif
                            </div>
                            @if ($req->purpose)
                                <div class="text-xs text-gray-700 dark:text-gray-300">
                                    <span class="font-semibold text-gray-500">{{ __('Purpose:') }}</span> {{ $req->purpose }}
                                </div>
                            @endif
                            @if ($req->remarks)
                                <div class="p-2 rounded bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 text-3xs text-gray-500 dark:text-gray-400 leading-normal">
                                    <span class="font-bold text-gray-650">{{ __('Admin Notes:') }}</span> {{ $req->remarks }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-folder-open text-gray-300 text-3xl mb-2"></i>
                            <p class="text-xs">{{ __('No official requests made yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Request Transcript Modal -->
    <x-college.modal name="request-transcript-modal" :title="__('Submit Transcript Request')" maxWidth="md">
        <form wire:submit.prevent="requestTranscript" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Purpose of Request (Optional)') }}</label>
                <input
                    wire:model="purpose"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    placeholder="e.g. Employment Application, Further Education"
                />
                @error('purpose') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'request-transcript-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none shadow-sm transition"
                >
                    {{ __('Submit Request') }}
                </button>
            </div>
        </form>
    </x-college.modal>
</div>
