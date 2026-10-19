<?php

namespace Tests\Feature\Demo;

use App\Livewire\Navigation\TeacherSidebar;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\DisciplinaryRecord;
use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\FeePayment;
use App\Models\Grade;
use App\Models\Graduation;
use App\Models\Invoice;
use App\Models\MedicalHistory;
use App\Models\Memo;
use App\Models\MemoSignatory;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Result;
use App\Models\ResultSlip;
use App\Models\SchoolLicence;
use App\Models\Student;
use App\Models\StudentClearance;
use App\Models\TeacherAttendanceSheet;
use App\Models\Timetable;
use App\Models\TimetableClass;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Services\Finance\FeeCalculationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_runs_clean(): void
    {
        // Engine compatibility only (no realism changes): must run without
        // exceptions on strict-mode-capable schema. Content assertions are
        // structural, not exact counts (seeder uses rand()).
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('schools', ['name' => 'Apex Polytechnic (Demo Sandbox)']);
        $this->assertDatabaseHas('users', ['email' => 'admin@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'teacher@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'student@demo.com']);

        $licence = SchoolLicence::query()->first();
        $this->assertNotNull($licence);
        $this->assertTrue((bool) $licence->module_finance);
        $this->assertTrue((bool) $licence->module_system_admin);

        // Email verification is for live users only: every seeded account
        // (including admission-intake students) is already verified, so the
        // verify banner never fires in demo.
        $this->assertSame(0, User::query()->whereNull('email_verified_at')->count());
    }

    public function test_demo_invariants_hold(): void
    {
        $this->seed(DemoDataSeeder::class);

        $current = AcademicSession::query()->where('is_current', true)->firstOrFail();
        $previous = AcademicSession::query()->where('is_current', false)->firstOrFail();
        $roleOf = fn (int $userId): ?string => User::find($userId)?->adminRoleSlug();

        // Licence cap fits approvals; every module on.
        $licence = SchoolLicence::query()->firstOrFail();
        $active = Student::query()->where('approved', true)->where('graduated', false)
            ->whereHas('user', fn ($q) => $q->where('active', true))->count();
        $this->assertLessThan((int) $licence->max_active_students, $active);
        foreach (['core_timetable', 'core_attendance', 'core_memos', 'core_impersonation', 'module_finance', 'module_staff_hr', 'module_reports', 'module_evaluations', 'module_student_welfare', 'module_progression', 'module_system_admin', 'module_teacher_tools', 'module_messaging', 'module_practicum'] as $flag) {
            $this->assertTrue((bool) $licence->{$flag}, "licence flag {$flag}");
        }

        // Promotion wrote audits; nobody exceeds their program max.
        $this->assertSame(124, Promotion::query()->where('academic_session_id', $current->id)->count());
        foreach (Promotion::query()->get() as $promo) {
            $this->assertSame($promo->from_level + 100, $promo->to_level);
            $this->assertLessThanOrEqual($promo->student->program->program_length * 100, $promo->to_level);
        }
        $this->assertTrue(
            Promotion::query()
                ->whereHas('student', fn ($query) => $query->where('approved', false))
                ->doesntExist()
        );

        // Owing reconciles per student per year: paid + balance + discount == gross.
        $feeService = app(FeeCalculationService::class);
        $checked = 0;
        foreach (Student::query()->where('approved', true)->limit(25)->get() as $student) {
            foreach ([$previous, $current] as $session) {
                $calc = $feeService->calculateStudentFees($student, $session);
                $this->assertEqualsWithDelta(
                    $calc['gross_fees'],
                    $calc['amount_paid'] + $calc['balance'] + $calc['discount'],
                    0.01
                );
                $this->assertGreaterThanOrEqual(0, $calc['balance']);
                $checked++;
            }
        }
        $this->assertSame(50, $checked);
        // One lifetime ledger row per approved student (real service semantics).
        $this->assertSame(
            Student::query()->where('approved', true)->count(),
            FeePayment::query()->count()
        );

        // Pending exists current-only; no results under pending slips.
        $this->assertTrue(
            ResultSlip::query()->where('status', 'pending')
                ->where('academic_session_id', '!=', $current->id)->doesntExist()
        );
        $badPending = TranscriptRequest::query()->where('status', 'pending')
            ->where(fn ($q) => $q->whereNotNull('processed_by')->orWhereDate('created_at', '<', $current->start_date))
            ->count();
        $this->assertSame(0, $badPending);

        // Demo includes fresh pending 1st-year admissions for the current
        // academic year, each with a guardian so they can be reviewed and
        // approved from the dashboard or students page.
        $pendingAdmits = Student::query()->where('approved', false)->get();
        $this->assertGreaterThan(0, $pendingAdmits->count());
        foreach ($pendingAdmits as $pending) {
            $this->assertSame('100', (string) $pending->current_year);
            $this->assertNotNull($pending->admission_date);
            $this->assertTrue($pending->admission_date->between($current->start_date, $current->end_date));
            $this->assertTrue($pending->parentGuardians()->exists());
            $this->assertNotNull($pending->department_id);
            $this->assertSame($pending->program->department_id, $pending->department_id);
        }

        $pendingSlipIds = ResultSlip::query()->where('status', 'pending')->pluck('id');
        $this->assertGreaterThan(0, $pendingSlipIds->count());
        $this->assertSame(0, Result::query()->whereIn('result_slip_id', $pendingSlipIds)->count());
        $this->assertGreaterThan(0, Grade::query()->whereIn('result_slip_id', $pendingSlipIds)->count());

        // Role ownership: postings and approvals belong to exactly one role.
        $this->assertTrue(Invoice::query()->whereHas('creator', fn ($q) => $q->where('email', '!=', 'accountant@demo.com'))->doesntExist());
        $this->assertTrue(Payment::query()->whereHas('receiver', fn ($q) => $q->where('email', '!=', 'accountant@demo.com'))->doesntExist());
        foreach (TranscriptRequest::query()->whereNotNull('processed_by')->get() as $tr) {
            $this->assertSame('exams_officer', $roleOf($tr->processed_by));
        }
        foreach (ResultSlip::query()->whereNotNull('approved_by')->get() as $slip) {
            $this->assertSame('exams_officer', $roleOf($slip->approved_by));
        }
        foreach (EvaluationForm::query()->get() as $form) {
            $this->assertSame('quality_assurance_officer', $roleOf($form->created_by));
        }
        $this->assertSame([], Memo::query()->whereNotIn('sender_id', User::query()->whereIn('email', ['secretary@demo.com', 'pro@demo.com'])->pluck('id'))->pluck('id')->all());
        $signerRoles = MemoSignatory::query()->with('user.admin.role')->get()
            ->map(fn ($s) => $s->user->adminRoleSlug())->unique()->sort()->values()->all();
        $this->assertEmpty(array_diff($signerRoles, ['hod', 'dean_of_students', 'vice_principal', 'public_relations_officer']));
        $secretaryId = User::query()->where('email', 'secretary@demo.com')->value('id');
        $this->assertSame(0, MemoSignatory::query()->where('user_id', $secretaryId)->where('status', 'signed')->where('remarks', '!=', 'Self-signed at creation.')->count());
        $this->assertSame(0, User::query()->whereHas('admin.role', fn ($q) => $q->where('name', 'principal'))->count());
        $this->assertSame('Main System Administrator', User::query()->where('email', 'admin@demo.com')->first()->admin->position_title);

        // Clearance + graduation scoped to finalists, current session.
        foreach (StudentClearance::query()->with('student.program')->get() as $row) {
            $this->assertSame($current->id, (int) $row->academic_session_id);
            $this->assertSame($row->student->program->program_length * 100, (int) $row->student->current_year);
        }
        foreach (Graduation::query()->with('student.program')->get() as $g) {
            $this->assertSame(
                $g->student->program->program_length * 100,
                (int) $g->student->current_year
            );
        }

        // Evaluations: previous closed with archived responses; current open,
        // believable fill (never 100%).
        $prevForm = EvaluationForm::query()->where('academic_year', $previous->name)->firstOrFail();
        $curForm = EvaluationForm::query()->where('academic_year', $current->name)->firstOrFail();
        $this->assertSame(-1, (int) $prevForm->getRawOriginal('is_active'));
        $this->assertSame(1, (int) $curForm->getRawOriginal('is_active'));
        $this->assertGreaterThan(0, EvaluationResponse::query()->where('form_id', $prevForm->id)->where('status', 'submitted')->count());
        $this->assertSame(0, EvaluationResponse::query()->where('form_id', $prevForm->id)->where('status', 'draft')->count());
        $eligible = Student::query()->where('approved', true)->count();
        $curSubmitted = EvaluationResponse::query()->where('form_id', $curForm->id)->where('status', 'submitted')->count();
        $this->assertGreaterThan(0, $curSubmitted);
        $this->assertLessThan($eligible, $curSubmitted);

        // Actor columns: discipline recorded by Dean, medical by VP,
        // teacher attendance by an HOD; ledgers carry their academic year.
        $this->assertSame(0, DisciplinaryRecord::query()->whereNull('recorded_by')->count());
        $this->assertSame(0, MedicalHistory::query()->whereNull('recorded_by')->count());
        $this->assertSame(0, TeacherAttendanceSheet::query()->whereNull('recorded_by')->count());
        foreach (DisciplinaryRecord::query()->get() as $record) {
            $this->assertSame('dean_of_students', $roleOf($record->recorded_by));
        }
        foreach (MedicalHistory::query()->get() as $history) {
            $this->assertSame('vice_principal', $roleOf($history->recorded_by));
        }
        $hodIds = User::query()->whereHas('admin.role', fn ($q) => $q->where('name', 'hod'))->pluck('id')->all();
        $this->assertGreaterThan(0, TeacherAttendanceSheet::query()->whereIn('recorded_by', $hodIds)->count());
        $this->assertSame(0, FeePayment::query()->whereNull('academic_year')->count());

        // Teacher portal map: lecturers resolve full menu (regression: an
        // empty teacher_portal_roles table collapsed the sidebar to
        // Dashboard + Profile only).
        $this->assertDatabaseHas('teacher_portal_roles', ['name' => 'lecturer']);
        $teacher = User::query()->where('email', 'teacher@demo.com')->firstOrFail();
        $this->assertSame(['courses', 'students', 'assessments', 'communication'], $teacher->teacherPermissions());
        Livewire::actingAs($teacher)
            ->test(TeacherSidebar::class)
            ->assertSee('My Courses')
            ->assertSee('Students')
            ->assertSee('Assessments')
            ->assertSee('Communication');

        // Timetable: every course placed in both sessions.
        $courseCount = Course::query()->count();
        foreach ([$previous, $current] as $session) {
            $placed = TimetableClass::query()
                ->whereIn('timetable_id', Timetable::query()->where('session_id', $session->id)->pluck('id'))
                ->distinct()->count('course_id');
            $this->assertSame($courseCount, $placed);
        }
    }
}
