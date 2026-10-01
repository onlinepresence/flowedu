{{-- TEMPORARY admin-only SMTP smoke test view (delete with MailTestController once emailing is verified). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mail test (temporary)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Effective mailer: :mailer', ['mailer' => $mailer]) }}
                    @if ($demoMode)
                        — {{ __('demo mode is ON, mail is forced to the log driver and will NOT deliver.') }}
                    @endif
                </p>

                @if (is_array($result))
                    @if ($result['ok'])
                        <p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300" role="status">
                            {{ $result['detail'] }}
                        </p>
                    @else
                        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300" role="alert">
                            {{ __('Sending failed: :error', ['error' => $result['detail']]) }}
                        </p>
                    @endif
                @endif

                <form method="GET" action="{{ route('testing.mail') }}" class="space-y-4">
                    <div>
                        <x-input-label for="mail-to" :value="__('Recipient')" />
                        <x-text-input id="mail-to" name="to" type="email" class="mt-1 block w-full" :value="old('to', $to)" required autocomplete="email" autofocus />
                        <x-input-error :messages="$errors->get('to')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="mail-message" :value="__('Message')" />
                        <textarea id="mail-message" name="message" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">{{ old('message', $message) }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-college-form-submit class="!text-xs">{{ __('Send test email') }}</x-college-form-submit>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
