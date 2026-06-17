<x-slot name="headerActions">
    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200/50 bg-indigo-50/50 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm dark:border-indigo-900/30 dark:bg-indigo-950/20 dark:text-indigo-400">
        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
        <i class="fa-solid fa-calendar-days text-indigo-500/70"></i>
        <span>{{ $activeTermString }}</span>
    </div>
</x-slot>

<div class="mx-auto max-w-7xl space-y-6">

    <!-- Stats Grid -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-college.stats-card
            :title="__('Assigned students')"
            :value="$assignedStudentsCount"
            color="purple"
            icon="fa-solid fa-users"
            :href="route('teacher.students')"
        />
        <x-college.stats-card
            :title="__('Courses')"
            :value="$coursesCount"
            color="blue"
            icon="fa-solid fa-book"
            :href="route('teacher.courses')"
        />
        <x-college.stats-card
            :title="__('Results pending grade')"
            :value="$pendingResultsCount"
            color="amber"
            icon="fa-solid fa-file-pen"
            :href="route('teacher.grades')"
        />
        <x-college.stats-card
            :title="__('Unread memos')"
            :value="$unreadMemosCount"
            :color="$unreadMemosCount > 0 ? 'red' : 'green'"
            icon="fa-solid fa-envelope-open-text"
            :href="route('teacher.memos.index')"
        />
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Left Column: Main Widgets -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Today's Schedule Widget -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <i class="fa-solid fa-calendar-day text-indigo-500"></i>
                        {{ __("Today's Schedule") }}
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ now()->format('l, F j, Y') }})</span>
                    </h3>
                    <a href="{{ route('teacher.timetable') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        {{ __('Full Timetable') }} &rarr;
                    </a>
                </div>

                @if ($todayClasses->isEmpty())
                    <x-college.empty-state 
                        :title="__('No classes today')" 
                        :description="__('You do not have any lectures scheduled for today. Enjoy your day!')"
                    >
                        <x-slot name="icon">
                            <i class="fa-solid fa-mug-hot text-gray-400 text-3xl"></i>
                        </x-slot>
                    </x-college.empty-state>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($todayClasses as $class)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $class->course?->code }} – {{ $class->course?->name }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $class->program?->name }}</span>
                                            @if ($class->venue)
                                                · <i class="fa-solid fa-location-dot text-gray-450 mr-0.5"></i> {{ $class->venue }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="sm:text-right shrink-0">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                                        <i class="fa-regular fa-clock text-emerald-600 dark:text-emerald-400"></i>
                                        {{ \Illuminate\Support\Str::substr((string) $class->start_time, 0, 5) }} – {{ \Illuminate\Support\Str::substr((string) $class->end_time, 0, 5) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Recent Course Materials Widget -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <i class="fa-solid fa-folder-open text-indigo-500"></i>
                        {{ __('Recent Course Materials') }}
                    </h3>
                    <a href="{{ route('teacher.courses.materials') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        {{ __('View All') }} &rarr;
                    </a>
                </div>

                @if ($recentMaterials->isEmpty())
                    <x-college.empty-state 
                        :title="__('No materials uploaded')" 
                        :description="__('Upload lecture notes, slides, and syllabus files for your students.')"
                    >
                        <x-slot name="icon">
                            <i class="fa-solid fa-cloud-arrow-up text-gray-400 text-3xl"></i>
                        </x-slot>
                    </x-college.empty-state>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($recentMaterials as $material)
                            <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                        @if (in_array(strtolower((string) $material->file_type), ['pdf']))
                                            <i class="fa-solid fa-file-pdf text-red-500 dark:text-red-400 text-lg"></i>
                                        @elseif (in_array(strtolower((string) $material->file_type), ['doc', 'docx', 'rtf', 'txt']))
                                            <i class="fa-solid fa-file-word text-blue-500 dark:text-blue-400 text-lg"></i>
                                        @else
                                            <i class="fa-solid fa-file-lines text-lg"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-gray-900 dark:text-white truncate" title="{{ $material->title }}">
                                            {{ $material->title }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                            {{ $material->course?->code }} · {{ __('Uploaded') }} {{ $material->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    @if ($material->published)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                                            {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                                            {{ __('Pending') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- Right Column: Sidebar Widgets -->
        <div class="space-y-6">
            
            <!-- Quick Links -->
            <x-college.quick-links :title="__('Quick Actions')">
                <a href="{{ route('teacher.courses') }}" wire:navigate class="dashboard-quick-link w-full justify-between">
                    <span class="flex items-center gap-3">
                        <i class="fa-solid fa-book text-indigo-500 dark:text-indigo-400"></i>
                        {{ __('Courses') }}
                    </span>
                    <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
                </a>
                <a href="{{ route('teacher.students') }}" wire:navigate class="dashboard-quick-link w-full justify-between">
                    <span class="flex items-center gap-3">
                        <i class="fa-solid fa-users text-indigo-500 dark:text-indigo-400"></i>
                        {{ __('Students') }}
                    </span>
                    <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
                </a>
                <a href="{{ route('teacher.timetable') }}" wire:navigate class="dashboard-quick-link w-full justify-between">
                    <span class="flex items-center gap-3">
                        <i class="fa-solid fa-calendar-days text-indigo-500 dark:text-indigo-400"></i>
                        {{ __('Timetable') }}
                    </span>
                    <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
                </a>
            </x-college.quick-links>

            <!-- Recent Memos Widget -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <i class="fa-solid fa-envelope-open-text text-indigo-500"></i>
                        {{ __('Recent Memos') }}
                    </h3>
                    <a href="{{ route('teacher.memos.index') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        {{ __('View All') }} &rarr;
                    </a>
                </div>

                @if ($recentMemos->isEmpty())
                    <x-college.empty-state 
                        :title="__('No memos')" 
                        :description="__('There are no official memos or circulars available at this time.')"
                    >
                        <x-slot name="icon">
                            <i class="fa-solid fa-inbox text-gray-400 text-3xl"></i>
                        </x-slot>
                    </x-college.empty-state>
                @else
                    <div class="space-y-4">
                        @foreach ($recentMemos as $memo)
                            @php
                                $receipt = $memo->readReceipts->where('user_id', auth()->id())->first();
                                $isUnread = $receipt && $receipt->acknowledged_at === null;
                            @endphp
                            <a href="{{ route('teacher.memos.show', $memo->id) }}" wire:navigate class="relative block rounded-lg border border-gray-150 p-4 transition-all hover:border-indigo-500 hover:shadow-sm dark:border-gray-700 dark:hover:border-indigo-500 bg-gray-50/50 dark:bg-gray-900/30">
                                @if ($isUnread)
                                    <div class="absolute top-3.5 right-3.5 flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </div>
                                @endif
                                <h4 @class([
                                    'font-semibold text-sm text-gray-900 dark:text-white pr-4 line-clamp-1',
                                    'font-bold' => $isUnread,
                                ])>
                                    {{ $memo->title }}
                                </h4>
                                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span class="truncate max-w-[130px] font-medium text-gray-700 dark:text-gray-300">
                                        <i class="fa-solid fa-user-tie text-gray-400 mr-1"></i> {{ $memo->sender_name }}
                                    </span>
                                    <span>
                                        {{ $memo->updated_at->diffForHumans() }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

</div>
