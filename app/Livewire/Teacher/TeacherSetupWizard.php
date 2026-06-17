<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\Teacher;
use App\Models\User;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class TeacherSetupWizard extends Component
{
    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?Teacher $teacher = null;

    public ?string $profilePicPond = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('teacher');
        $this->teacher = $user->teacher;
        if ($this->teacher === null) {
            abort(404);
        }

        $this->username = (string) ($user->username ?? '');
    }

    public function save(): void
    {
        if ($this->teacher === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        $passwordRules = $this->teacher->password_reset_required
            ? ['required', 'confirmed', Password::defaults()]
            : ['nullable', 'confirmed', Password::defaults()];

        $this->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'password' => $passwordRules,
            'profilePicPond' => ['nullable', 'string', 'max:500'],
        ]);

        $data = ['username' => $this->username];
        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        $teacherData = [
            'password_reset_required' => false,
            'is_onboarded' => true,
        ];

        $profilePic = $this->uploadedFromPending($this->profilePicPond);
        if ($profilePic !== null) {
            $prev = $this->teacher->profile_pic;
            if ($prev !== null && $prev !== '' && ! str_contains($prev, '..')) {
                Storage::disk('college_uploads')->delete($prev);
            }
            $teacherData['profile_pic'] = $profilePic->store('teachers/profiles', 'college_uploads');
            $this->clearPending($this->profilePicPond);
            $this->profilePicPond = null;
        }

        $this->teacher->forceFill($teacherData)->save();

        session()->flash('status', __('Your profile has been saved.'));

        $this->redirect(route('teacher.dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.teacher.teacher-setup-wizard')
            ->layout('components.layouts.teacher', ['title' => __('Teacher setup')]);
    }

    private function uploadedFromPending(?string $pendingPath): ?UploadedFile
    {
        if ($pendingPath === null || $pendingPath === '') {
            return null;
        }

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($pendingPath, $userId)) {
            $this->addError('profilePicPond', __('Uploaded photo is invalid.'));

            return null;
        }

        $path = Storage::disk('local')->path($pendingPath);

        return new UploadedFile(
            $path,
            basename($path),
            mime_content_type($path) ?: null,
            null,
            true
        );
    }

    private function clearPending(?string $pendingPath): void
    {
        if ($pendingPath !== null && $pendingPath !== '') {
            Storage::disk('local')->delete($pendingPath);
        }
    }
}
