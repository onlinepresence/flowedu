<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Course;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\Result;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expenditure;
use App\Models\LeaveRequest;
use App\Models\EvaluationResponse;
use App\Models\SystemAudit;
use App\Models\User;
use App\Models\Admin;
use App\Models\Announcement;
use App\Models\Memo;
use App\Models\DisciplinaryRecord;
use App\Models\MedicalHistory;
use App\Services\SchoolLicenceService;
use App\Services\StudentLicenceCapService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminDashboardPage extends Component
{
    public function render(
        SchoolLicenceService $licenceService,
        StudentLicenceCapService $capService,
    ): View {
        $user = auth()->user();
        $admin = $user->admin;
        $roleSlug = $user->adminRoleSlug();

        // 1. Resolve Archetype
        $archetype = match ($roleSlug) {
            'owner', 'system_admin', 'principal', 'vice_principal', 'registrar', 'admissions_officer' => 'executive',
            'hod', 'secretary', 'exams_officer', 'quality_assurance_officer' => 'academic',
            'finance_officer', 'accountant', 'procurement_officer' => 'finance',
            'dean_of_students' => 'welfare',
            'human_resource_manager' => 'hr',
            'internal_auditor' => 'audit',
            default => 'general',
        };

        // 2. Fetch Scoped Data
        // Student Stats
        $pendingCount = $this->applyScope(Student::query(), $admin)->where('approved', false)->count();
        $approvedCount = $this->applyScope(Student::query(), $admin)->where('approved', true)->count();
        $pendingPreview = $this->applyScope(Student::query(), $admin)
            ->where('approved', false)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(5)
            ->get(['id', 'index_number', 'firstname', 'othernames', 'lastname']);

        // License
        $maxStudents = $licenceService->maxActiveStudents();
        $activeForCap = $capService->activeStudentsCount();
        $capNotice = $capService->dashboardCapNotice();
        $capNoticeIsBlock = $capNotice !== null && $licenceService->studentCapMode() === 'block';

        // Academic Stats
        $programsCount = $this->applyScope(Program::query(), $admin)->count();
        $coursesCount = $this->applyScope(Course::query(), $admin, 'program')->count();
        $teachersCount = $this->applyScope(Teacher::query(), $admin)->count();
        $pendingGradesCount = $this->applyScope(Result::query(), $admin, 'course.program')->whereNull('result_slip_id')->count();

        // Finance Stats
        $totalInvoiced = Invoice::sum('total_amount');
        $totalCollected = Payment::sum('amount');
        $totalOutstanding = $totalInvoiced - $totalCollected;
        $totalExpenditure = Expenditure::sum('amount');

        // Recent Transactions
        $recentPayments = Payment::with('student')->latest()->limit(5)->get();
        $recentExpenditures = Expenditure::latest()->limit(5)->get();

        // Welfare Stats
        $studentIds = $this->applyScope(Student::query(), $admin)->pluck('id');
        $studentIndexNumbers = $this->applyScope(Student::query(), $admin)->pluck('index_number');

        $disciplinaryCount = DisciplinaryRecord::whereIn('index_number', $studentIndexNumbers)->count();
        $medicalCount = $this->applyScope(MedicalHistory::query(), $admin, 'student')->count();
        $recentDisciplinary = DisciplinaryRecord::whereIn('index_number', $studentIndexNumbers)
            ->latest()
            ->limit(5)
            ->get();


        // HR & Leaves
        $totalStaffCount = User::whereIn('type', ['admin', 'staff'])->count();
        $totalTeachersCount = Teacher::count();
        $pendingLeavesCount = $this->applyLeaveScope(LeaveRequest::query()->where('status', 'pending'), $admin);
        
        $pendingLeavesPreview = $this->applyLeaveScope(LeaveRequest::query()->where('status', 'pending'), $admin)
            ->latest()
            ->limit(5)
            ->get();

        // Audit Trail
        $recentAuditLogs = SystemAudit::with('user')->latest()->limit(5)->get();

        // Evaluations
        $evaluationResponsesCount = $this->applyEvaluationScope(EvaluationResponse::query(), $admin)->count();
        $activeFormsCount = \App\Models\EvaluationForm::where('is_active', true)->count();
        $recentEvaluations = $this->applyEvaluationScope(EvaluationResponse::query(), $admin)
            ->with(['form', 'studentDepartment'])
            ->latest()
            ->limit(5)
            ->get();

        // Memos & Announcements
        $recentMemos = Memo::latest()->limit(5)->get();
        $recentAnnouncements = Announcement::latest()->limit(5)->get();

        return view('livewire.admin.admin-dashboard-page', [
            'archetype' => $archetype,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'pendingPreview' => $pendingPreview,
            'maxStudents' => $maxStudents,
            'activeForCap' => $activeForCap,
            'capNotice' => $capNotice,
            'capNoticeIsBlock' => $capNoticeIsBlock,
            'programsCount' => $programsCount,
            'coursesCount' => $coursesCount,
            'teachersCount' => $teachersCount,
            'pendingGradesCount' => $pendingGradesCount,
            'totalInvoiced' => $totalInvoiced,
            'totalCollected' => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
            'totalExpenditure' => $totalExpenditure,
            'recentPayments' => $recentPayments,
            'recentExpenditures' => $recentExpenditures,
            'disciplinaryCount' => $disciplinaryCount,
            'medicalCount' => $medicalCount,
            'recentDisciplinary' => $recentDisciplinary,
            'totalStaffCount' => $totalStaffCount,
            'totalTeachersCount' => $totalTeachersCount,
            'pendingLeavesCount' => $pendingLeavesCount->count(),
            'pendingLeavesPreview' => $pendingLeavesPreview,
            'recentAuditLogs' => $recentAuditLogs,
            'evaluationResponsesCount' => $evaluationResponsesCount,
            'activeFormsCount' => $activeFormsCount,
            'recentEvaluations' => $recentEvaluations,
            'recentMemos' => $recentMemos,
            'recentAnnouncements' => $recentAnnouncements,
            'canFinance' => $licenceService->can('finance'),
        ])->layout('components.layouts.admin', [
            'title' => __('Dashboard'),
            'headerDescription' => __('Welcome back! Here is an overview of the school status, quick actions, and recent activities.'),
        ]);
    }

    private function applyScope($query, ?Admin $admin, string $relation = null)
    {
        if ($admin === null) {
            return $query;
        }

        if ($admin->department_id) {
            if ($relation) {
                return $query->whereHas($relation, function ($q) use ($admin) {
                    $q->where('department_id', $admin->department_id);
                });
            }
            return $query->where('department_id', $admin->department_id);
        }

        if ($admin->faculty_id) {
            if ($relation) {
                return $query->whereHas($relation . '.department', function ($q) use ($admin) {
                    $q->where('faculty_id', $admin->faculty_id);
                });
            }
            return $query->whereHas('department', function ($q) use ($admin) {
                $q->where('faculty_id', $admin->faculty_id);
            });
        }

        return $query;
    }

    private function applyEvaluationScope($query, ?Admin $admin)
    {
        if ($admin === null) {
            return $query;
        }

        if ($admin->department_id) {
            return $query->where('student_department_id', $admin->department_id);
        }

        if ($admin->faculty_id) {
            return $query->whereHas('studentDepartment', function ($q) use ($admin) {
                $q->where('faculty_id', $admin->faculty_id);
            });
        }

        return $query;
    }

    private function applyLeaveScope($query, ?Admin $admin)
    {
        if ($admin === null) {
            return $query;
        }

        if ($admin->department_id) {
            return $query->whereHas('user', function ($q) use ($admin) {
                $q->whereHas('admin', fn($a) => $a->where('department_id', $admin->department_id))
                  ->orWhereHas('teacher', fn($t) => $t->where('department_id', $admin->department_id));
            });
        }

        if ($admin->faculty_id) {
            return $query->whereHas('user', function ($q) use ($admin) {
                $q->whereHas('admin.department', fn($d) => $d->where('faculty_id', $admin->faculty_id))
                  ->orWhereHas('teacher.department', fn($d) => $d->where('faculty_id', $admin->faculty_id));
            });
        }

        return $query;
    }
}


