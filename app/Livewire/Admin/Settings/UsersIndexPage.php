<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\User;
use App\Services\AdminImpersonationService;
use App\Services\SchoolLicenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UsersIndexPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $search = '';


    public ?int $editingUserId = null;

    public ?int $togglingActiveUserId = null;

    public ?int $impersonatingUserId = null;

    public string $editName = '';

    public string $editUsername = '';

    public string $editEmail = '';

    public string $editType = 'student';

    public bool $editActive = true;

    public string $createName = '';

    public string $createUsername = '';

    public string $createEmail = '';

    public string $createType = 'student';

    public bool $createActive = true;

    public string $createPassword = '';

    public string $createPasswordConfirmation = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openImpersonateModal(int $userId): void
    {
        $this->impersonatingUserId = $userId;
        $this->dispatch('open-modal', 'users-confirm-impersonate');
    }

    public function closeImpersonateModal(): void
    {
        $this->impersonatingUserId = null;
        $this->dispatch('close-modal', 'users-confirm-impersonate');
    }

    public function impersonate(int $userId, AdminImpersonationService $service): void
    {
        $this->impersonatingUserId = $userId;
        $this->confirmImpersonate($service);
    }

    public function confirmImpersonate(AdminImpersonationService $service): void
    {
        if ($this->impersonatingUserId === null) {
            return;
        }

        $userId = $this->impersonatingUserId;
        $this->closeImpersonateModal();

        if (session()->has('college_impersonator_id')) {
            $this->addError('impersonate', __('You cannot start impersonation while already impersonating.'));

            return;
        }

        $actor = auth()->user();
        if ($actor === null) {
            abort(403);
        }

        $target = User::query()->findOrFail($userId);

        try {
            $service->start($actor, $target);
        } catch (\Throwable $e) {
            $this->addError('impersonate', __('You cannot impersonate this account.'));

            return;
        }

        $this->redirect(route('post.login.redirect'), navigate: false);
    }

    public function openEditModal(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('updateForUserSettings', $user);

        $this->editingUserId = $user->id;
        $this->editName = (string) ($user->name ?? '');
        $this->editUsername = (string) $user->username;
        $this->editEmail = (string) ($user->email ?? '');
        $this->editType = (string) $user->type;
        $this->editActive = (bool) $user->active;
        $this->resetValidation();
        $this->dispatch('open-modal', 'users-edit');
    }

    #[On('open-create-user')]
    public function openCreateModal(): void
    {
        abort_unless($this->canManageUserSettings(), 403);
        $this->createName = '';
        $this->createUsername = '';
        $this->createEmail = '';
        $this->createType = 'student';
        $this->createActive = true;
        $this->createPassword = '';
        $this->createPasswordConfirmation = '';
        $this->resetValidation();
        $this->dispatch('open-modal', 'users-create');
    }

    public function closeCreateModal(): void
    {
        $this->resetValidation();
        $this->dispatch('close-modal', 'users-create');
    }

    public function saveCreate(): void
    {
        abort_unless($this->canManageUserSettings(), 403);

        $validated = $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createUsername' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'createEmail' => ['nullable', 'string', 'email', 'max:255'],
            'createType' => ['required', 'string', Rule::in(['student', 'teacher', 'admin', 'staff'])],
            'createActive' => ['boolean'],
            'createPassword' => ['required', 'string', 'min:8'],
            'createPasswordConfirmation' => ['required', 'string', 'same:createPassword'],
        ], [
            'createPasswordConfirmation.same' => __('Password confirmation does not match.'),
        ]);

        User::query()->create([
            'name' => $validated['createName'],
            'username' => $validated['createUsername'],
            'email' => $validated['createEmail'] !== '' ? $validated['createEmail'] : null,
            'type' => $validated['createType'],
            'active' => (bool) $validated['createActive'],
            'password' => Hash::make($validated['createPassword']),
        ]);

        $this->collegeToast(__('User created.'));
        $this->closeCreateModal();
        $this->resetPage();
    }

    public function closeEditModal(): void
    {
        $this->editingUserId = null;
        $this->resetValidation();
        $this->dispatch('close-modal', 'users-edit');
    }

    public function saveEdit(): void
    {
        if ($this->editingUserId === null) {
            return;
        }

        $user = User::query()->findOrFail($this->editingUserId);
        Gate::authorize('updateForUserSettings', $user);

        $actor = auth()->user();
        if ($actor !== null && $actor->is($user) && ! $this->editActive) {
            $this->addError('editActive', __('You cannot deactivate your own account.'));

            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editUsername' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'editEmail' => ['nullable', 'string', 'email', 'max:255'],
            'editType' => ['required', 'string', Rule::in(['student', 'teacher', 'admin', 'staff'])],
            'editActive' => ['boolean'],
        ]);

        if ($user->type === 'student' && $validated['editType'] !== 'student') {
            $this->addError('editType', __('Students cannot have their type changed.'));

            return;
        }

        if (! $this->typeChangeAllowed($user, $validated['editType'])) {
            $this->addError('editType', __('This type does not match linked profiles for this account.'));

            return;
        }

        $user->update([
            'name' => $validated['editName'],
            'username' => $validated['editUsername'],
            'email' => $validated['editEmail'] !== '' ? $validated['editEmail'] : null,
            'type' => $validated['editType'],
            'active' => $validated['editActive'],
        ]);

        $this->collegeToast(__('User updated.'));
        $this->closeEditModal();
    }

    public function sendPasswordReset(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('sendPasswordResetForUserSettings', $user);

        // Reset the password directly to Password@1
        $user->password = \Illuminate\Support\Facades\Hash::make('Password@1');
        $user->save();

        $email = $user->email;
        if ($email !== null && trim($email) !== '') {
            // Send notification email (disabled in Demo mode)
            if (! config('college.demo_mode')) {
                dispatch(new \App\Jobs\SendCollegeNotificationMailJob(
                    $email,
                    __('Your Password Has Been Reset'),
                    '<p>' . __('Your password has been reset by an administrator.') . '</p><p>' . __('Your new temporary password is:') . ' <strong>Password@1</strong></p><p>' . __('Please log in and change your password immediately.') . '</p>'
                ));
            }
        }

        $this->collegeToast(__('Password has been reset to Password@1 successfully.'));
    }

    public function openToggleActiveModal(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('toggleActiveForUserSettings', $user);

        $this->togglingActiveUserId = $userId;
        $this->dispatch('open-modal', 'users-toggle-active');
    }

    public function closeToggleActiveModal(): void
    {
        $this->togglingActiveUserId = null;
        $this->dispatch('close-modal', 'users-toggle-active');
    }

    public function confirmToggleActive(): void
    {
        if ($this->togglingActiveUserId === null) {
            return;
        }

        $user = User::query()->findOrFail($this->togglingActiveUserId);
        Gate::authorize('toggleActiveForUserSettings', $user);

        $user->update(['active' => ! $user->active]);

        $this->collegeToast(
            $user->active
                ? __('Account unlocked.')
                : __('Account locked.')
        );
        $this->closeToggleActiveModal();
    }

    public function render(): View
    {
        $q = trim($this->search);

        $users = User::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('username', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('name', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('type')
            ->orderBy('username')
            ->paginate(25);

        return view('livewire.admin.settings.users-index-page', [
            'users' => $users,
            'currentUserId' => auth()->id(),
            'currentUserProfileRoute' => $this->currentUserProfileRoute(),
            'canCreateUsers' => $this->canManageUserSettings(),
        ])->layout('components.layouts.admin', [
            'title' => __('Users'),
            'headerTitle' => __('User Accounts'),
            'headerDescription' => __('Manage system users, login credentials, and account lock statuses.'),
        ]);
    }

    private function typeChangeAllowed(User $user, string $newType): bool
    {
        if ($user->type === $newType) {
            return true;
        }

        $user->loadMissing(['admin', 'teacher', 'student', 'nonTeachingStaff']);

        if ($user->admin !== null && $newType !== 'admin') {
            return false;
        }

        if ($user->teacher !== null && $newType !== 'teacher') {
            return false;
        }

        if ($user->student !== null && $newType !== 'student') {
            return false;
        }

        if ($user->nonTeachingStaff !== null && $newType !== 'staff') {
            return false;
        }

        return true;
    }

    private function canManageUserSettings(): bool
    {
        $actor = auth()->user();
        if ($actor === null || $actor->type !== 'admin') {
            return false;
        }

        return app(SchoolLicenceService::class)->can('system_admin');
    }

    private function currentUserProfileRoute(): string
    {
        $user = auth()->user();
        if ($user === null) {
            return route('profile');
        }

        return match ($user->type) {
            'admin' => route('admin.profile'),
            'teacher' => route('teacher.profile'),
            'student' => route('student.profile'),
            default => route('profile'),
        };
    }
}
