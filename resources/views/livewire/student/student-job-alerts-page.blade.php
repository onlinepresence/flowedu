<div class="mx-auto max-w-4xl space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                {{ __('Job Board & Campus Activities') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Browse active job opportunities, internships, and student activities.') }}
            </p>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-card class="p-4 no-print">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fa-solid fa-search text-gray-400 text-xs"></i>
                </div>
                <x-text-input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('Search by title, organizer…') }}"
                    class="block w-full pl-9 text-sm"
                />
            </div>
            <div>
                <select wire:model.live="typeFilter" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="job">{{ __('Jobs Only') }}</option>
                    <option value="activity">{{ __('Activities Only') }}</option>
                </select>
            </div>
        </div>
    </x-card>

    {{-- Active Board Section --}}
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ __('Active Opportunities') }}
            <span class="ml-1 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-2 py-0.5 rounded-full">
                {{ $activeItems->count() }}
            </span>
        </h2>

        @if ($activeItems->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($activeItems as $item)
                    <x-card wire:key="active-job-{{ $item->id }}" class="flex flex-col justify-between border border-slate-200 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-900 transition-all shadow-sm">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $item->title }}">
                                        {{ $item->title }}
                                    </h3>
                                    @if ($item->company_or_organizer)
                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ $item->company_or_organizer }}
                                        </p>
                                    @endif
                                </div>
                                <span class="shrink-0">
                                    @if ($item->type === 'job')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            {{ __('Job') }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                                            {{ __('Activity') }}
                                        </span>
                                    @endif
                                </span>
                            </div>

                            <div class="prose prose-sm dark:prose-invert text-xs text-slate-600 dark:text-slate-350 line-clamp-3">
                                {!! $item->description !!}
                            </div>

                            @if ($item->requirements)
                                <div class="bg-slate-50 dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/80">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">{{ __('Requirements') }}</span>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">{{ $item->requirements }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-450">
                            <span class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-semibold">
                                <i class="fa-regular fa-calendar-xmark"></i>
                                {{ __('Closes') }}: {{ $item->expiry_date->format('M d, Y') }}
                            </span>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <x-college.empty-state
                title="{{ __('No Active Opportunities') }}"
                description="{{ __('There are currently no open positions or active events matching your query.') }}"
            >
                <x-slot name="icon">
                    <i class="fa-solid fa-briefcase text-2xl text-slate-400"></i>
                </x-slot>
            </x-college.empty-state>
        @endif
    </div>

    {{-- Expired Board Section --}}
    <div class="space-y-4 pt-4">
        <h2 class="text-lg font-bold text-slate-400 dark:text-slate-500 flex items-center gap-2">
            <span class="flex h-2.5 w-2.5 rounded-full bg-red-400"></span>
            {{ __('Recently Closed') }}
            <span class="ml-1 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-2 py-0.5 rounded-full">
                {{ $expiredItems->count() }}
            </span>
        </h2>

        @if ($expiredItems->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($expiredItems as $item)
                    <x-card wire:key="expired-job-{{ $item->id }}" class="flex flex-col justify-between border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/10 opacity-70">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-500 dark:text-slate-400 line-clamp-1">
                                        {{ $item->title }}
                                    </h3>
                                    @php
                                        $closedDays = now()->diffInDays($item->expiry_date);
                                    @endphp
                                    <p class="text-xs text-red-500 font-semibold mt-0.5">
                                        {{ __('Closed :days days ago', ['days' => $closedDays]) }}
                                    </p>
                                </div>
                                <span class="shrink-0">
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-950/20 dark:text-red-450 border border-red-100/50">
                                        {{ __('Closed') }}
                                    </span>
                                </span>
                            </div>

                            <div class="prose prose-sm dark:prose-invert text-xs text-slate-450 line-clamp-2">
                                {!! $item->description !!}
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-450">
                            <span>{{ __('Ended on') }}: {{ $item->expiry_date->format('M d, Y') }}</span>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</div>
