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
        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700" x-data>
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Quick Demo Login
            </h2>
            
            <div class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" x-on:click="$dispatch('open-modal', 'admin-role-modal')" class="flex flex-col items-center justify-center p-2 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/20 dark:text-purple-300 dark:hover:bg-purple-950/40 text-xs transition">
                        <i class="fa-solid fa-user-shield mb-1 text-base"></i>
                        <span>Admin/Staff</span>
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
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-gray-500">{{ __('All data resets automatically.') }}</p>
                <x-college.demo-reset-button />
            </div>

            <!-- Admin & Staff Roles Popup Modal -->
            <x-college.modal name="admin-role-modal" :title="__('Choose an Administrative or Staff Role')" maxWidth="4xl">
                <div class="space-y-6">
                    <!-- Section 1: Administrative Staff -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 border-b border-gray-100 dark:border-gray-700 pb-1">
                            {{ __('Administrative Staff') }}
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <button type="button" wire:click="quickLogin('admin@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-user-gear text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('Owner (Principal/CEO)') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">admin@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('sysadmin@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-laptop-code text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('System Administrator') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">sysadmin@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('principal@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-school text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('College Principal') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">principal@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('viceprincipal@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-users-gear text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('Vice Principal') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">viceprincipal@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('hod@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-sitemap text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('Head of Dept (HOD)') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">hod@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('dean@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ __('Dean of Student Affairs') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">dean@demo.com</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Section 2: Other Staff / Operations -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 border-b border-gray-100 dark:border-gray-700 pb-1">
                            {{ __('Support & Operations Staff') }}
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <button type="button" wire:click="quickLogin('registrar@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-address-book text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Academic Registrar') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">registrar@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('finance@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-money-check-dollar text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Finance Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">finance@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('accountant@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-calculator text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Accountant') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">accountant@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('librarian@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-book-open text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('College Librarian') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">librarian@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('auditor@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-magnifying-glass-chart text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Internal Auditor') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">auditor@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('secretary@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-keyboard text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Department Secretary') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">secretary@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('admissions@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-user-plus text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Admissions Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">admissions@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('exams@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-file-signature text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Examinations Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">exams@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('qa@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-shield-halved text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Quality Assurance Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">qa@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('hr@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-users-viewfinder text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('HR Manager') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">hr@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('pro@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-bullhorn text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Public Relations Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">pro@demo.com</div>
                                </div>
                            </button>

                            <button type="button" wire:click="quickLogin('procurement@demo.com')" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition text-left group">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                                    <i class="fa-solid fa-box-open text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ __('Procurement Officer') }}</div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">procurement@demo.com</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <x-slot name="footer">
                    <button type="button" x-on:click="show = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                        {{ __('Close') }}
                    </button>
                </x-slot>
            </x-college.modal>
        </div>
    @endif
</div>

