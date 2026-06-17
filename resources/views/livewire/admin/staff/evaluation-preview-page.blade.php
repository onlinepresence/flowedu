<div class="mx-auto max-w-3xl space-y-6">
    <x-college.page-header
        :title="__('Preview: :title', ['title' => $form->title])"
        :description="__('This is a read-only preview of the questionnaire. Students will see it in this format.')"
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.evaluation', ['form_code' => $form_code]) }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
            >
                ← {{ __('Back to manage') }}
            </a>
        </x-slot:actions>
    </x-college.page-header>

    <x-card :title="__('Questionnaire Preview')">
        <div class="divide-y divide-gray-150 dark:divide-gray-700 mt-2">
            @forelse ($questions as $q)
                <div class="py-5 last:pb-0" wire:key="pv-{{ $q->id }}">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $q->question_order }}. {{ $q->question_text }}
                        </p>
                        @if ($q->is_required)
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                                {{ __('Required') }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-3">
                        @if (in_array($q->rating_type, ['scale_5', 'scale_10'], true))
                            @php $max = $q->rating_type === 'scale_5' ? 5 : 10; @endphp
                            <div class="flex flex-wrap items-center gap-2">
                                @for ($i = 1; $i <= $max; $i++)
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-300 bg-white text-xs font-bold text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        {{ $i }}
                                    </span>
                                @endfor
                                <span class="text-xs text-gray-400 dark:text-gray-500 ml-1 font-semibold">({{ __('1 = Poor, :max = Excellent', ['max' => $max]) }})</span>
                            </div>
                        @elseif ($q->rating_type === 'boolean')
                            <div class="flex gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" disabled class="text-purple-600 border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                                    <span>{{ __('Yes') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" disabled class="text-purple-600 border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                                    <span>{{ __('No') }}</span>
                                </label>
                            </div>
                        @elseif (in_array($q->rating_type, ['select_single', 'select_multiple'], true) && is_array($q->options_json) && $q->options_json !== [])
                            <div class="space-y-2">
                                @foreach ($q->options_json as $opt)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input
                                            type="{{ $q->rating_type === 'select_single' ? 'radio' : 'checkbox' }}"
                                            disabled
                                            class="text-purple-600 border-gray-300 dark:border-gray-600 dark:bg-gray-800 {{ $q->rating_type === 'select_single' ? '' : 'rounded' }}"
                                        />
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif ($q->rating_type === 'text_long')
                            <textarea disabled class="block w-full rounded-md border-gray-300 bg-gray-50/50 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800/40 dark:text-gray-400" rows="3" placeholder="{{ __('Placeholder for student feedback text...') }}"></textarea>
                        @else
                            <input type="text" disabled class="block w-full rounded-md border-gray-300 bg-gray-50/50 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800/40 dark:text-gray-400" placeholder="{{ __('Placeholder for short response...') }}" />
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6 font-semibold">{{ __('No questions on this evaluation form.') }}</p>
            @endforelse
        </div>
    </x-card>
</div>
