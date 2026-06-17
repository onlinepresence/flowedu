<div class="mx-auto max-w-7xl space-y-6">

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button 
                type="button"
                wire:click="switchTab('history')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold transition {{ $activeTab === 'history' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-300' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
            >
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                {{ __('Requests & History') }}
            </button>
            <button 
                type="button"
                wire:click="switchTab('generate')"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold transition {{ $activeTab === 'generate' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-300' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
            >
                <i class="fa-solid fa-file-invoice mr-2"></i>
                {{ __('Generate Transcript') }}
            </button>
        </nav>
    </div>

    @if ($activeTab === 'history')
        <!-- Tab 1: Requests & History -->
        <div class="space-y-6">
            <!-- Search & Filter Card -->
            <div class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="relative md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Search Student') }}</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                            </span>
                            <input 
                                wire:model.live.debounce.300ms="search"
                                type="text"
                                class="block w-full rounded-md border-gray-300 pl-10 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="{{ __('Search by index number or student name...') }}"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Program') }}</label>
                        <select 
                            wire:model.live="filterProgram"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('All Programs') }}</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</label>
                        <select 
                            wire:model.live="filterStatus"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ __('All Requests') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="processed">{{ __('Processed') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <div class="overflow-hidden rounded-lg border border-gray-250 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Index Number') }}</th>
                                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Student Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">{{ __('Purpose') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Requested') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400 bg-purple-50/50 dark:bg-purple-950/20">{{ __('Current CGPA') }}</th>
                                <th scope="col" class="px-6 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($requestRows as $row)
                                <tr wire:key="request-row-{{ $row['id'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-850">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $row['index_number'] }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">
                                        {{ $row['name'] }}
                                        <div class="text-3xs text-gray-450 mt-0.5">{{ $row['program_name'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                        {{ $row['purpose'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-650 dark:text-gray-450 font-mono whitespace-nowrap">
                                        {{ $row['created_at']->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-purple-700 dark:text-purple-300 bg-purple-50/30 dark:bg-purple-950/10">
                                        {{ $row['cgpa'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @if ($row['status'] === 'pending')
                                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-950/30 dark:text-yellow-300">
                                                <i class="fa-solid fa-clock mr-1 animate-pulse"></i>
                                                {{ __('Pending') }}
                                            </span>
                                        @elseif ($row['status'] === 'processed')
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-950/30 dark:text-green-300">
                                                <i class="fa-solid fa-circle-check mr-1"></i>
                                                {{ __('Processed') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-350" title="{{ $row['remarks'] }}">
                                                <i class="fa-solid fa-circle-xmark mr-1"></i>
                                                {{ __('Rejected') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-4">
                                            <button 
                                                type="button"
                                                wire:click="showTranscriptModal({{ $row['student_id'] }}, false, {{ $row['id'] }})"
                                                class="text-purple-650 hover:text-purple-500 hover:scale-110 transition-transform focus:outline-none"
                                                title="{{ __('View Preview') }}"
                                            >
                                                <i class="fa-solid fa-eye text-base"></i>
                                            </button>
                                            @if ($row['status'] === 'pending')
                                                <button 
                                                    type="button"
                                                    wire:click="markAsProcessed({{ $row['id'] }})"
                                                    class="text-emerald-600 hover:text-emerald-500 hover:scale-110 transition-transform focus:outline-none"
                                                    title="{{ __('Mark as Processed') }}"
                                                >
                                                    <i class="fa-solid fa-circle-check text-base"></i>
                                                </button>
                                                <button 
                                                    type="button"
                                                    wire:click="openRejectModal({{ $row['id'] }})"
                                                    class="text-red-650 hover:text-red-500 hover:scale-110 transition-transform focus:outline-none"
                                                    title="{{ __('Reject Request') }}"
                                                >
                                                    <i class="fa-solid fa-circle-xmark text-base"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-file-invoice text-gray-300 text-5xl mb-4"></i>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No Requests Found') }}</h3>
                                        <p class="mt-1 text-xs">{{ __('There are no official transcript requests matching your filters.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($requests->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Tab 2: Generate Transcript Form -->
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <div class="rounded-lg border border-gray-250 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 uppercase tracking-wider text-purple-650">
                        {{ __('Generate Single Student Transcript') }}
                    </h2>
                    <form wire:submit.prevent="generateTranscript" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Student Index Number') }}</label>
                            <input 
                                wire:model="student_index" 
                                type="text" 
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="e.g. STU-1004"
                            />
                            @error('student_index') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 transition focus:outline-none"
                            >
                                <i class="fa-solid fa-square-poll-vertical text-base"></i>
                                {{ __('Generate Preview') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                @if ($showInlinePreview && $selectedStudent)
                    <div class="space-y-6">
                        <!-- Bio-Data Header -->
                        <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $selectedStudent->lastname }} {{ $selectedStudent->firstname }} {{ $selectedStudent->othernames }}
                                    </h2>
                                    <p class="text-sm font-semibold text-purple-700 dark:text-purple-400">
                                        {{ __('Index Number:') }} {{ $selectedStudent->index_number }}
                                    </p>
                                    <div class="mt-2 grid gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <div><span class="font-bold">{{ __('Program:') }}</span> {{ $selectedStudent->program?->name ?? '—' }}</div>
                                        <div><span class="font-bold">{{ __('Department:') }}</span> {{ $selectedStudent->program?->department?->name ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="bg-purple-100 dark:bg-purple-950/40 rounded-lg p-4 text-center sm:min-w-[150px]">
                                    <span class="block text-2xs font-semibold text-purple-650 dark:text-purple-400 uppercase tracking-wider">{{ __('Cumulative CGPA') }}</span>
                                    <span class="block text-3xl font-extrabold text-purple-700 dark:text-purple-300 mt-1">
                                        {{ $selectedStudentStats['cgpa'] }}
                                    </span>
                                    <span class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5 block">
                                        {{ __('Total Credits: :n', ['n' => $selectedStudentStats['credit_hours']]) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Semester Iteration -->
                        <div class="space-y-6">
                            @foreach ($transcriptData as $semKey => $semData)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                                    <h3 class="text-sm font-bold text-purple-700 dark:text-purple-450 border-b border-purple-100 dark:border-purple-900/50 pb-2 mb-3">
                                        {{ $semKey }}
                                    </h3>
                                    <table class="min-w-full divide-y divide-gray-250 dark:divide-gray-700 text-xs">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="py-2 text-left font-semibold text-gray-500">{{ __('Course Code') }}</th>
                                                <th scope="col" class="py-2 text-left font-semibold text-gray-500">{{ __('Course Title') }}</th>
                                                <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Score') }}</th>
                                                <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Grade') }}</th>
                                                <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Points') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @foreach ($semData['results'] as $res)
                                                <tr>
                                                    <td class="py-2 font-semibold text-gray-900 dark:text-white">{{ $res['code'] }}</td>
                                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $res['name'] }}</td>
                                                    <td class="py-2 text-center text-gray-600 dark:text-gray-400">{{ $res['score'] }}</td>
                                                    <td class="py-2 text-center font-bold text-gray-900 dark:text-white">{{ $res['grade'] }}</td>
                                                    <td class="py-2 text-center text-gray-650 dark:text-gray-450">{{ $res['points'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <div class="mt-3 flex justify-between items-center bg-gray-50 dark:bg-gray-900/30 p-2.5 rounded text-2xs font-bold text-gray-650 dark:text-gray-350">
                                        <div>{{ __('Semester GPA:') }} <span class="text-purple-700 ml-1">{{ $semData['gpa'] }}</span></div>
                                        <div>{{ __('Cumulative CGPA:') }} <span class="text-purple-700 ml-1">{{ $semData['cgpa'] }}</span></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Footer options -->
                        <div class="flex justify-between items-center border-t border-gray-200 pt-4 dark:border-gray-700">
                            <a 
                                href="{{ route('admin.grading.transcripts', ['index_number' => $selectedStudent->index_number]) }}" 
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 text-sm font-semibold transition shadow-sm focus:outline-none"
                            >
                                <i class="fa-solid fa-print"></i>
                                {{ __('Print Official Transcript') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                        <i class="fa-solid fa-file-invoice text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No Preview Generated') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Enter a student\'s index number on the left and click Generate Preview to view their transcript here.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Transcript Details Modal -->
    <x-college.modal
        name="view-transcript-modal"
        :title="__('Official Academic Transcript Preview')"
        maxWidth="5xl"
    >
        @if ($selectedStudent)
            <div class="space-y-6">
                <!-- Bio-Data Header inside Modal -->
                <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $selectedStudent->lastname }} {{ $selectedStudent->firstname }} {{ $selectedStudent->othernames }}
                            </h2>
                            <p class="text-sm font-semibold text-purple-700 dark:text-purple-400">
                                {{ __('Index Number:') }} {{ $selectedStudent->index_number }}
                            </p>
                            <div class="mt-2 grid gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <div><span class="font-bold">{{ __('Program:') }}</span> {{ $selectedStudent->program?->name ?? '—' }}</div>
                                <div><span class="font-bold">{{ __('Department:') }}</span> {{ $selectedStudent->program?->department?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-950/40 rounded-lg p-4 text-center sm:min-w-[150px]">
                            <span class="block text-2xs font-semibold text-purple-650 dark:text-purple-400 uppercase tracking-wider">{{ __('Cumulative CGPA') }}</span>
                            <span class="block text-3xl font-extrabold text-purple-700 dark:text-purple-300 mt-1">
                                {{ $selectedStudentStats['cgpa'] }}
                            </span>
                            <span class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5 block">
                                {{ __('Total Credits: :n', ['n' => $selectedStudentStats['credit_hours']]) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Semester Iteration -->
                <div class="space-y-6">
                    @foreach ($transcriptData as $semKey => $semData)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                            <h3 class="text-sm font-bold text-purple-700 dark:text-purple-450 border-b border-purple-100 dark:border-purple-900/50 pb-2 mb-3">
                                {{ $semKey }}
                            </h3>
                            <table class="min-w-full divide-y divide-gray-250 dark:divide-gray-700 text-xs">
                                <thead>
                                    <tr>
                                        <th scope="col" class="py-2 text-left font-semibold text-gray-500">{{ __('Course Code') }}</th>
                                        <th scope="col" class="py-2 text-left font-semibold text-gray-500">{{ __('Course Title') }}</th>
                                        <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Score') }}</th>
                                        <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Grade') }}</th>
                                        <th scope="col" class="py-2 text-center font-semibold text-gray-500">{{ __('Points') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($semData['results'] as $res)
                                        <tr>
                                            <td class="py-2 font-semibold text-gray-900 dark:text-white">{{ $res['code'] }}</td>
                                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $res['name'] }}</td>
                                            <td class="py-2 text-center text-gray-600 dark:text-gray-400">{{ $res['score'] }}</td>
                                            <td class="py-2 text-center font-bold text-gray-900 dark:text-white">{{ $res['grade'] }}</td>
                                            <td class="py-2 text-center text-gray-650 dark:text-gray-450">{{ $res['points'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-3 flex justify-between items-center bg-gray-50 dark:bg-gray-900/30 p-2.5 rounded text-2xs font-bold text-gray-650 dark:text-gray-350">
                                <div>{{ __('Semester GPA:') }} <span class="text-purple-700 ml-1">{{ $semData['gpa'] }}</span></div>
                                <div>{{ __('Cumulative CGPA:') }} <span class="text-purple-700 ml-1">{{ $semData['cgpa'] }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer options -->
                <div class="flex justify-between items-center border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <a 
                            href="{{ route('admin.grading.transcripts', ['index_number' => $selectedStudent->index_number]) }}" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-md bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 text-sm font-semibold transition shadow-sm focus:outline-none"
                        >
                            <i class="fa-solid fa-print"></i>
                            {{ __('Print Official Transcript') }}
                        </a>

                        @if ($activeRequestId && \App\Models\TranscriptRequest::find($activeRequestId)?->status === 'pending')
                            <button
                                type="button"
                                wire:click="markAsProcessed({{ $activeRequestId }})"
                                class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 text-sm font-semibold transition shadow-sm focus:outline-none"
                            >
                                <i class="fa-solid fa-check"></i>
                                {{ __('Mark as Processed') }}
                            </button>
                        @endif
                    </div>

                    <button
                        type="button"
                        x-on:click="$dispatch('close-modal', 'view-transcript-modal')"
                        class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        @endif
    </x-college.modal>

    <!-- Reject Rejection Remarks Modal -->
    <x-college.modal
        name="reject-request-modal"
        :title="__('Reject Transcript Request')"
        maxWidth="md"
    >
        <form wire:submit.prevent="submitRejection" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Rejection Remarks / Reason') }}</label>
                <textarea
                    wire:model="rejectionRemarks"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    placeholder="e.g. Please clear your outstanding school fees of $150 before requesting a transcript."
                    required
                ></textarea>
                @error('rejectionRemarks') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', 'reject-request-modal')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus:outline-none shadow-sm transition"
                >
                    {{ __('Submit Rejection') }}
                </button>
            </div>
        </form>
    </x-college.modal>
</div>
