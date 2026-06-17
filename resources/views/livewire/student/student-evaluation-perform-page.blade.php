<div class="space-y-6">
    <!-- Header Summary Info -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                <i class="fa-solid fa-hashtag text-sm"></i>
            </span>
            <div class="space-y-0.5">
                <span class="text-3xs font-extrabold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ __('Evaluation Code') }}
                </span>
                <p class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400 leading-none">
                    {{ $form->unique_code }}
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 font-semibold bg-gray-50 dark:bg-gray-900 px-3.5 py-1.5 rounded-lg border border-gray-150 dark:border-gray-750">
            <i class="fa-regular fa-clock text-purple-500"></i>
            <span>{{ __('Due:') }} {{ $form->end_time?->timezone(config('app.timezone'))->format('M d, Y H:i') }}</span>
        </div>
    </div>

    <!-- Evaluating Lecturer Banner -->
    @if ($teacher)
        <div class="flex items-center gap-4 bg-gradient-to-r from-purple-500/10 to-indigo-500/10 dark:from-purple-950/20 dark:to-indigo-950/20 p-5 rounded-xl border border-purple-100 dark:border-purple-900/30">
            @if ($teacher->profile_pic && \Illuminate\Support\Facades\Storage::disk('public')->exists($teacher->profile_pic))
                <img src="{{ asset('storage/' . $teacher->profile_pic) }}" alt="{{ $teacher->user->name }}" class="h-14 w-14 rounded-full object-cover border-2 border-white dark:border-gray-800 shadow-sm">
            @else
                @php
                    $initials = '';
                    if (!empty($teacher->firstname) || !empty($teacher->lastname)) {
                        $initials .= substr($teacher->firstname ?? '', 0, 1);
                        $initials .= substr($teacher->lastname ?? '', 0, 1);
                    } else {
                        $nameParts = explode(' ', $teacher->user->name ?? '');
                        $initials .= substr($nameParts[0] ?? '', 0, 1);
                        if (isset($nameParts[1])) {
                            $initials .= substr($nameParts[1], 0, 1);
                        }
                    }
                    $initials = strtoupper(substr($initials, 0, 2));
                @endphp
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-purple-200 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-2 border-white dark:border-gray-800 font-bold text-base shadow-sm shrink-0">
                    {{ $initials }}
                </div>
            @endif
            <div class="space-y-0.5">
                <span class="text-3xs font-extrabold uppercase tracking-widest text-purple-700 dark:text-purple-400">
                    {{ __('Evaluating Lecturer') }}
                </span>
                <h3 class="text-base font-extrabold text-gray-900 dark:text-white leading-tight">
                    {{ $teacher->user->name }}
                </h3>
                @if ($teacher->department)
                    <p class="text-2xs text-gray-500 dark:text-gray-400 font-medium">
                        {{ $teacher->department->name }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <!-- Form Questionnaire -->
    <form wire:submit.prevent="submit" class="space-y-6">
        @foreach ($questions as $q)
            @php
                $hasError = $errors->has('answers.'.$q->id);
            @endphp
            <div 
                @class([
                    'rounded-xl border bg-white p-5 dark:bg-gray-800 shadow-sm space-y-3 transition',
                    'border-red-300 dark:border-red-900/50 bg-red-50/5' => $hasError,
                    'border-gray-200 dark:border-gray-700' => !$hasError
                ]) 
                wire:key="ans-{{ $q->id }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold text-gray-900 dark:text-white leading-normal">
                        {{ $q->question_text }}
                        @if ($q->is_required)
                            <span class="text-red-500 font-bold ml-0.5">*</span>
                        @endif
                    </p>
                </div>

                @error('answers.'.$q->id)
                    <p class="text-3xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ $message }}
                    </p>
                @enderror

                <div>
                    @switch($q->rating_type)
                        @case('scale_5')
                            <!-- Circular Clickable Numbers 1 to 5 -->
                            <div class="flex items-center gap-2 mt-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button 
                                        type="button"
                                        wire:click="$set('answers.{{ $q->id }}', '{{ $i }}')"
                                        @class([
                                            'h-10 w-10 rounded-full flex items-center justify-center text-xs font-bold transition border focus:outline-none',
                                            'bg-purple-600 border-purple-600 text-white shadow-sm' => ($answers[$q->id] ?? '') == (string)$i,
                                            'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-850' => ($answers[$q->id] ?? '') != (string)$i
                                        ])
                                    >
                                        {{ $i }}
                                    </button>
                                @endfor
                            </div>
                            @break

                        @case('scale_10')
                            <!-- Circular Clickable Numbers 1 to 10 -->
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                @for ($i = 1; $i <= 10; $i++)
                                    <button 
                                        type="button"
                                        wire:click="$set('answers.{{ $q->id }}', '{{ $i }}')"
                                        @class([
                                            'h-9 w-9 rounded-full flex items-center justify-center text-2xs font-bold transition border focus:outline-none',
                                            'bg-purple-600 border-purple-600 text-white shadow-sm' => ($answers[$q->id] ?? '') == (string)$i,
                                            'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-855' => ($answers[$q->id] ?? '') != (string)$i
                                        ])
                                    >
                                        {{ $i }}
                                    </button>
                                @endfor
                            </div>
                            @break

                        @case('boolean')
                            <!-- Segmented Switch Yes / No -->
                            <div class="flex items-center gap-2 mt-1">
                                <button 
                                    type="button"
                                    wire:click="$set('answers.{{ $q->id }}', '1')"
                                    @class([
                                        'px-4 py-2.5 rounded-lg text-xs font-bold transition border focus:outline-none flex items-center justify-center gap-1.5 w-24',
                                        'bg-purple-600 border-purple-600 text-white shadow-sm' => ($answers[$q->id] ?? '') === '1',
                                        'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-850' => ($answers[$q->id] ?? '') !== '1'
                                    ])
                                >
                                    <i class="fa-solid fa-thumbs-up text-[10px]"></i>
                                    {{ __('Yes') }}
                                </button>
                                <button 
                                    type="button"
                                    wire:click="$set('answers.{{ $q->id }}', '0')"
                                    @class([
                                        'px-4 py-2.5 rounded-lg text-xs font-bold transition border focus:outline-none flex items-center justify-center gap-1.5 w-24',
                                        'bg-purple-600 border-purple-600 text-white shadow-sm' => ($answers[$q->id] ?? '') === '0',
                                        'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-850' => ($answers[$q->id] ?? '') !== '0'
                                    ])
                                >
                                    <i class="fa-solid fa-thumbs-down text-[10px]"></i>
                                    {{ __('No') }}
                                </button>
                            </div>
                            @break

                        @case('select_single')
                            <!-- Styled Pills for Select Single -->
                            @php $opts = is_array($q->options_json) ? $q->options_json : []; @endphp
                            @if ($opts === [])
                                <p class="text-2xs text-gray-400 dark:text-gray-500 italic">{{ __('No options configured.') }}</p>
                            @else
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @foreach ($opts as $opt)
                                        <button 
                                            type="button"
                                            wire:click="$set('answers.{{ $q->id }}', '{{ $opt }}')"
                                            @class([
                                                'px-3.5 py-2 rounded-lg text-2xs font-bold transition border focus:outline-none',
                                                'bg-purple-600 border-purple-600 text-white shadow-sm' => ($answers[$q->id] ?? '') === $opt,
                                                'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-850' => ($answers[$q->id] ?? '') !== $opt
                                            ])
                                        >
                                            {{ $opt }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @break

                        @case('select_multiple')
                            <!-- Styled Option Grids for Checkboxes -->
                            @php $opts = is_array($q->options_json) ? $q->options_json : []; @endphp
                            @if ($opts === [])
                                <p class="text-2xs text-gray-400 dark:text-gray-500 italic">{{ __('No options configured.') }}</p>
                            @else
                                <div class="grid gap-2 sm:grid-cols-2 mt-1">
                                    @foreach ($opts as $opt)
                                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/10 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-900/30 cursor-pointer transition">
                                            <input 
                                                type="checkbox" 
                                                value="{{ $opt }}" 
                                                wire:model.live="answers.{{ $q->id }}" 
                                                class="rounded text-purple-600 focus:ring-purple-500 border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                            />
                                            <span class="text-2xs font-semibold text-gray-700 dark:text-gray-300">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @break

                        @case('text_long')
                            <textarea 
                                wire:model.live="answers.{{ $q->id }}" 
                                rows="3" 
                                placeholder="{{ __('Type your feedback here…') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-750 dark:bg-gray-900 dark:text-white text-xs focus:border-purple-500 focus:ring-purple-500 shadow-sm"
                            ></textarea>
                            @break

                        @default
                            <input 
                                wire:model.live="answers.{{ $q->id }}" 
                                type="text" 
                                placeholder="{{ __('Type response…') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-750 dark:bg-gray-900 dark:text-white text-xs focus:border-purple-500 focus:ring-purple-500 shadow-sm"
                            />
                    @endswitch
                </div>
            </div>
        @endforeach

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <button
                type="button"
                wire:click="saveDraft"
                wire:loading.attr="disabled"
                wire:target="saveDraft"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-bold text-gray-800 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition"
            >
                <i class="fa-regular fa-bookmark"></i>
                <span wire:loading.remove wire:target="saveDraft">{{ __('Save Draft') }}</span>
                <span wire:loading.delay.200ms wire:target="saveDraft" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    {{ __('Saving…') }}
                </span>
            </button>
            
            <x-college-form-submit target="submit" class="rounded-lg px-5 py-2.5 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white transition shadow-sm">
                <i class="fa-solid fa-paper-plane mr-1.5 text-[10px]"></i>
                {{ __('Submit Evaluation') }}
            </x-college-form-submit>
            
            <a 
                href="{{ route('student.evaluation') }}" 
                wire:navigate 
                class="inline-flex items-center justify-center rounded-lg border border-transparent px-4 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition"
            >
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
