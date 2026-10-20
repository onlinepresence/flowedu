<div class="mx-auto w-full max-w-3xl space-y-6"
    x-data="{
        show: false,
        count(value) {
            const seen = new Set();
            for (const part of String(value ?? '').split(/[\s,;]+/)) {
                const email = part.trim();
                if (email !== '' && ! seen.has(email)) {
                    seen.add(email);
                }
            }
            return seen.size;
        }
    }">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form wire:submit="send" class="space-y-5">
            <div>
                <div class="flex items-baseline justify-between">
                    <x-input-label for="email-to" :value="__('Recipients')" />
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        <span x-text="count($wire.recipients)"></span> {{ __('recipient(s)') }}
                    </span>
                </div>
                <textarea
                    wire:model.live.debounce.400ms="recipients"
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
                        @click="show = ! show"
                        class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400"
                    >
                        <span x-show="! show">{{ __('Show preview') }}</span>
                        <span x-show="show" x-cloak>{{ __('Hide preview') }}</span>
                    </button>
                </div>
                <textarea
                    wire:model.live.debounce.400ms="htmlBody"
                    id="email-body"
                    rows="8"
                    spellcheck="false"
                    placeholder="{{ __('Write HTML here — e.g. <p>Hello <strong>…') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono"
                ></textarea>
                <x-input-error :messages="$errors->get('htmlBody')" class="mt-2" />
            </div>

            <div x-show="show" x-cloak>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Preview') }}</p>
                <div class="rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                    <template x-if="$wire.htmlBody.trim() !== ''"><div x-html="$wire.htmlBody"></div></template>
                    <template x-if="$wire.htmlBody.trim() === ''"><span class="text-gray-400">{{ __('Nothing to preview yet.') }}</span></template>
                </div>
            </div>

            @if ($deliveryFailures !== [])
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200" role="alert">
                    <p class="font-semibold">{{ __('Some emails could not be sent.') }}</p>
                    <ul class="mt-2 space-y-2">
                        @foreach ($deliveryFailures as $email => $message)
                            <li>
                                <span class="font-semibold">{{ $email }}</span>
                                <span class="mt-0.5 block break-words font-mono text-xs">{{ $message }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-input-error :messages="$errors->get('delivery')" />

            <div>
                <x-college-form-submit target="send" class="w-full justify-center">
                    {{ __('Send emails') }}
                </x-college-form-submit>
                <p class="mt-2 text-center text-xs text-gray-400 dark:text-gray-500">
                    {{ __('Emails are sent immediately. Any delivery errors will be shown here.') }}
                </p>
            </div>
        </form>
    </div>
</div>
