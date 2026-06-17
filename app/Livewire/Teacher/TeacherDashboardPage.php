<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\Course;
use App\Models\Result;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Semester;
use App\Models\TimetableClass;
use App\Models\CourseMaterial;
use App\Models\Memo;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TeacherDashboardPage extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $teacher = $user->teacher;
        if ($teacher === null) {
            abort(403);
        }

        $assignedStudentsCount = Student::query()
            ->whereHas('program.courses', function ($q) use ($teacher): void {
                $q->where('teacher_id', $teacher->id);
            })
            ->count();

        $coursesCount = Course::query()->where('teacher_id', $teacher->id)->count();

        /** Results not fully graded (score or points still null). */
        $pendingResultsCount = Result::query()
            ->where('teacher_id', $teacher->id)
            ->where(function ($q): void {
                $q->whereNull('score')->orWhereNull('grade_points');
            })
            ->count();

        // Get Active Academic Session & Semester
        $activeSession = AcademicSession::query()->where('is_current', true)->first() ?? AcademicSession::query()->orderByDesc('id')->first();
        $activeSemester = Semester::query()->where('is_active', true)->first() ?? Semester::query()->orderByDesc('id')->first();

        $activeTermString = '';
        if ($activeSession && $activeSemester) {
            $activeTermString = $activeSession->name . ' · ' . $activeSemester->name;
        } elseif ($activeSession) {
            $activeTermString = $activeSession->name;
        } elseif ($activeSemester) {
            $activeTermString = $activeSemester->name;
        } else {
            $activeTermString = __('No Active Session');
        }

        // Fetch Today's Classes
        $todayDay = now()->format('l');
        $todayClasses = TimetableClass::query()
            ->where('teacher_id', $teacher->id)
            ->where('day', $todayDay)
            ->with(['course', 'program'])
            ->orderBy('start_time')
            ->get();

        // Fetch Recent Course Materials
        $recentMaterials = CourseMaterial::query()
            ->where('teacher_id', $teacher->id)
            ->with(['course'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Fetch Unread Memos Count and Recent Memos
        $unreadMemosCount = Memo::query()
            ->whereIn('status', ['sent', 'archived'])
            ->whereHas('readReceipts', function ($q) use ($user): void {
                $q->where('user_id', $user->id)->whereNull('acknowledged_at');
            })
            ->count();

        $recentMemos = Memo::query()
            ->whereIn('status', ['sent', 'archived'])
            ->whereHas('readReceipts', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            })
            ->with(['sender'])
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        $rank = trim((string) ($teacher->rank ?? ''));
        $qualification = trim((string) ($teacher->qualification ?? ''));
        $departmentName = $teacher->department?->name ? trim((string) $teacher->department->name) : '';
        $subtitleParts = array_filter([
            $rank !== '' ? $rank : null,
            $qualification !== '' ? $qualification : null,
            $departmentName !== '' ? $departmentName : null,
        ]);
        $subtitle = $subtitleParts !== [] ? implode(' · ', $subtitleParts) : '—';

        $greetingName = $teacher->othernames
            ? trim((string) $teacher->othernames).' '.trim((string) $teacher->lastname)
            : (trim((string) $teacher->lastname) ?: ($user->username ?? $user->email ?? ''));

        return view('livewire.teacher.teacher-dashboard-page', [
            'greetingName' => $greetingName,
            'subtitle' => $subtitle,
            'assignedStudentsCount' => $assignedStudentsCount,
            'coursesCount' => $coursesCount,
            'pendingResultsCount' => $pendingResultsCount,
            'activeTermString' => $activeTermString,
            'todayClasses' => $todayClasses,
            'recentMaterials' => $recentMaterials,
            'unreadMemosCount' => $unreadMemosCount,
            'recentMemos' => $recentMemos,
        ])->layout('components.layouts.teacher', [
            'title' => __('Dashboard'),
            'headerTitle' => __('Welcome, :name', ['name' => $greetingName]),
            'headerDescription' => $subtitle,
        ]);
    }
}
