<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        $loginRules = ['required', 'string', 'max:255'];
        if (Str::contains(trim($this->login), '@')) {
            $loginRules[] = 'email';
        }

        return [
            'login' => $loginRules,
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = trim($this->login);
        $user = $this->resolveUserByIdentifier($identifier);

        if ($user === null || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'form.login' => __('Your account has been deactivated.'),
            ]);
        }

        Auth::login($user, $this->remember);

        RateLimiter::clear($this->throttleKey());
    }

    protected function resolveUserByIdentifier(string $identifier): ?User
    {
        $looksLikeEmail = Str::contains($identifier, '@')
            && filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        if ($looksLikeEmail) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
                ->first();
        }

        return User::query()->where('username', $identifier)->first();
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        $identifier = Str::lower(trim($this->login));

        return Str::transliterate($identifier.'|'.request()->ip());
    }
}
