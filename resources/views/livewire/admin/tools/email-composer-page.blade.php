<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form wire:submit="send" class="space-y-5">
            <div>
                <div class="flex items-baseline justify-between">
                    <x-input-label for="email-to" :value="__('Recipients')" />
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ __(':count recipient(s)', ['count' => $this->recipientCount]) }}
                    </span>
                </div>
                <textarea
                    wire:model.live="recipients"
                    id="email-to"
                    rows="3"
                    placeholder="{{ __('one@example.com, two@example.com — one per line, comma or semicolon separated') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"
                ></textarea>
                <x-input-error :messages="$errors->get('recipients')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email-subject" :value="__('Subject')" />
                <x-text-input wire:model="subject" id="email-subject" type="text" class="mt-1 block w-full" maxlength="255" />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <div class="flex items-baseline justify-between">
                    <x-input-label for="email-body" :value="__('Message (HTML)')" />
                    <button
                        type="button"
                        wire:click="$toggle('showPreview')"
                        class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400"
                    >
                        {{ $showPreview ? __('Hide preview') : __('Show preview') }}
                    </button>
                </div>
                <textarea
                    wire:model.live="htmlBody"
                    id="email-body"
                    rows="8"
                    spellcheck="false"
                    placeholder="{{ __('Write HTML here — e.g. <p>Hello <strong>…') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono"
                ></textarea>
                <x-input-error :messages="$errors->get('htmlBody')" class="mt-2" />
            </div>

            @if($showPreview)
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Preview') }}</p>
                    <div class="rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                        {!! $htmlBody !== '' ? $htmlBody : '<span class="text-gray-400">'.__('Nothing to preview yet.').'</span>' !!}
                    </div>
                </div>
            @endif

            <div>
                <x-college-form-submit target="send" class="w-full justify-center">
                    {{ __('Queue emails') }}
                </x-college-form-submit>
                <p class="mt-2 text-center text-xs text-gray-400 dark:text-gray-500">
                    {{ __('Sending is queued — delivery follows within a minute via the queue worker.') }}
                </p>
            </div>
        </form>
    </div>
</div>
