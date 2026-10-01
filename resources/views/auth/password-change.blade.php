<x-guest-layout>
    @php($me = auth()->user())
    <div>
        <h1 class="mb-1 text-xl font-semibold text-gray-700 dark:text-gray-200">
            {{ __('Change password') }}
        </h1>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Pick a new secret to unlock your account.') }}
        </p>

        @if ($me)
            <div class="mb-4 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr((string) ($me->name ?: $me->username), 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $me->name ?: $me->username }}
                    </p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Signed in as :username', ['username' => '@'.$me->username]) }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">
                        {{ __('Not you? Log out') }}
                    </button>
                </form>
            </div>
        @endif

        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
            <i class="fa-solid fa-lock mr-1.5" aria-hidden="true"></i>{{ __('Your account requires a new password before you can continue.') }}
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="current_password" :value="__('Current Password')" />
                <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" autofocus />
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('New Password')" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div>
                <x-college-form-submit variant="auth" class="w-full justify-center !text-xs">{{ __('Change password') }}</x-college-form-submit>
            </div>
        </form>
    </div>
</x-guest-layout>
