<?php

use App\Models\User;
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public bool $allowSelfRegistration = true;

    public function mount(): void
    {
        $allowReg = \App\Models\Setting::query()
            ->where('setting_key', 'system_preferences.allow_student_self_registration')
            ->value('setting_value');
        if ($allowReg === '0') {
            $this->allowSelfRegistration = false;
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('post.login.redirect', absolute: false), navigate: true);
    }

    /**
     * Log in instantly using a pre-seeded email.
     */
    public function quickLogin(string $email): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            Auth::login($user);
            Session::regenerate();
            $this->redirectIntended(default: route('post.login.redirect', absolute: false), navigate: true);
        }
    }
}; ?>

<div>
    <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">
        {{ __('Login') }}
    </h1>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- Email or username (students: index number once set on the account) -->
        <div>
            <x-input-label for="login" :value="__('Email or username')" />
            <x-text-input wire:model="form.login" id="login" class="mt-1 block w-full" type="text" name="login" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-655 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div>
            <x-college-form-submit target="login" variant="auth" class="w-full justify-center !text-xs">
                {{ __('Log in') }}
            </x-college-form-submit>
        </div>

        <hr class="my-8 border-gray-200 dark:border-gray-600" />

        @if (Route::has('password.request'))
            <p>
                <a class="text-sm font-medium text-purple-600 hover:underline dark:text-purple-400" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            </p>
        @endif

        @if (Route::has('register') && $allowSelfRegistration)
            <p class="mt-1">
                <a class="text-sm font-medium text-purple-600 hover:underline dark:text-purple-400" href="{{ route('register') }}" wire:navigate>
                    {{ __('Create account') }}
                </a>
            </p>
        @endif
    </form>

    @if(config('college.demo_mode') || session('demo_mode'))
        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Quick Demo Login
            </h2>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" wire:click="quickLogin('admin@demo.com')" class="flex flex-col items-center justify-center p-2 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/20 dark:text-purple-300 dark:hover:bg-purple-950/40 text-xs transition">
                    <i class="fa-solid fa-user-shield mb-1 text-base"></i>
                    <span>Admin</span>
                </button>
                <button type="button" wire:click="quickLogin('teacher@demo.com')" class="flex flex-col items-center justify-center p-2 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:text-indigo-300 dark:hover:bg-indigo-950/40 text-xs transition">
                    <i class="fa-solid fa-chalkboard-user mb-1 text-base"></i>
                    <span>Teacher</span>
                </button>
                <button type="button" wire:click="quickLogin('student@demo.com')" class="flex flex-col items-center justify-center p-2 rounded-md bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/20 dark:text-sky-300 dark:hover:bg-sky-950/40 text-xs transition">
                    <i class="fa-solid fa-user-graduate mb-1 text-base"></i>
                    <span>Student</span>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-gray-500">{{ __('All data resets automatically.') }}</p>
                <x-college.demo-reset-button />
            </div>
        </div>
    @endif
</div>

