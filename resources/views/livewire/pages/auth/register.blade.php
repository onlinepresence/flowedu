<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $system_secret = '';

    public function mount(): void
    {
        $adminBootstrap = (bool) session('admin_register', false);
        if (!$adminBootstrap) {
            $allowReg = \App\Models\Setting::query()
                ->where('setting_key', 'system_preferences.allow_student_self_registration')
                ->value('setting_value');
            if ($allowReg === '0') {
                abort(403, __('Student self-registration is disabled.'));
            }
        }
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $adminBootstrap = (bool) session('admin_register', false);

        $rules = [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        if ($adminBootstrap) {
            $rules['system_secret'] = ['required', 'string'];
        }

        $validated = $this->validate($rules);

        if ($adminBootstrap) {
            $expected = (string) config('college.system_registration_secret');
            if ($expected !== $validated['system_secret']) {
                $this->addError('system_secret', __('The system secret provided is not valid.'));

                return;
            }
        }

        $type = $adminBootstrap ? 'admin' : 'student';

        $user = User::create([
            'name' => null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'type' => $type,
            'user_secret' => Str::random(64),
            'active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $target = $type === 'admin'
            ? route('admin.setup.personal', absolute: false)
            : route('student.setup.personal', absolute: false);

        $this->redirect($target, navigate: true);
    }
}; ?>

<div>
    <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">
        {{ __('Create account') }}
    </h1>

    <form wire:submit="register" class="space-y-4">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="mt-1 block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        @if (session('admin_register'))
            <div>
                <x-input-label for="system_secret" :value="__('System secret')" />
                <x-text-input wire:model="system_secret" id="system_secret" class="mt-1 block w-full"
                    type="password" name="system_secret" required autocomplete="off" />
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('The secret provided for first-time system setup.') }}</p>
                <x-input-error :messages="$errors->get('system_secret')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-college-form-submit target="register" variant="auth" class="w-full justify-center !text-xs">
                @if (session('admin_register'))
                    {{ __('Setup Admin Account') }}
                @else
                    {{ __('Register') }}
                @endif
            </x-college-form-submit>
        </div>

        @unless (session('admin_register'))
            <p class="mt-4">
                <a class="text-sm font-medium text-purple-600 hover:underline dark:text-purple-400" href="{{ route('login') }}" wire:navigate>
                    {{ __('Already registered?') }}
                </a>
            </p>
        @endunless
    </form>
</div>
