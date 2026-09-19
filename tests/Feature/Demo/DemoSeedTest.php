<?php

namespace Tests\Feature\Demo;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $licence = \App\Models\SchoolLicence::query()->first();
        $this->assertNotNull($licence);
        $this->assertTrue((bool) $licence->module_finance);
        $this->assertTrue((bool) $licence->module_system_admin);
    }

    public function test_demo_invariants_hold(): void
    {
        $this->seed(DemoDataSeeder::class);

        $current = \App\Models\AcademicSession::query()->where('is_current', true)->firstOrFail();
        $previous = \App\Models\AcademicSession::query()->where('is_current', false)->firstOrFail();
        $roleOf = fn (int $userId): ?string => \App\Models\User::find($userId)?->adminRoleSlug();

        // Licence cap fits approvals; every module on.
        $licence = \App\Models\SchoolLicence::query()->firstOrFail();
        $active = \App\Models\Student::query()->where('approved', true)->where('graduated', false)
            ->whereHas('user', fn ($q) => $q->where('active', true))->count();
        $this->assertLessThan((int) $licence->max_active_students, $active);
        foreach (['core_timetable', 'core_attendance', 'core_memos', 'core_impersonation', 'module_finance', 'module_staff_hr', 'module_reports', 'module_evaluations', 'module_student_welfare', 'module_progression', 'module_system_admin', 'module_teacher_tools', 'module_messaging', 'module_practicum'] as $flag) {
            $this->assertTrue((bool) $licence->{$flag}, "licence flag {$flag}");
        }

        // Promotion wrote audits; nobody exceeds their program max.
        $this->assertSame(124, \App\Models\Promotion::query()->where('academic_session_id', $current->id)->count());
        foreach (\App\Models\Promotion::query()->get() as $promo) {
            $this->assertSame($promo->from_level + 100, $promo->to_level);
            $this->assertLessThanOrEqual($promo->student->program->program_length * 100, $promo->to_level);
        }

        // Owing reconciles per student per year: paid + balance + discount == gross.
        $feeService = app(\App\Services\Finance\FeeCalculationService::class);
        $checked = 0;
        foreach (\App\Models\Student::query()->where('approved', true)->limit(25)->get() as $student) {
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
            \App\Models\Student::query()->where('approved', true)->count(),
            \App\Models\FeePayment::query()->count()
        );

        // Pending exists current-only; no results under pending slips.
        $this->assertTrue(
            \App\Models\ResultSlip::query()->where('status', 'pending')
                ->where('academic_session_id', '!=', $current->id)->doesntExist()
        );
        $badPending = \App\Models\TranscriptRequest::query()->where('status', 'pending')
            ->where(fn ($q) => $q->whereNotNull('processed_by')->orWhereDate('created_at', '<', $current->start_date))
            ->count();
        $this->assertSame(0, $badPending);
        $pendingSlipIds = \App\Models\ResultSlip::query()->where('status', 'pending')->pluck('id');
        $this->assertGreaterThan(0, $pendingSlipIds->count());
        $this->assertSame(0, \App\Models\Result::query()->whereIn('result_slip_id', $pendingSlipIds)->count());
        $this->assertGreaterThan(0, \App\Models\Grade::query()->whereIn('result_slip_id', $pendingSlipIds)->count());

        // Role ownership: postings and approvals belong to exactly one role.
        $this->assertTrue(\App\Models\Invoice::query()->whereHas('creator', fn ($q) => $q->where('email', '!=', 'accountant@demo.com'))->doesntExist());
        $this->assertTrue(\App\Models\Payment::query()->whereHas('receiver', fn ($q) => $q->where('email', '!=', 'accountant@demo.com'))->doesntExist());
        foreach (\App\Models\TranscriptRequest::query()->whereNotNull('processed_by')->get() as $tr) {
            $this->assertSame('exams_officer', $roleOf($tr->processed_by));
        }
        foreach (\App\Models\ResultSlip::query()->whereNotNull('approved_by')->get() as $slip) {
            $this->assertSame('exams_officer', $roleOf($slip->approved_by));
        }
        foreach (\App\Models\EvaluationForm::query()->get() as $form) {
            $this->assertSame('quality_assurance_officer', $roleOf($form->created_by));
        }
        $this->assertSame([], \App\Models\Memo::query()->whereNotIn('sender_id', \App\Models\User::query()->whereIn('email', ['secretary@demo.com', 'pro@demo.com'])->pluck('id'))->pluck('id')->all());
        $signerRoles = \App\Models\MemoSignatory::query()->with('user.admin.role')->get()
            ->map(fn ($s) => $s->user->adminRoleSlug())->unique()->sort()->values()->all();
        $this->assertEmpty(array_diff($signerRoles, ['hod', 'dean_of_students', 'vice_principal', 'public_relations_officer']));
        $secretaryId = \App\Models\User::query()->where('email', 'secretary@demo.com')->value('id');
        $this->assertSame(0, \App\Models\MemoSignatory::query()->where('user_id', $secretaryId)->where('status', 'signed')->where('remarks', '!=', 'Self-signed at creation.')->count());
        $this->assertSame(0, \App\Models\User::query()->whereHas('admin.role', fn ($q) => $q->where('name', 'principal'))->count());
        $this->assertSame('Main System Administrator', \App\Models\User::query()->where('email', 'admin@demo.com')->first()->admin->position_title);

        // Clearance + graduation scoped to finalists, current session.
        foreach (\App\Models\StudentClearance::query()->with('student.program')->get() as $row) {
            $this->assertSame($current->id, (int) $row->academic_session_id);
            $this->assertSame($row->student->program->program_length * 100, (int) $row->student->current_year);
        }
        foreach (\App\Models\Graduation::query()->with('student.program')->get() as $g) {
            $this->assertSame('400', (string) $g->student->current_year);
            $this->assertSame(4, (int) $g->student->program->program_length);
        }

        // Evaluations: previous closed with archived responses; current open,
        // believable fill (never 100%).
        $prevForm = \App\Models\EvaluationForm::query()->where('academic_year', $previous->name)->firstOrFail();
        $curForm = \App\Models\EvaluationForm::query()->where('academic_year', $current->name)->firstOrFail();
        $this->assertSame(-1, (int) $prevForm->getRawOriginal('is_active'));
        $this->assertSame(1, (int) $curForm->getRawOriginal('is_active'));
        $this->assertGreaterThan(0, \App\Models\EvaluationResponse::query()->where('form_id', $prevForm->id)->where('status', 'submitted')->count());
        $this->assertSame(0, \App\Models\EvaluationResponse::query()->where('form_id', $prevForm->id)->where('status', 'draft')->count());
        $eligible = \App\Models\Student::query()->where('approved', true)->count();
        $curSubmitted = \App\Models\EvaluationResponse::query()->where('form_id', $curForm->id)->where('status', 'submitted')->count();
        $this->assertGreaterThan(0, $curSubmitted);
        $this->assertLessThan($eligible, $curSubmitted);

        // Timetable: every course placed in both sessions.
        $courseCount = \App\Models\Course::query()->count();
        foreach ([$previous, $current] as $session) {
            $placed = \App\Models\TimetableClass::query()
                ->whereIn('timetable_id', \App\Models\Timetable::query()->where('session_id', $session->id)->pluck('id'))
                ->distinct()->count('course_id');
            $this->assertSame($courseCount, $placed);
        }
    }
}
