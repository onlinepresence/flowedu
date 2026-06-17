<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Actions\Students\DeleteStudentRegistrationAction;
use App\Http\Requests\Student\DeleteStudentAccountRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StudentSetupDeletePage extends Component
{
    public int $user_id = 0;

    public string $password = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->user_id = $user->id;
    }

    public function destroy(DeleteStudentRegistrationAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->validate(DeleteStudentAccountRequest::rulesFor($user->id));

        $action->execute($user);

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Account has been deleted'));
    }

    public function render(): View
    {
        return view('livewire.student.student-setup-delete-page')
            ->layout('components.layouts.student', ['title' => __('Cancel registration')]);
    }
}
