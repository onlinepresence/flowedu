<div class="mx-auto max-w-4xl space-y-6">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-gavel text-purple-600 dark:text-purple-400"></i>
                {{ __('Discipline & Conduct Registry') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Official record of your student conduct status and history.') }}
            </p>
        </div>
    </div>

    {{-- ── INTRO ADVISORY BANNER ──────────────────────────────────────────── --}}
    <div class="flex items-start gap-4 rounded-2xl border border-rose-200 bg-rose-50/60 p-5 dark:border-rose-900/40 dark:bg-rose-950/20">
        <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
            <i class="fa-solid fa-scale-balanced text-lg"></i>
        </div>
        <div class="space-y-0.5">
            <h1 class="text-base font-extrabold tracking-tight text-rose-900 dark:text-rose-300">
                {{ __('Disciplinary File — Confidential') }}
            </h1>
            <p class="text-xs text-rose-700 dark:text-rose-400 leading-relaxed">
                {{ __('This record is maintained by the Institution\'s Disciplinary Committee. Contents are strictly confidential and should not be shared. Contact the Dean of Students for any disputes or corrections.') }}
            </p>
        </div>
    </div>

    @if ($rows->isNotEmpty())

        {{-- ── SUMMARY STAT CHIPS ──────────────────────────────────────────────── --}}
        @php
            $total    = $rows->count();
            $cleared  = $rows->filter(fn($r) => $r->return_status)->count();
            $pending  = $rows->filter(fn($r) => !$r->return_status && $r->return_date)->count();
        @endphp

        <div class="grid grid-cols-3 gap-4">
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    <i class="fa-solid fa-folder-open"></i>
                </span>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ __('Total Incidents') }}</p>
                    <p class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ $total }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm dark:border-amber-900/30 dark:bg-amber-950/20">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                    <i class="fa-solid fa-hourglass-half"></i>
                </span>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400">{{ __('Pending Return') }}</p>
                    <p class="text-xl font-black text-amber-700 dark:text-amber-300 leading-none">{{ $pending }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-900/30 dark:bg-emerald-950/20">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="fa-solid fa-circle-check"></i>
                </span>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ __('Cleared') }}</p>
                    <p class="text-xl font-black text-emerald-700 dark:text-emerald-300 leading-none">{{ $cleared }}</p>
                </div>
            </div>
        </div>

        {{-- ── TIMELINE ─────────────────────────────────────────────────────── --}}
        <div class="flow-root">
            <ul class="-mb-8">
                @foreach ($rows as $index => $row)
                    @php
                        // Derive severity color from action_taken text
                        $action = strtolower($row->action_taken ?? '');

                        if (str_contains($action, 'expel') || str_contains($action, 'dismiss') || str_contains($action, 'expulsion')) {
                            $dotColor    = 'bg-rose-500';
                            $dotRing     = 'ring-rose-100 dark:ring-rose-950/80';
                            $dotIcon     = 'fa-ban';
                            $badgeBg     = 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-400';
                        } elseif (str_contains($action, 'suspend') || str_contains($action, 'suspension')) {
                            $dotColor    = 'bg-orange-500';
                            $dotRing     = 'ring-orange-100 dark:ring-orange-950/80';
                            $dotIcon     = 'fa-circle-pause';
                            $badgeBg     = 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20 dark:bg-orange-950/40 dark:text-orange-400';
                        } elseif (str_contains($action, 'probation')) {
                            $dotColor    = 'bg-amber-500';
                            $dotRing     = 'ring-amber-100 dark:ring-amber-950/80';
                            $dotIcon     = 'fa-triangle-exclamation';
                            $badgeBg     = 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400';
                        } elseif (str_contains($action, 'warn') || str_contains($action, 'caution')) {
                            $dotColor    = 'bg-yellow-500';
                            $dotRing     = 'ring-yellow-100 dark:ring-yellow-950/80';
                            $dotIcon     = 'fa-exclamation';
                            $badgeBg     = 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20 dark:bg-yellow-950/40 dark:text-yellow-500';
                        } elseif (str_contains($action, 'fine') || str_contains($action, 'penalt')) {
                            $dotColor    = 'bg-violet-500';
                            $dotRing     = 'ring-violet-100 dark:ring-violet-950/80';
                            $dotIcon     = 'fa-coins';
                            $badgeBg     = 'bg-violet-50 text-violet-700 ring-1 ring-violet-600/20 dark:bg-violet-950/40 dark:text-violet-400';
                        } else {
                            $dotColor    = 'bg-slate-400';
                            $dotRing     = 'ring-slate-100 dark:ring-slate-800';
                            $dotIcon     = 'fa-gavel';
                            $badgeBg     = 'bg-slate-100 text-slate-600 ring-1 ring-slate-400/20 dark:bg-slate-800 dark:text-slate-400';
                        }
                    @endphp

                    <li wire:key="disc-{{ $row->id }}">
                        <div class="relative pb-8">
                            {{-- Connecting spine line (hidden for last item) --}}
                            @if (!$loop->last)
                                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                            @endif

                            <div class="relative flex gap-4">
                                {{-- Timeline Dot --}}
                                <div class="shrink-0">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $dotColor }} ring-8 {{ $dotRing }} text-white text-xs shadow-sm">
                                        <i class="fa-solid {{ $dotIcon }}"></i>
                                    </span>
                                </div>

                                {{-- Record Card --}}
                                <div class="flex-1 min-w-0">
                                    <x-card class="overflow-hidden rounded-xl border border-slate-200 shadow-sm dark:border-slate-700/80">
                                        {{-- Card Header --}}
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 p-4">
                                            <div class="flex items-center gap-3">
                                                {{-- Action Badge --}}
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeBg }}">
                                                    <i class="fa-solid {{ $dotIcon }} text-[9px]"></i>
                                                    {{ $row->action_taken }}
                                                </span>
                                                {{-- Program Badge --}}
                                                @if ($row->program)
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                        {{ $row->program->name }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Date Chip --}}
                                            <span class="shrink-0 rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1 font-mono text-[11px] font-semibold text-slate-500 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400">
                                                <i class="fa-regular fa-calendar mr-1"></i>
                                                {{ $row->date_of_action?->format('M d, Y') ?? '—' }}
                                            </span>
                                        </div>

                                        {{-- Offense Description --}}
                                        <div class="p-4 space-y-4">
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">{{ __('Offense / Incident') }}</p>
                                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-relaxed">
                                                    {{ $row->offense }}
                                                </p>
                                            </div>

                                            {{-- Comments (if any) --}}
                                            @if ($row->comments)
                                                <div class="rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">{{ __('Committee Remarks') }}</p>
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ $row->comments }}</p>
                                                </div>
                                            @endif

                                            {{-- Return / Status row --}}
                                            @if ($row->return_date || $row->return_status)
                                                <div class="flex flex-wrap items-center gap-3 pt-1 border-t border-slate-100 dark:border-slate-800">
                                                    @if ($row->return_date)
                                                        <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                                            <i class="fa-regular fa-calendar-check text-slate-400"></i>
                                                            <span>{{ __('Expected Return') }}:</span>
                                                            <span class="font-bold font-mono text-slate-700 dark:text-slate-300">{{ $row->return_date->format('M d, Y') }}</span>
                                                        </div>
                                                    @endif

                                                    @if ($row->return_status)
                                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400">
                                                            <i class="fa-solid fa-circle-check text-[9px]"></i>
                                                            {{ __('Cleared / Returned') }}
                                                        </span>
                                                    @elseif ($row->return_date)
                                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400">
                                                            <i class="fa-solid fa-hourglass-half text-[9px]"></i>
                                                            {{ __('Pending Return') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </x-card>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

    @else

        {{-- ── EMPTY STATE ─────────────────────────────────────────────────── --}}
        <x-college.empty-state
            title="{{ __('No Disciplinary Incidents on File') }}"
            description="{{ __('Your disciplinary record is clean. Keep up the good conduct throughout your academic journey.') }}"
        >
            <x-slot name="icon">
                <i class="fa-solid fa-shield-halved text-3xl text-emerald-500"></i>
            </x-slot>
        </x-college.empty-state>

    @endif

</div>
