<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostLoginRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->route('login')->with('error', 'User not found');
        }

        return match ($user->type) {
            'admin', 'staff' => $this->redirectAdmin($user),
            'teacher' => $this->redirectTeacher($user),
            default => $this->redirectStudent($user),
        };
    }

    private function redirectAdmin(User $user): RedirectResponse
    {
        if ($user->type === 'admin') {
            $user->loadMissing('admin');
            if ($user->admin === null) {
                return redirect()->route('admin.setup.personal');
            }
        } elseif ($user->type === 'staff') {
            $user->loadMissing('nonTeachingStaff');
            if ($user->nonTeachingStaff === null) {
                return redirect()->route('admin.setup.personal');
            }
        }

        $school = School::current();
        if ($school === null || ! $school->ready) {
            return redirect()->route('admin.setup.school');
        }

        return redirect()->route('admin.dashboard');
    }

    private function redirectTeacher(User $user): RedirectResponse
    {
        $user->loadMissing('teacher');
        $teacher = $user->teacher;
        if ($teacher !== null && ($teacher->password_reset_required || ! $teacher->is_onboarded)) {
            return redirect()->route('teacher.setup');
        }

        return redirect()->route('teacher.dashboard');
    }

    private function redirectStudent(User $user): RedirectResponse
    {
        $user->loadMissing('student');
        $student = $user->student;
        if ($student !== null && $student->is_new) {
            return redirect()->route('student.setup.personal');
        }

        return redirect()->route('student.dashboard');
    }
}
