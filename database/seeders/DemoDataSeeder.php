<?php

namespace Database\Seeders;

/*
|--------------------------------------------------------------------------
| FlowEdu Demo Data Seeder (single-connection, MySQL-strict clean)
|--------------------------------------------------------------------------
|
| Two dynamic GES academic years anchored on today (never hardcoded):
|   previous = [today - 1yr, today - 1day], current = [today, today + 1yr - 1day]
|
| Non-negotiable principles:
| - Deterministic: ONE mt_srand() seed drives every pick; money figures are
|   fixed constants (current-year uplift is a seeded 0-5% draw per structure,
|   reproducible run to run). The only non-determinism is UUID/token secrets.
| - Fast: ONE precomputed password hash reused for every demo user, a single
|   DB transaction around all inserts, chunked bulk inserts (no per-row
|   Eloquent creates on hot paths).
| - Real code paths: AutoPromotionService bumps years (never hand-set
|   current_year), ActivateStudentDashboardAction assigns official index
|   numbers, AssertStudentApprovalAllowedByLicence gates approvals,
|   EvaluationFormStatusService + SemesterActiveStatusService open/close
|   windows, ProcessGraduationService graduates 4-year finalists,
|   FeeCalculationService::syncFeePaymentLedger builds owing ledgers.
|
| Role -> movement map (mirrors UserRole::ensureSystemRoles; a person never
| crosses roles; NO principal user is seeded — VP/Dean top out all chains):
|   owner                 system setup only (skeleton, users/roles, licence,
|                         impersonation/audit/backups)
|   admissions_officer    ALL student approvals
|   registrar             student records, clearance processing
|   accountant            posts invoices + payments + scholarships
|   finance_officer       financial oversight/reporting (reads)
|   internal_auditor      views only, touches nothing
|   exams_officer         grade uploads, grade approvals, transcripts
|   lecturers (teachers)  enter their own course marks
|   quality_assurance     CREATES all evaluation forms; approves materials
|   secretary             DRAFTS every memo, signs none
|   hod                   signs department memos, dept leaves, attendance files
|   dean_of_students /    top administration signatures, discipline + medical
|     vice_principal
|   human_resource_mgr    leave decisions for non-teaching staff
|   pro                   announcements (college memo announcements)
|   students              evaluations, transcripts, fees, discipline/medical
|
| Known gaps (flagged, never faked):
| - announcements.teacher_id is required, so course announcements are
|   authored by lecturers (approved by QA); the PRO owns college-wide
|   announcements, which live as chainless memos.
*/

use App\Actions\Students\ActivateStudentDashboardAction;
use App\Actions\Students\AssertStudentApprovalAllowedByLicence;
use App\Actions\Students\SaveStudentAdmissionProfileAction;
use App\Models\AcademicSession;
use App\Models\Admin;
use App\Models\AdminType;
use App\Models\Course;
use App\Models\Department;
use App\Models\DisciplinaryRecord;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\Expenditure;
use App\Models\Faculty;
use App\Models\FeeComponent;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Hall;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobAlert;
use App\Models\LeaveRequest;
use App\Models\MedicalHistory;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoReadReceipt;
use App\Models\MemoSignatory;
use App\Models\MemoTracking;
use App\Models\ParentGuardian;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Program;
use App\Models\ResponseDetail;
use App\Models\Result;
use App\Models\ResultSlip;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use App\Models\School;
use App\Models\SchoolLicence;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\StaffLeaveType;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherCourse;
use App\Models\TeacherRole;
use App\Models\Timetable;
use App\Models\TimetableClass;
use App\Models\TranscriptRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Finance\FeeCalculationService;
use App\Services\Maintenance\AutoPromotionService;
use App\Services\Maintenance\EvaluationFormStatusService;
use App\Services\Maintenance\SemesterActiveStatusService;
use App\Services\Students\ProcessGraduationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    // ------------------------------------------------------------------
    // Deterministic helpers (seeded RNG only — no rand()/fake() anywhere)
    // ------------------------------------------------------------------

    private int $seq = 0;

    private function nextSeq(): int
    {
        return ++$this->seq;
    }

    /** Deterministic pick from a list. */
    private function pick(array $list): mixed
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    /** Deterministic token (hex) — avoids random_bytes so runs reproduce. */
    private function token(int $bytes = 8): string
    {
        $out = '';
        for ($i = 0; $i < $bytes; $i++) {
            $out .= str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        }

        return $out;
    }

    private function ghanaCard(): string
    {
        return 'GHA-'.str_pad((string) (310000000 + $this->nextSeq()), 9, '0', STR_PAD_LEFT).'-'.mt_rand(0, 9);
    }

    private function ghanaPhone(): string
    {
        $prefixes = ['024', '025', '053', '054', '055', '059', '020', '050', '027', '057', '026', '056'];
        $prefix = $prefixes[$this->seq % count($prefixes)];

        return '+233 '.substr($prefix, 1).' '.str_pad((string) (1000000 + (($this->seq * 7919) % 8999999)), 7, '0', STR_PAD_LEFT);
    }

    /** A date inside [$start, $end] derived deterministically from a salt. */
    private function dateIn(Carbon $start, Carbon $end, int $salt, int $step = 7): Carbon
    {
        $spanDays = $start->diffInDays($end);
        if ($spanDays <= 0) {
            return $start->copy();
        }
        $date = $start->copy()->addDays(($salt * $step) % ($spanDays + 1));
        if ($date->gt($end)) {
            return $end->copy();
        }
        if ($date->lt($start)) {
            return $start->copy();
        }

        return $date;
    }

    private function stamp(array $rows): array
    {
        $now = now()->toDateTimeString();
        foreach ($rows as &$row) {
            $row['created_at'] ??= $now;
            $row['updated_at'] ??= $now;
        }

        return $rows;
    }

    private function chunkInsert(string $table, array $rows, int $chunk = 400): void
    {
        foreach (array_chunk($rows, $chunk) as $piece) {
            DB::table($table)->insert($piece);
        }
    }

    public function run(): void
    {
        mt_srand(20260719);

        // Keep phpunit runs hermetic: file writes (profile photos, memo
        // attachments, attendance CSVs) go to fake disks under testing.
        if (app()->runningUnitTests()) {
            Storage::fake('local');
            Storage::fake('college_uploads');
        }

        // --------------------------------------------------------------
        // 0. Reset (TRUNCATE implicit-commits on MySQL: keep OUTSIDE txn)
        // --------------------------------------------------------------
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        $tables = [
            'users', 'admins', 'admin_types', 'teachers', 'students', 'schools', 'school_licences',
            'academic_sessions', 'semesters', 'halls', 'faculties', 'departments',
            'programs', 'courses', 'teacher_assignments', 'teacher_courses', 'teacher_roles',
            'teacher_portal_roles', 'parent_guardians',
            'fee_structures', 'fee_structure_items', 'fee_components', 'payments', 'fee_payments',
            'fee_breakdown_requests',
            'grades', 'results', 'result_slips', 'grade_points', 'academic_information',
            'transcript_requests',
            'disciplinary_records', 'medical_histories',
            'evaluation_forms', 'evaluation_questions', 'evaluation_responses', 'response_details',
            'announcements', 'course_materials',
            'memos', 'memo_tracking', 'memo_attachments', 'memo_signatories', 'memo_read_receipts',
            'notifications',
            'scholarships', 'scholarship_recipients',
            'timetables', 'timetable_classes',
            'teacher_attendance_sheets',
            'products', 'invoices', 'invoice_items', 'expenditures',
            'staff_leave_types', 'leave_requests',
            'office_assignment_histories',
            'student_clearances', 'graduations', 'promotions',
            'job_alerts', 'settings', 'system_audits', 'admin_impersonation_logs', 'backups',
            'documents', 'activities',
            'user_file_categories', 'user_uploaded_files',
            'conversations', 'conversation_participants', 'messages',
            'teaching_practice_supervisions', 'shared_lesson_plans',
            'password_reset_tokens',
        ];
        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
            } catch (\Throwable $e) {
                // Table may not exist on older checkouts; strict-clean means
                // migrate:fresh first, so a missing table here is unexpected —
                // fail loudly rather than seeding half a schema.
                throw new \RuntimeException("DemoDataSeeder cannot truncate [{$table}]: ".$e->getMessage(), 0, $e);
            }
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // ONE precomputed hash reused for every demo user (fast by design).
        $passwordHash = Hash::make('password');
        $now = now();

        DB::transaction(function () use ($passwordHash, $now): void {
            $this->seedAll($passwordHash, $now);
        });

        Auth::logout();
    }

    private function seedAll(string $passwordHash, Carbon $now): void
    {
        // ==============================================================
        // 1. SKELETON — sessions (dynamic GES years), school, licence
        // ==============================================================
        AdminType::ensureDefaults();
        UserRole::ensureSystemRoles();
        $roleId = fn (string $name): ?int => UserRole::query()->where('name', $name)->value('id');

        $today = Carbon::today();
        $prevStart = $today->copy()->subYear();
        $prevEnd = $today->copy()->subDay();
        $curStart = $today->copy();
        $curEnd = $today->copy()->addYear()->subDay();
        $prevName = $prevStart->format('Y').'/'.($prevStart->format('Y') + 1);
        $curName = $curStart->format('Y').'/'.($curStart->format('Y') + 1);

        $school = School::create([
            'name' => 'Apex Polytechnic (Demo Sandbox)',
            'address' => '10 University Road, East Legon, Accra, Ghana',
            'email' => 'sandbox@apex-poly.edu.gh',
            'phone' => '+233 30 298 7654',
            'website' => 'www.apex-poly.edu.gh',
            'description' => 'A premier sandbox environment of the Apex College Management System.',
            'ready' => true,
            'is_admit' => true,
            'motto' => 'Knowledge, Integrity, Excellence',
            'established_year' => 2012,
            'principal_name' => null, // No principal user is seeded; VP/Dean top out chains.
            'facebook_url' => 'https://facebook.com/apexpoly',
            'twitter_url' => 'https://twitter.com/apexpoly',
            'linkedin_url' => 'https://linkedin.com/school/apexpoly',
            'instagram_url' => 'https://instagram.com/apexpoly',
        ]);

        // Licence: ~144 active approvals land in the 101-500 band (x1.25);
        // notes echo config/licence.php core figures (upfront GHS 4,500.00,
        // renewal GHS 1,200.00). Cap 500 keeps the approval demo unblocked.
        SchoolLicence::create([
            'school_id' => $school->id,
            'max_active_students' => 500,
            'licence_start' => $prevStart->toDateString(),
            'licence_end' => $curEnd->toDateString(),
            'support_until' => $curEnd->toDateString(),
            'notes' => 'Demo licence - enrolment band 101-500 (x1.25) - core upfront GHS 4,500.00, renewal GHS 1,200.00, all modules on.',
            'external_ref' => 'REF-DEMO-'.$curStart->format('Y'),
            'licence_key' => 'APEX-DEMO-'.$curStart->format('Y').'-ENTERPRISE',
            'core_timetable' => true,
            'core_attendance' => true,
            'core_memos' => true,
            'core_impersonation' => true,
            'module_finance' => true,
            'module_staff_hr' => true,
            'module_reports' => true,
            'module_evaluations' => true,
            'module_student_welfare' => true,
            'module_progression' => true,
            'module_system_admin' => true,
            'module_teacher_tools' => true,
            'module_messaging' => true,
            'module_practicum' => true,
        ]);

        $prevSession = AcademicSession::create([
            'name' => $prevName,
            'start_date' => $prevStart->toDateString(),
            'end_date' => $prevEnd->toDateString(),
            'is_current' => false,
        ]);
        $curSession = AcademicSession::create([
            'name' => $curName,
            'start_date' => $curStart->toDateString(),
            'end_date' => $curEnd->toDateString(),
            'is_current' => true,
        ]);

        // Semesters: split each year in half; the status service activates
        // whichever contains today (real path, asserted below).
        $semesters = [];
        foreach ([$prevSession, $curSession] as $sess) {
            $s = Carbon::parse($sess->start_date);
            $e = Carbon::parse($sess->end_date);
            $mid = $s->copy()->addMonths(6);
            $semesters[] = Semester::create([
                'academic_session_id' => $sess->id,
                'name' => 'First Semester',
                'start_date' => $s->toDateString(),
                'end_date' => $mid->copy()->subDay()->toDateString(),
                'is_active' => false,
            ]);
            $semesters[] = Semester::create([
                'academic_session_id' => $sess->id,
                'name' => 'Second Semester',
                'start_date' => $mid->toDateString(),
                'end_date' => $e->toDateString(),
                'is_active' => false,
            ]);
        }
        if (! app(SemesterActiveStatusService::class)->run()) {
            throw new \RuntimeException('SemesterActiveStatusService failed during seeding.');
        }
        if (Semester::query()->where('is_active', true)->count() === 0) {
            throw new \RuntimeException('No active semester resolved; check session dates.');
        }

        // Halls (deterministic costs).
        $hallRows = [
            ['name' => 'Republic Hall', 'master' => 'Prof. Kofi Asante', 'cost' => 450.00, 'period' => 'per_semester'],
            ['name' => 'Queens Hall', 'master' => 'Dr. Ama Serwaa', 'cost' => 550.00, 'period' => 'per_semester'],
            ['name' => 'Unity Hall', 'master' => 'Mr. Yaw Darko', 'cost' => 800.00, 'period' => 'per_year'],
            ['name' => 'Independence Hall', 'master' => 'Mrs. Abena Owusu', 'cost' => 750.00, 'period' => 'per_year'],
        ];
        $this->chunkInsert('halls', $this->stamp($hallRows));
        $hallIds = DB::table('halls')->orderBy('id')->pluck('id')->all();

        // Faculties -> departments -> programs (length mix: 4yr + 2yr diplomas).
        $facultyRows = [
            ['name' => 'Faculty of Applied Sciences & Technology'],
            ['name' => 'Faculty of Business & Humanities'],
        ];
        $this->chunkInsert('faculties', $this->stamp($facultyRows));
        $faculties = DB::table('faculties')->orderBy('id')->get();

        $deptRows = [
            ['name' => 'Department of Computer Science', 'faculty_id' => $faculties[0]->id],
            ['name' => 'Department of Electrical Engineering', 'faculty_id' => $faculties[0]->id],
            ['name' => 'Department of Accounting & Finance', 'faculty_id' => $faculties[1]->id],
            ['name' => 'Department of Communication Studies', 'faculty_id' => $faculties[1]->id],
        ];
        $this->chunkInsert('departments', $this->stamp($deptRows));
        $departments = DB::table('departments')->orderBy('id')->get();

        // [program name, dept idx, certificate, cost(tuition), length years, code prefix]
        $programDefs = [
            ['BSc Computer Science', 0, 'BSc', 1800.00, 4, 'CSC'],
            ['Diploma in Information Technology', 0, 'Diploma', 1200.00, 2, 'DIT'],
            ['BEng Electrical Engineering', 1, 'BEng', 2200.00, 4, 'EEE'],
            ['BSc Accounting', 2, 'BSc', 1500.00, 4, 'ACC'],
            ['Diploma in Business Studies', 2, 'Diploma', 1000.00, 2, 'DBS'],
            ['BA Communication Studies', 3, 'BA', 1400.00, 4, 'CMS'],
        ];
        $programRows = [];
        foreach ($programDefs as $def) {
            $programRows[] = [
                'name' => $def[0],
                'department_id' => $departments[$def[1]]->id,
                'certificate' => $def[2],
                'cost' => $def[3],
                'program_length' => $def[4],
            ];
        }
        $this->chunkInsert('programs', $this->stamp($programRows));
        $programs = Program::query()->orderBy('id')->get()->all();

        // Courses: 4yr -> 3+2+2+1 per year; 2yr -> 2+2. Deterministic codes.
        $courseNames = [
            'CSC' => ['Introduction to Computing', 'Introduction to Programming', 'Data Structures & Algorithms', 'Database Management Systems', 'Operating Systems', 'Computer Networks', 'Software Engineering Principles', 'Artificial Intelligence'],
            'DIT' => ['PC Hardware Basics', 'Office Productivity Tools', 'Web Design Fundamentals', 'IT Support Practice'],
            'EEE' => ['Applied Engineering Mathematics', 'Circuit Theory I', 'Digital Logic Design', 'Signals & Systems', 'Power Systems I', 'Control Engineering', 'Microprocessor Systems', 'Final Year Project'],
            'ACC' => ['Introduction to Management', 'Financial Accounting I', 'Principles of Microeconomics', 'Cost Accounting', 'Corporate Finance', 'Auditing & Assurance', 'Taxation of Ghana', 'Strategic Management'],
            'DBS' => ['Business Communication', 'Principles of Marketing', 'Small Business Management', 'Records & Office Practice'],
            'CMS' => ['Introduction to Communication', 'Media Writing Skills', 'Broadcast Production I', 'Public Relations Principles', 'Development Communication', 'Media Law & Ethics', 'Research Methods', 'Documentary Project'],
        ];
        $courseRows = [];
        foreach ($programs as $pi => $program) {
            $prefix = $programDefs[$pi][5];
            $names = $courseNames[$prefix];
            $length = (int) $program->program_length;
            $perYear = $length === 4 ? [3, 2, 2, 1] : [2, 2];
            $cursor = 0;
            foreach ($perYear as $yi => $count) {
                for ($k = 0; $k < $count; $k++) {
                    $cursor++;
                    $courseRows[] = [
                        'code' => $prefix.(($yi + 1) * 100 + $k + 1),
                        'name' => $names[$cursor - 1],
                        'program_id' => $program->id,
                        'course_semester' => (string) (($cursor % 2) + 1),
                        'year_level' => (string) ($yi + 1),
                    ];
                }
            }
        }
        $this->chunkInsert('courses', $this->stamp($courseRows));

        // Grade points (fixed scale).
        $this->chunkInsert('grade_points', $this->stamp([
            ['min_score' => 80.0, 'max_score' => 100.0, 'points' => 4.0, 'grade' => 'A'],
            ['min_score' => 70.0, 'max_score' => 79.99, 'points' => 3.5, 'grade' => 'B+'],
            ['min_score' => 60.0, 'max_score' => 69.99, 'points' => 3.0, 'grade' => 'B'],
            ['min_score' => 50.0, 'max_score' => 59.99, 'points' => 2.5, 'grade' => 'C'],
            ['min_score' => 0.0, 'max_score' => 49.99, 'points' => 0.0, 'grade' => 'F'],
        ]));
        $gradeBands = GradePoint::query()->orderByDesc('min_score')->get()->all();

        // Leave types + fee components.
        $leaveTypeRows = [
            ['name' => 'Senior Staff', 'max_leave_days' => 30],
            ['name' => 'Junior Staff', 'max_leave_days' => 21],
            ['name' => 'Principal Officers', 'max_leave_days' => 42],
        ];
        $this->chunkInsert('staff_leave_types', $this->stamp($leaveTypeRows));
        $leaveTypes = DB::table('staff_leave_types')->orderBy('id')->get();

        // Teacher portal permission map (mirrors the migration defaults; the
        // truncate above wipes them). Without the 'lecturer' row, every
        // teacher resolves zero permissions and the sidebar collapses to
        // Dashboard + Profile only.
        $this->chunkInsert('teacher_portal_roles', $this->stamp([
            [
                'name' => 'lecturer',
                'display_name' => 'Lecturer',
                'permissions' => json_encode(['courses', 'students', 'assessments', 'communication']),
                'description' => 'Standard teaching faculty with full access to portal features.',
            ],
            [
                'name' => 'coordinator',
                'display_name' => 'Programme Coordinator',
                'permissions' => json_encode(['courses', 'students', 'assessments']),
                'description' => 'Academic coordinator managing courses, student attendance, and grades.',
            ],
            [
                'name' => 'tutor',
                'display_name' => 'Tutor / Teaching Assistant',
                'permissions' => json_encode(['courses', 'students']),
                'description' => 'Tutor with access to courses and student records but no grade entry.',
            ],
        ]));

        $componentDefs = [
            ['Tuition Fee', true], ['Library Fee', true], ['Laboratory Fee', true],
            ['Medical Fee', true], ['Sports Fee', true], ['Examination Fee', true],
        ];
        $componentRows = [];
        foreach ($componentDefs as $cd) {
            $componentRows[] = ['name' => $cd[0], 'default_percentage' => 0.00, 'is_active' => true, 'is_system' => $cd[1]];
        }
        $this->chunkInsert('fee_components', $this->stamp($componentRows));
        $components = DB::table('fee_components')->orderBy('id')->get();

        // ==============================================================
        // 2. CAST — Ghanaian name pools only; one shared password hash
        // ==============================================================
        $maleFirst = ['Kofi', 'Kwame', 'Yaw', 'Kwabena', 'Kwadwo', 'Fiifi', 'Ekow', 'Ato', 'Ebo', 'Kweku', 'Selasi', 'Edem', 'Mawuli', 'Komla', 'Elikem', 'Nana', 'Papa', 'Sena'];
        $femaleFirst = ['Ama', 'Abena', 'Akosua', 'Adwoa', 'Efya', 'Esi', 'Araba', 'Ewurama', 'Maame', 'Selasie', 'Dzifa', 'Kafui', 'Abla', 'Dede', 'Naa', 'Mamle'];
        $last = ['Mensah', 'Owusu', 'Asante', 'Osei', 'Boateng', 'Darko', 'Appiah', 'Frimpong', 'Agyemang', 'Ofori', 'Amankwah', 'Sarpong', 'Adjei', 'Ankrah', 'Lamptey', 'Tetteh', 'Quaye', 'Ayittey', 'Dadzie', 'Essien', 'Baiden', 'Hagan', 'Koomson', 'Sackey', 'Tawiah', 'Woode', 'Yankey', 'Nunoo', 'Arthur', 'Cudjoe'];

        $userRows = [];
        $addUser = function (string $name, string $username, string $email, string $type, ?int $leaveTypeId) use (&$userRows, $passwordHash, $now): void {
            $userRows[] = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $now->toDateTimeString(),
                'type' => $type,
                'staff_leave_type_id' => $leaveTypeId,
                'password' => $passwordHash,
                'user_secret' => $this->token(8),
                'active' => true,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        };

        $seniorId = $leaveTypes[0]->id;
        $juniorId = $leaveTypes[1]->id;
        $principalLeaveId = $leaveTypes[2]->id;

        // [display name, username, email, role name|null(teacher/student), leave type, position title]
        $castDefs = [
            ['Nana Akwasi Bonsu', 'owner_demo', 'admin@demo.com', 'owner', $principalLeaveId, 'Main System Administrator'],
            ['Efya Hammond', 'sysadmin_demo', 'sysadmin@demo.com', 'system_admin', $seniorId, 'System Administrator'],
            ['Kwadwo Frimpong', 'hod_cs_demo', 'hod.cs@demo.com', 'hod', $seniorId, 'Head, Computer Science'],
            ['Abena Sarpong', 'hod_ee_demo', 'hod.ee@demo.com', 'hod', $seniorId, 'Head, Electrical Engineering'],
            ['Yaw Amankwah', 'hod_acc_demo', 'hod.acc@demo.com', 'hod', $seniorId, 'Head, Accounting & Finance'],
            ['Maame Dede Quaye', 'hod_cms_demo', 'hod.cms@demo.com', 'hod', $seniorId, 'Head, Communication Studies'],
            ['Fiifi Cudjoe', 'vp_demo', 'vp@demo.com', 'vice_principal', $principalLeaveId, 'Vice Principal'],
            ['Araba Essien', 'dean_demo', 'dean@demo.com', 'dean_of_students', $seniorId, 'Dean of Student Affairs'],
            ['Selasi Tetteh', 'registrar_demo', 'registrar@demo.com', 'registrar', $seniorId, 'Academic Registrar'],
            ['Deladem Ayittey', 'admissions_demo', 'admissions@demo.com', 'admissions_officer', $juniorId, 'Admissions Officer'],
            ['Ebo Dadzie', 'exams_demo', 'exams@demo.com', 'exams_officer', $seniorId, 'Examinations Officer'],
            ['Kafui Nunoo', 'qa_demo', 'qa@demo.com', 'quality_assurance_officer', $seniorId, 'Quality Assurance Officer'],
            ['Akosua Woode', 'accountant_demo', 'accountant@demo.com', 'accountant', $juniorId, 'Accountant'],
            ['Kwabena Yankey', 'finance_demo', 'finance@demo.com', 'finance_officer', $seniorId, 'Finance Officer'],
            ['Ato Arthur', 'auditor_demo', 'auditor@demo.com', 'internal_auditor', $seniorId, 'Internal Auditor'],
            ['Adwoa Lamptey', 'secretary_demo', 'secretary@demo.com', 'secretary', $juniorId, 'Department Secretary'],
            ['Naa Sackey', 'hr_demo', 'hr@demo.com', 'human_resource_manager', $seniorId, 'Human Resource Manager'],
            ['Elikem Tawiah', 'pro_demo', 'pro@demo.com', 'public_relations_officer', $juniorId, 'Public Relations Officer'],
            ['Esi Baiden', 'librarian_demo', 'librarian@demo.com', 'librarian', $juniorId, 'College Librarian'],
            ['Komla Hagan', 'procurement_demo', 'procurement@demo.com', 'procurement_officer', $juniorId, 'Procurement Officer'],
        ];
        foreach ($castDefs as $c) {
            $addUser($c[0], $c[1], $c[2], 'admin', $c[4]);
        }
        $this->chunkInsert('users', $userRows);
        $userByEmail = User::query()->whereIn('email', array_column($castDefs, 2))->get()->keyBy('email');

        $owner = $userByEmail['admin@demo.com'];
        $sysadmin = $userByEmail['sysadmin@demo.com'];
        $hodByDept = [
            $departments[0]->id => $userByEmail['hod.cs@demo.com'],
            $departments[1]->id => $userByEmail['hod.ee@demo.com'],
            $departments[2]->id => $userByEmail['hod.acc@demo.com'],
            $departments[3]->id => $userByEmail['hod.cms@demo.com'],
        ];
        $vp = $userByEmail['vp@demo.com'];
        $dean = $userByEmail['dean@demo.com'];
        $registrar = $userByEmail['registrar@demo.com'];
        $admissions = $userByEmail['admissions@demo.com'];
        $exams = $userByEmail['exams@demo.com'];
        $qa = $userByEmail['qa@demo.com'];
        $accountant = $userByEmail['accountant@demo.com'];
        $secretary = $userByEmail['secretary@demo.com'];
        $hr = $userByEmail['hr@demo.com'];
        $pro = $userByEmail['pro@demo.com'];

        // Admins rows (secretary carries no dept/faculty -> sender entity 'user').
        $adminRows = [];
        foreach ($castDefs as $c) {
            $u = $userByEmail[$c[2]];
            [$other, $lastName] = $this->splitName($c[0]);
            $deptId = null;
            $facId = null;
            if (in_array($c[3], ['hod'], true)) {
                $deptId = array_search($u->id, array_map(fn ($x): int => $x->id, $hodByDept), true) !== false
                    ? array_search($u->id, array_map(fn ($x): int => $x->id, $hodByDept), true)
                    : null;
            } elseif (in_array($c[3], ['registrar', 'admissions_officer', 'exams_officer', 'accountant', 'secretary'], true)) {
                $deptId = $departments[0]->id;
                $facId = $faculties[0]->id;
            } else {
                $facId = $faculties[0]->id;
            }
            $adminRows[] = [
                'user_id' => $u->id,
                'lastname' => $lastName,
                'othernames' => $other,
                'phone_number' => $this->ghanaPhone(),
                'gender' => $this->pickGender($c[0]),
                'position_title' => $c[5],
                'department_id' => $deptId,
                'faculty_id' => $facId,
                'status' => 'active',
                'date_of_appointment' => $prevStart->copy()->subYears(2)->toDateString(),
                'created_by' => $owner->id,
                'ghana_card' => $this->ghanaCard(),
                'type' => $roleId($c[3]),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('admins', $adminRows);

        // departments.hod drives dept-leave review routing.
        foreach ($hodByDept as $deptId => $hodUser) {
            DB::table('departments')->where('id', $deptId)->update(['hod' => $hodUser->id]);
        }

        // Teachers: 12 lecturers across departments (canonical first).
        $teacherSpecs = [
            ['Ama Serwaa', 'teacher@demo.com', 'tch_ama_serwaa', 0, 'Senior Lecturer', 'PhD Computer Science'],
            ['Kofi Boateng', 'kofi.boateng@demo.com', 'tch_kofi_boateng', 0, 'Lecturer', 'MPhil Computer Science'],
            ['Efya Asante', 'efya.asante@demo.com', 'tch_efya_asante', 0, 'Assistant Lecturer', 'MSc Information Technology'],
            ['Yaw Osei', 'yaw.osei@demo.com', 'tch_yaw_osei', 1, 'Senior Lecturer', 'PhD Electrical Engineering'],
            ['Kwame Darko', 'kwame.darko@demo.com', 'tch_kwame_darko', 1, 'Lecturer', 'MSc Power Systems'],
            ['Abena Frimpong', 'abena.frimpong@demo.com', 'tch_abena_frimpong', 2, 'Senior Lecturer', 'PhD Accounting'],
            ['Kwadwo Appiah', 'kwadwo.appiah@demo.com', 'tch_kwadwo_appiah', 2, 'Lecturer', 'MBA Finance'],
            ['Adwoa Mensah', 'adwoa.mensah@demo.com', 'tch_adwoa_mensah', 3, 'Lecturer', 'MA Communication Studies'],
            ['Fiifi Owusu', 'fiifi.owusu@demo.com', 'tch_fiifi_owusu', 3, 'Assistant Lecturer', 'BA Media Production'],
            ['Selasie Adjei', 'selasie.adjei@demo.com', 'tch_selasie_adjei', 0, 'Lecturer', 'MSc Software Engineering'],
            ['Mawuli Ankrah', 'mawuli.ankrah@demo.com', 'tch_mawuli_ankrah', 2, 'Lecturer', 'MCom Taxation'],
            ['Dzifa Quarshie', 'dzifa.quarshie@demo.com', 'tch_dzifa_quarshie', 1, 'Assistant Lecturer', 'BSc Electrical Engineering'],
        ];
        $teacherUserRows = [];
        foreach ($teacherSpecs as $t) {
            $teacherUserRows[] = [
                'name' => $t[0],
                'username' => $t[2],
                'email' => $t[1],
                'email_verified_at' => $now->toDateTimeString(),
                'type' => 'teacher',
                'staff_leave_type_id' => $seniorId,
                'password' => $passwordHash,
                'user_secret' => $this->token(8),
                'active' => true,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('users', $teacherUserRows);
        $teacherUsers = User::query()->where('type', 'teacher')->orderBy('id')->get()->all();

        $teacherRows = [];
        foreach ($teacherUsers as $i => $tu) {
            [$other, $lastName] = $this->splitName($teacherSpecs[$i][0]);
            $teacherRows[] = [
                'user_id' => $tu->id,
                'lastname' => $lastName,
                'othernames' => $other,
                'title' => $i === 0 ? 'Dr.' : $this->pick(['Dr.', 'Mr.', 'Mrs.', 'Ms.', 'Prof.']),
                'ghana_card' => $this->ghanaCard(),
                'profile_pic' => null,
                'gender' => in_array(explode(' ', $teacherSpecs[$i][0])[0], $femaleFirst, true) ? 'female' : 'male',
                'date_of_birth' => $today->copy()->subYears(32 + ($i % 18))->toDateString(),
                'nationality' => 'Ghanaian',
                'contact_address' => 'Plot '.(10 + $i).', Ring Road East, Accra',
                'phone_number' => $this->ghanaPhone(),
                'staff_id' => 'TCH-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'department_id' => $departments[$teacherSpecs[$i][3]]->id,
                'rank' => $teacherSpecs[$i][4],
                'qualification' => $teacherSpecs[$i][5],
                'specialization' => 'Academic instruction & research',
                'employment_type' => 'Full-time',
                'years_experience' => 3 + ($i % 15),
                'emergency_name' => $this->pick($maleFirst).' '.$this->pick($last),
                'emergency_phone' => $this->ghanaPhone(),
                'date_of_appointment' => $prevStart->copy()->subYear()->toDateString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
                'password_reset_required' => false,
                'is_onboarded' => 1,
            ];
        }
        $this->chunkInsert('teachers', $teacherRows);
        $teachers = Teacher::query()->orderBy('id')->get()->all();
        $teacherByUserId = [];
        foreach ($teachers as $t) {
            $teacherByUserId[$t->user_id] = $t;
        }

        // Teacher roles + course assignments (round-robin across departments).
        $courses = Course::query()->orderBy('id')->get()->all();
        $roleRows = [];
        $assignRows = [];
        $courseTeacherRows = [];
        $byDept = [];
        foreach ($teachers as $i => $t) {
            $byDept[$t->department_id][] = $t;
            $roleRows[] = [
                'teacher_id' => $t->id,
                'role' => 'lecturer',
                'program_id' => $programs[$i % count($programs)]->id,
                'description' => 'Assigned during demo setup.',
                'assigned_by' => $owner->id,
                'assigned_date' => $prevStart->toDateString(),
                'status' => 'active',
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $teacherIds = array_map(fn ($t): int => $t->id, $teachers);
        foreach ($courses as $ci => $course) {
            $deptTeachers = $byDept[$course->program->department_id] ?? $teachers;
            $teacher = $deptTeachers[$ci % count($deptTeachers)];
            $level = ((int) $course->year_level) * 100;
            $assignRows[] = [
                'teacher_id' => $teacher->id,
                'program_id' => $course->program_id,
                'level' => $level,
                'course_id' => $course->id,
                'session_id' => $curSession->id,
                'assigned_by' => $owner->id,
                'assigned_date' => $prevStart->toDateString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
            $courseTeacherRows[] = [
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'program_level' => (string) $level,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
            DB::table('courses')->where('id', $course->id)->update(['teacher_id' => $teacher->id]);
        }
        $this->chunkInsert('teacher_roles', $roleRows);
        $this->chunkInsert('teacher_assignments', $assignRows);
        $this->chunkInsert('teacher_courses', $courseTeacherRows);
        unset($teacherIds);
        // Re-fetch: teacher_id backfill above postdates the first read.
        $courses = Course::query()->orderBy('id')->get()->all();

        // ==============================================================
        // 3. CONTINUING STUDENTS (pre-promotion y1-y3) + previous year
        // ==============================================================
        // Per-program cohorts: 4yr -> y1:10 y2:8 y3:8; 2yr -> y1:10 y2:8.
        $cohortPlan = [];
        foreach ($programs as $program) {
            $cohortPlan[$program->id] = ((int) $program->program_length) === 4
                ? ['100' => 10, '200' => 8, '300' => 8]
                : ['100' => 10, '200' => 8];
        }

        $studentUserRows = [];
        $studentSpecs = []; // deterministic generation plan
        $gIdx = 0;
        $admitSeq = 0;
        foreach ($programs as $program) {
            foreach ($cohortPlan[$program->id] as $level => $count) {
                for ($k = 0; $k < $count; $k++) {
                    $gIdx++;
                    $admitSeq++;
                    $isFemale = ($gIdx % 2) === 0;
                    $first = $isFemale ? $femaleFirst[$gIdx % count($femaleFirst)] : $maleFirst[$gIdx % count($maleFirst)];
                    $surname = $last[($gIdx * 7) % count($last)];
                    $email = strtolower($first).'.'.strtolower($surname).$gIdx.'@demo.com';
                    $studentUserRows[] = [
                        'name' => $first.' '.$surname,
                        'username' => strtolower(substr($first, 0, 1)).'.'.strtolower($surname).$gIdx,
                        'email' => $email,
                        'email_verified_at' => $now->toDateTimeString(),
                        'type' => 'student',
                        'staff_leave_type_id' => null,
                        'password' => $passwordHash,
                        'user_secret' => $this->token(8),
                        'active' => true,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                    // Fee flag stamped AT GENERATION: covered / part / full.
                    $flag = ['covered', 'part', 'full'][$gIdx % 3];
                    $studentSpecs[] = [
                        'email' => $email,
                        'first' => $first,
                        'surname' => $surname,
                        'female' => $isFemale,
                        'program' => $program,
                        'preLevel' => $level,
                        'feeFlag' => $flag,
                        'applicantNo' => 'ADM-'.$prevStart->format('Y').'-'.str_pad((string) $admitSeq, 4, '0', STR_PAD_LEFT),
                        'seq' => $gIdx,
                    ];
                }
            }
        }
        // Canonical tour login: y2 Computer Science, fully paid.
        $studentSpecs[10]['email'] = 'student@demo.com';
        $studentSpecs[10]['feeFlag'] = 'full';
        $studentUserRows[10]['email'] = 'student@demo.com';
        $studentUserRows[10]['username'] = 'y.mensah';
        $studentUserRows[10]['name'] = 'Yaw Mensah';
        $studentSpecs[10]['first'] = 'Yaw';
        $studentSpecs[10]['surname'] = 'Mensah';
        $this->chunkInsert('users', $studentUserRows);
        $studentUsersByEmail = User::query()->where('type', 'student')->get()->keyBy('email');

        // Student rows: historical final state (activated long ago): official-style
        // index (school+admitYY+dept+seq), admission_index = applicant number.
        $studentRows = [];
        $officialSeq = 0;
        foreach ($studentSpecs as $spec) {
            $officialSeq++;
            $program = $spec['program'];
            $admitYY = $prevStart->copy()->subYears(((int) $spec['preLevel'] / 100) - 1)->format('y');
            $official = str_pad((string) $school->id, 2, '0', STR_PAD_LEFT)
                .$admitYY
                .str_pad((string) $program->department_id, 2, '0', STR_PAD_LEFT)
                .str_pad((string) (1000 + $officialSeq), 4, '0', STR_PAD_LEFT);
            $u = $studentUsersByEmail[$spec['email']];
            $studentRows[] = [
                'user_id' => $u->id,
                'index_number' => $official,
                'admission_index' => $spec['applicantNo'],
                'lastname' => $spec['surname'],
                'firstname' => $spec['first'],
                'othernames' => null,
                'department_id' => $program->department_id,
                'program_id' => $program->id,
                'date_of_birth' => $today->copy()->subYears(19 + ($spec['seq'] % 6))->toDateString(),
                'gender' => $spec['female'] ? 'female' : 'male',
                'nationality' => 'Ghanaian',
                'religion' => $this->pick(['Christian', 'Muslim', 'Christian', 'Traditionalist']),
                // NOTE: $spec['preLevel'] arrives as int (PHP casts numeric
                // string array keys); the enum column needs the string form.
                'current_year' => (string) $spec['preLevel'],
                'contact_address' => 'House '.(1 + ($spec['seq'] % 40)).', '.($this->pick(['Accra', 'Kumasi', 'Takoradi', 'Tamale', 'Ho', 'Cape Coast'])),
                'phone_number' => $this->ghanaPhone(),
                'admission_date' => $prevStart->toDateString(),
                'hall_id' => $hallIds[$spec['seq'] % count($hallIds)],
                'profile_pic' => 'images/auth/login-office.jpeg',
                'is_new' => false,
                'approved' => true,
                'graduated' => false,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('students', $studentRows);
        $students = Student::query()->orderBy('id')->get()->all();
        $studentByUserId = [];
        foreach ($students as $s) {
            $studentByUserId[$s->user_id] = $s;
        }

        // Guardians (guardian-complete for every approved student).
        $guardianRows = [];
        foreach ($studentSpecs as $spec) {
            $s = $studentByUserId[$studentUsersByEmail[$spec['email']]->id];
            $guardianRows[] = [
                'student_id' => $s->id,
                'name' => $this->pick($maleFirst).' '.$spec['surname'],
                'relationship' => $this->pick(['Father', 'Mother', 'Guardian', 'Mother', 'Father']),
                'address' => $s->contact_address,
                'phone_number' => $this->ghanaPhone(),
                'email' => 'guardian.'.$spec['seq'].'@demo.com',
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('parent_guardians', $guardianRows);

        // Medical history (previous year for all continuing).
        $medicalRows = [];
        foreach ($students as $i => $s) {
            if ($i % 4 === 3) {
                continue; // a quarter with no declarations on file
            }
            $medicalRows[] = [
                'student_id' => $s->id,
                'academic_session_id' => $prevSession->id,
                'recorded_by' => $vp->id,
                'medical_conditions' => $i % 5 === 0 ? 'Mild asthma' : 'None',
                'allergies' => $i % 6 === 0 ? 'Peanuts' : 'None',
                'medications' => $i % 5 === 0 ? 'Inhaler' : 'None',
                'immunization_records' => 'Yellow Fever, COVID-19 (fully vaccinated)',
                'emergency_contacts' => $this->pick($maleFirst).' '.$s->lastname.' - '.$this->ghanaPhone(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('medical_histories', $medicalRows);

        // ==============================================================
        // 4. FEES — previous year structures, deterministic base figures
        // ==============================================================
        $baseExtras = [ // [library, lab(2yr/4yr EE uplift), medical, sports, exam]
            'Library Fee' => 50.00, 'Laboratory Fee' => 80.00, 'Medical Fee' => 40.00,
            'Sports Fee' => 30.00, 'Examination Fee' => 60.00,
        ];
        $compIdByName = [];
        foreach ($components as $c) {
            $compIdByName[$c->name] = $c->id;
        }
        $structRows = [];
        $structKey = []; // "programId:level:sessionId" => FeeStructure (filled after insert)
        foreach ([$prevSession, $curSession] as $sess) {
            $isCurrent = $sess->id === $curSession->id;
            foreach ($programs as $program) {
                $maxLevel = ((int) $program->program_length) * 100;
                for ($lvl = 100; $lvl <= $maxLevel; $lvl += 100) {
                    $tuition = (float) $program->cost;
                    $lab = $program->id === $programs[2]->id ? 150.00 : $baseExtras['Laboratory Fee'];
                    // Current year = previous x (1 + u/100), u = seeded 0-5 draw.
                    $u = $isCurrent ? mt_rand(0, 5) : 0;
                    $scale = 1 + ($u / 100);
                    $amounts = [
                        'Tuition Fee' => round($tuition * $scale, 2),
                        'Library Fee' => round($baseExtras['Library Fee'] * $scale, 2),
                        'Laboratory Fee' => round($lab * $scale, 2),
                        'Medical Fee' => round($baseExtras['Medical Fee'] * $scale, 2),
                        'Sports Fee' => round($baseExtras['Sports Fee'] * $scale, 2),
                        'Examination Fee' => round($baseExtras['Examination Fee'] * $scale, 2),
                    ];
                    $structRows[] = [
                        'program_id' => $program->id,
                        'level' => $lvl,
                        'session_id' => $sess->id,
                        'semester_id' => null,
                        'tuition_fee' => $amounts['Tuition Fee'],
                        'library_fee' => $amounts['Library Fee'],
                        'lab_fee' => $amounts['Laboratory Fee'],
                        'medical_fee' => $amounts['Medical Fee'],
                        'sports_fee' => $amounts['Sports Fee'],
                        'examination_fee' => $amounts['Examination Fee'],
                        'total_amount' => round(array_sum($amounts), 2),
                        'created_by' => $owner->id,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                }
            }
        }
        $this->chunkInsert('fee_structures', $structRows);
        $structures = FeeStructure::query()->get()->all();
        $structByKey = [];
        foreach ($structures as $fs) {
            $structByKey[$fs->program_id.':'.$fs->level.':'.$fs->session_id] = $fs;
        }
        $itemRows = [];
        foreach ($structures as $fs) {
            $map = [
                'Tuition Fee' => $fs->tuition_fee, 'Library Fee' => $fs->library_fee,
                'Laboratory Fee' => $fs->lab_fee, 'Medical Fee' => $fs->medical_fee,
                'Sports Fee' => $fs->sports_fee, 'Examination Fee' => $fs->examination_fee,
            ];
            foreach ($map as $name => $amt) {
                $itemRows[] = [
                    'fee_structure_id' => $fs->id,
                    'fee_component_id' => $compIdByName[$name],
                    'amount' => $amt,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('fee_structure_items', $itemRows);

        // Scholarships (posted by the accountant).
        $scholarshipRows = [
            ['Presidential Merit Award', 'scholarship', 1200.00, 8, 'tuition_only', null, 'Tuition award for top of class.', 'active'],
            ['Needy Student Bursary', 'scholarship', 1500.00, 4, 'full', null, 'Full-cost bursary for needy,Ghanaian students.', 'active'],
            ['Hostel Relief Grant', 'grant', 600.00, 2, 'hostel_only', null, 'Accommodation support grant.', 'active'],
        ];
        $schRows = [];
        foreach ($scholarshipRows as $sr) {
            $schRows[] = [
                'name' => $sr[0], 'type' => $sr[1], 'amount' => $sr[2], 'duration_semesters' => $sr[3],
                'expiry_date' => $curEnd->toDateString(), 'coverage_type' => $sr[4], 'coverage_components' => $sr[5],
                'description' => $sr[6], 'status' => $sr[7], 'created_by' => $accountant->id,
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('scholarships', $schRows);
        $scholarships = Scholarship::query()->orderBy('id')->get()->all();

        // Scholarship coverage for continuing students flagged 'covered'
        // (previous-year award; current-year award for a subset).
        $recipientRows = [];
        foreach ($studentSpecs as $spec) {
            if ($spec['feeFlag'] !== 'covered') {
                continue;
            }
            $s = $studentByUserId[$studentUsersByEmail[$spec['email']]->id];
            $sch = $scholarships[$spec['seq'] % count($scholarships)];
            $recipientRows[] = [
                'scholarship_id' => $sch->id,
                'student_id' => $s->id,
                'academic_session_id' => $prevSession->id,
                'amount_awarded' => $sch->amount,
                'award_date' => $this->dateIn($prevStart, $prevEnd, $spec['seq'])->toDateString(),
                'status' => 'approved',
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
            if ($spec['seq'] % 2 === 0) {
                $recipientRows[] = [
                    'scholarship_id' => $sch->id,
                    'student_id' => $s->id,
                    'academic_session_id' => $curSession->id,
                    'amount_awarded' => $sch->amount,
                    'award_date' => $this->dateIn($curStart, $today, $spec['seq'] + 3)->toDateString(),
                    'status' => 'approved',
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('scholarship_recipients', $recipientRows);

        // Previous-year payments driven by the stamped flags.
        $paymentRows = [];
        $paySeq = 0;
        foreach ($studentSpecs as $spec) {
            if ($spec['feeFlag'] === 'covered') {
                continue;
            }
            $s = $studentByUserId[$studentUsersByEmail[$spec['email']]->id];
            $fs = $structByKey[$spec['program']->id.':'.$spec['preLevel'].':'.$prevSession->id];
            $total = (float) $fs->total_amount;
            $paid = $spec['feeFlag'] === 'full' ? $total : round($total / 2, 2);
            $paySeq++;
            $paymentRows[] = [
                'student_id' => $s->id,
                'fee_structure_id' => $fs->id,
                'amount_paid' => $paid,
                'payment_method' => $this->pick(['Bank Transfer', 'Mobile Money', 'Bank Transfer', 'Cheque']),
                'payment_date' => $this->dateIn($prevStart, $prevEnd, $spec['seq'], 11)->toDateString(),
                'reference_number' => 'PAY-'.$prevStart->format('Y').'-'.str_pad((string) $paySeq, 5, '0', STR_PAD_LEFT),
                'status' => 'completed',
                'received_by' => $accountant->id,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('payments', $paymentRows);

        $feeService = app(FeeCalculationService::class);
        foreach ($students as $s) {
            $feeService->syncFeePaymentLedger($s, $prevSession);
        }

        // ==============================================================
        // 5. PREVIOUS-YEAR RESULTS (all approved; fails seed retakes)
        // ==============================================================
        $coursesByProgramLevel = [];
        foreach ($courses as $course) {
            $coursesByProgramLevel[$course->program_id][(string) ((int) $course->year_level * 100)][] = $course;
        }
        $teacherById = [];
        foreach ($teachers as $t) {
            $teacherById[$t->id] = $t;
        }

        $gradeFor = function (float $score) use ($gradeBands): array {
            foreach ($gradeBands as $gp) {
                if ($score >= (float) $gp->min_score && $score <= (float) $gp->max_score) {
                    return ['grade' => $gp->grade, 'points' => (float) $gp->points];
                }
            }

            return ['grade' => 'F', 'points' => 0.0];
        };

        // Deterministic score: hash of (studentSeq, courseId) -> fail band or pass.
        $scoreFor = function (int $studentSeq, int $courseId, int $tweak = 0): array {
            $h = ($studentSeq * 37 + $courseId * 17 + $tweak) % 100;
            $fail = $h < 15;
            $att = 6 + (($studentSeq + $courseId) % 5);
            $mid = 12 + (($studentSeq * 3 + $courseId) % 9);
            $proj = 6 + (($studentSeq + $courseId * 2) % 5);
            $exam = $fail ? (15 + ($h % 15)) : (35 + (($studentSeq * 5 + $courseId * 3 + $tweak) % 24));
            $exam = min($exam, 60);
            $total = $att + $mid + $proj + $exam;

            return [$att, $mid, $proj, $exam, (float) $total];
        };

        $slipRows = [];
        $gradeRows = [];
        $resultRows = [];
        $slipSeq = 0;
        $prevFails = []; // studentId => [courseIds failed in previous year]
        // One approved slip per (course, previous session).
        foreach ($courses as $course) {
            $courseTeacher = $teacherById[$course->teacher_id];
            $semester = str_contains((string) $course->course_semester, '2') ? 2 : 1;
            $slipSeq++;
            $slipRows[] = [
                'slip_number' => 'SLIP-'.$prevStart->format('Y').'-'.str_pad((string) $slipSeq, 6, '0', STR_PAD_LEFT),
                'teacher_id' => $courseTeacher->id,
                'program_id' => $course->program_id,
                'course_id' => $course->id,
                'academic_session_id' => $prevSession->id,
                'level' => (string) ((int) $course->year_level * 100),
                'semester' => $semester,
                'status' => 'approved',
                'review_comments' => null,
                'approved_by' => $exams->id,
                'approved_at' => $prevEnd->copy()->subDays(20)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('result_slips', $slipRows);
        $slips = ResultSlip::query()->get()->all();
        $slipByCourseSession = [];
        foreach ($slips as $slip) {
            $slipByCourseSession[$slip->course_id.':'.$slip->academic_session_id] = $slip;
        }

        $resSeq = 0;
        foreach ($studentSpecs as $spec) {
            $s = $studentByUserId[$studentUsersByEmail[$spec['email']]->id];
            $levels = [];
            for ($l = 100; $l <= (int) $spec['preLevel']; $l += 100) {
                $levels[] = (string) $l;
            }
            foreach ($levels as $lvl) {
                foreach ($coursesByProgramLevel[$spec['program']->id][$lvl] ?? [] as $course) {
                    [$att, $mid, $proj, $exam, $total] = $scoreFor($spec['seq'], $course->id);
                    $gd = $gradeFor($total);
                    $slip = $slipByCourseSession[$course->id.':'.$prevSession->id];
                    $courseTeacher = $teacherById[$course->teacher_id];
                    $gradeRows[] = [
                        'result_slip_id' => $slip->id,
                        'student_id' => $s->id,
                        'teacher_id' => $courseTeacher->id,
                        'attendance_score' => $att,
                        'midsem_score' => $mid,
                        'project_score' => $proj,
                        'class_score' => $att + $mid + $proj,
                        'exam_score' => $exam,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                    $resSeq++;
                    $resultRows[] = [
                        'student_id' => $s->id,
                        'course_id' => $course->id,
                        'academic_session_id' => $prevSession->id,
                        'score' => $total,
                        'grade' => $gd['grade'],
                        'grade_points' => $gd['points'],
                        'entered_by' => $courseTeacher->user_id,
                        'entered_date' => $prevEnd->copy()->subDays(30)->toDateString(),
                        'result_token' => 'RES-'.$prevStart->format('Y').'-'.str_pad((string) $resSeq, 6, '0', STR_PAD_LEFT),
                        'teacher_id' => $courseTeacher->id,
                        'result_slip_id' => $slip->id,
                        'admin_amended' => false,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                    if ($gd['grade'] === 'F') {
                        $prevFails[$s->id][] = $course->id;
                    }
                }
            }
        }
        $this->chunkInsert('grades', $gradeRows);
        $this->chunkInsert('results', $resultRows);

        // AcademicInformation (GPA) for the previous year.
        $this->seedAcademicInformation($prevSession);

        // ==============================================================
        // 6. PROMOTION — the real service bumps years (never hand-set)
        // ==============================================================
        if (! app(AutoPromotionService::class)->run()) {
            throw new \RuntimeException('AutoPromotionService failed during seeding.');
        }
        $promotedCount = DB::table('promotions')->where('academic_session_id', $curSession->id)->count();
        if ($promotedCount === 0) {
            throw new \RuntimeException('AutoPromotionService promoted nobody; check session dates.');
        }

        // ==============================================================
        // 7. NEW Y1 INTAKE — real registration path, approvals, activation
        // ==============================================================
        $photoSource = public_path('images/auth/login-office.jpeg');
        $saveAdmission = app(SaveStudentAdmissionProfileAction::class);
        $activate = app(ActivateStudentDashboardAction::class);
        $assertCap = app(AssertStudentApprovalAllowedByLicence::class);

        $intakeCount = 30;
        $intakeApprovedTarget = 20;
        $intakeSpecs = [];
        $programIds = array_map(fn ($p): int => $p->id, $programs);
        for ($i = 1; $i <= $intakeCount; $i++) {
            $isFemale = ($i % 2) === 0;
            $first = $isFemale ? $femaleFirst[($i * 3) % count($femaleFirst)] : $maleFirst[($i * 3) % count($maleFirst)];
            $surname = $last[($i * 11) % count($last)];
            $program = $programs[$i % count($programs)];
            $intakeSpecs[] = [
                'first' => $first, 'surname' => $surname, 'female' => $isFemale,
                'program' => $program,
                'applicantNo' => 'ADM-'.$curStart->format('Y').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'email' => strtolower($first).'.'.strtolower($surname).'.n'.$i.'@demo.com',
                'username' => strtolower(substr($first, 0, 1)).'.'.strtolower($surname).'.n'.$i,
            ];
        }

        $newApproved = 0;
        $intakeStudents = [];
        $intakeIds = [];
        foreach ($intakeSpecs as $idx => $spec) {
            $user = User::create([
                'name' => $spec['first'].' '.$spec['surname'],
                'email' => $spec['email'],
                'password' => $passwordHash,
                'type' => 'student',
                'user_secret' => $this->token(8),
                'active' => true,
            ]);
            // Real registration path (profile photo via a temp copy).
            $tmpPhoto = sys_get_temp_dir().'/demo-intake-'.$idx.'.jpg';
            copy($photoSource, $tmpPhoto);
            $upload = new UploadedFile($tmpPhoto, 'photo.jpg', 'image/jpeg', null, true);
            $student = $saveAdmission->create($user, [
                'index_number' => $spec['applicantNo'],
                'lastname' => $spec['surname'],
                'firstname' => $spec['first'],
                'othernames' => null,
                'date_of_birth' => $today->copy()->subYears(19 + ($idx % 4))->toDateString(),
                'nationality' => 'Ghanaian',
                'insurance_number' => null,
                'ghana_card' => $this->ghanaCard(),
                'contact_address' => 'House '.($idx + 1).', '.($this->pick(['Accra', 'Kumasi', 'Ho'])),
                'phone_number' => $this->ghanaPhone(),
                'religion' => 'Christian',
                'denomination' => null,
                'disability_status' => 'no',
                'disability_type' => null,
                'program_id' => $spec['program']->id,
                'hall_id' => $hallIds[$idx % count($hallIds)],
                'gender' => $spec['female'] ? 'female' : 'male',
                'blood_group' => $this->pick(['O+', 'A+', 'B+', 'O+', 'AB+']),
                'username' => $spec['username'],
            ], $upload);

            ParentGuardian::create([
                'student_id' => $student->id,
                'name' => $this->pick($maleFirst).' '.$spec['surname'],
                'relationship' => $this->pick(['Father', 'Mother', 'Guardian']),
                'address' => $student->contact_address,
                'phone_number' => $this->ghanaPhone(),
                'email' => 'guardian.intake.'.$idx.'@demo.com',
            ]);

            // First 20 admitted AND approved (admissions_officer owns approvals):
            // approval field semantics + licence-cap gate, for real.
            if ($idx < $intakeApprovedTarget) {
                $assertCap();
                $student->forceFill([
                    'approved' => true,
                    'admission_index' => $student->index_number,
                    'department_id' => $student->program?->department_id,
                ])->save();
                $activate->execute($student->fresh());
                $newApproved++;
            }
            $fresh = $student->fresh();
            $intakeStudents[] = $fresh;
            $intakeIds[] = $fresh->id;
        }

        // New admits: current-year fees + payments for the approved; ledger sync.
        $intakePaySeq = 90000;
        foreach ($intakeStudents as $ni => $ns) {
            if (! $ns->approved) {
                continue;
            }
            $flag = ['full', 'part', 'covered'][$ni % 3];
            $fs = $structByKey[$ns->program_id.':100:'.$curSession->id];
            if ($flag === 'covered') {
                $sch = $scholarships[$ni % count($scholarships)];
                ScholarshipRecipient::create([
                    'scholarship_id' => $sch->id,
                    'student_id' => $ns->id,
                    'academic_session_id' => $curSession->id,
                    'amount_awarded' => $sch->amount,
                    'award_date' => $this->dateIn($curStart, $today, $ni + 40)->toDateString(),
                    'status' => 'approved',
                ]);
            } else {
                $total = (float) $fs->total_amount;
                $intakePaySeq++;
                Payment::create([
                    'student_id' => $ns->id,
                    'fee_structure_id' => $fs->id,
                    'amount_paid' => $flag === 'full' ? $total : round($total / 2, 2),
                    'payment_method' => $this->pick(['Bank Transfer', 'Mobile Money']),
                    'payment_date' => $this->dateIn($curStart, $today, $ni + 50, 5)->toDateString(),
                    'reference_number' => 'PAY-'.$curStart->format('Y').'-'.str_pad((string) $intakePaySeq, 5, '0', STR_PAD_LEFT),
                    'status' => 'completed',
                    'received_by' => $accountant->id,
                ]);
            }
            $feeService->syncFeePaymentLedger($ns, $curSession);
            // Current-year medical row for new admits.
            MedicalHistory::create([
                'student_id' => $ns->id,
                'academic_session_id' => $curSession->id,
                'recorded_by' => $vp->id,
                'medical_conditions' => 'None',
                'allergies' => 'None',
                'medications' => 'None',
                'immunization_records' => 'Yellow Fever, COVID-19 (fully vaccinated)',
                'emergency_contacts' => $this->pick($maleFirst).' '.$ns->lastname.' - '.$this->ghanaPhone(),
            ]);
        }
        // Current-year ledgers for all continuing students.
        $continuing = Student::query()->where('approved', true)->get()->all();
        foreach ($continuing as $s) {
            if (in_array($s->id, $intakeIds, true)) {
                continue; // intake already synced above
            }
            $feeService->syncFeePaymentLedger($s, $curSession);
            if ($s->id % 3 !== 0) {
                MedicalHistory::create([
                    'student_id' => $s->id,
                    'academic_session_id' => $curSession->id,
                    'recorded_by' => $vp->id,
                    'medical_conditions' => 'None',
                    'allergies' => 'None',
                    'medications' => 'None',
                    'immunization_records' => 'Yellow Fever, COVID-19 (fully vaccinated)',
                    'emergency_contacts' => $this->pick($maleFirst).' '.$s->lastname.' - '.$this->ghanaPhone(),
                ]);
            }
        }

        // ==============================================================
        // 8. CURRENT-YEAR RESULTS (approved + retakes + pending-only-now)
        // ==============================================================
        $freshStudents = Student::query()->orderBy('id')->get()->all();
        $curSlipRows = [];
        foreach ($courses as $course) {
            $courseTeacher = $teacherById[$course->teacher_id];
            $semester = str_contains((string) $course->course_semester, '2') ? 2 : 1;
            $slipSeq++;
            // ~1 in 5 current slips stays pending (grades only, no results).
            $pending = (($course->id + $curSession->id) % 5) === 0;
            $curSlipRows[] = [
                'slip_number' => 'SLIP-'.$curStart->format('Y').'-'.str_pad((string) $slipSeq, 6, '0', STR_PAD_LEFT),
                'teacher_id' => $courseTeacher->id,
                'program_id' => $course->program_id,
                'course_id' => $course->id,
                'academic_session_id' => $curSession->id,
                'level' => (string) ((int) $course->year_level * 100),
                'semester' => $semester,
                'status' => $pending ? 'pending' : 'approved',
                'review_comments' => null,
                'approved_by' => $pending ? null : $exams->id,
                'approved_at' => $pending ? null : $today->copy()->subDays(10)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('result_slips', $curSlipRows);
        $curSlips = ResultSlip::query()->where('academic_session_id', $curSession->id)->get()->all();
        $curSlipByCourse = [];
        foreach ($curSlips as $slip) {
            $curSlipByCourse[$slip->course_id] = $slip;
        }

        $curGradeRows = [];
        $curResultRows = [];
        foreach ($freshStudents as $s) {
            if (! $s->approved) {
                continue; // pending intake has no marks yet
            }
            $levels = [];
            for ($l = 100; $l <= (int) $s->current_year; $l += 100) {
                $levels[] = (string) $l;
            }
            foreach ($levels as $lvl) {
                foreach ($coursesByProgramLevel[$s->program_id][$lvl] ?? [] as $course) {
                    if (in_array($course->id, $prevFails[$s->id] ?? [], true)) {
                        continue; // failed last year: covered by the retake row below
                    }
                    $slip = $curSlipByCourse[$course->id];
                    $courseTeacher = $teacherById[$course->teacher_id];
                    [$att, $mid, $proj, $exam, $total] = $scoreFor($s->id, $course->id, 7);
                    $curGradeRows[] = [
                        'result_slip_id' => $slip->id,
                        'student_id' => $s->id,
                        'teacher_id' => $courseTeacher->id,
                        'attendance_score' => $att,
                        'midsem_score' => $mid,
                        'project_score' => $proj,
                        'class_score' => $att + $mid + $proj,
                        'exam_score' => $exam,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                    if ($slip->status !== 'approved') {
                        continue; // pending slip: grades entered, results await approval
                    }
                    $gd = $gradeFor($total);
                    $resSeq++;
                    $curResultRows[] = [
                        'student_id' => $s->id,
                        'course_id' => $course->id,
                        'academic_session_id' => $curSession->id,
                        'score' => $total,
                        'grade' => $gd['grade'],
                        'grade_points' => $gd['points'],
                        'entered_by' => $courseTeacher->user_id,
                        'entered_date' => $today->copy()->subDays(12)->toDateString(),
                        'result_token' => 'RES-'.$curStart->format('Y').'-'.str_pad((string) $resSeq, 6, '0', STR_PAD_LEFT),
                        'teacher_id' => $courseTeacher->id,
                        'result_slip_id' => $slip->id,
                        'admin_amended' => false,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                }
            }
            // Retakes: previous-year fails re-sat now (approved result).
            foreach ($prevFails[$s->id] ?? [] as $courseId) {
                $course = Course::find($courseId);
                if ($course === null) {
                    continue;
                }
                $slip = $curSlipByCourse[$courseId] ?? null;
                if ($slip === null || $slip->status !== 'approved') {
                    continue;
                }
                $courseTeacher = $teacherById[$course->teacher_id];
                [$att, $mid, $proj, $exam, $total] = $scoreFor($s->id, $courseId, 99);
                $total = max($total, 52.0); // retake passes
                $gd = $gradeFor($total);
                $resSeq++;
                $curResultRows[] = [
                    'student_id' => $s->id,
                    'course_id' => $courseId,
                    'academic_session_id' => $curSession->id,
                    'score' => $total,
                    'grade' => $gd['grade'],
                    'grade_points' => $gd['points'],
                    'entered_by' => $courseTeacher->user_id,
                    'entered_date' => $today->copy()->subDays(9)->toDateString(),
                    'result_token' => 'RES-'.$curStart->format('Y').'-R'.str_pad((string) $resSeq, 5, '0', STR_PAD_LEFT),
                    'teacher_id' => $courseTeacher->id,
                    'result_slip_id' => $slip->id,
                    'admin_amended' => false,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('grades', $curGradeRows);
        $this->chunkInsert('results', $curResultRows);
        $this->seedAcademicInformation($curSession);

        // ==============================================================
        // 9. EVALUATIONS — QA creates; status service opens/closes
        // ==============================================================
        $likert = [
            'The lecturer explains course concepts with clarity.',
            'The lecturer demonstrates mastery of the subject matter.',
            'The lecturer is punctual and regular for classes.',
            'Marking of assignments and exams is fair and timely.',
            'The lecturer is available for consultation outside class.',
            'The quality of course materials and slides is high.',
        ];
        $prevForm = EvaluationForm::create([
            'title' => 'Lecturer Performance Evaluation',
            'academic_year' => $prevName,
            'unique_code' => 'EVAL-'.$prevStart->format('Y').'-A',
            'start_time' => $prevStart->copy()->addMonth()->toDateTimeString(),
            'end_time' => $prevEnd->copy()->subMonths(3)->toDateTimeString(),
            'control_type' => 'auto',
            'is_active' => false,
            'created_by' => $qa->id,
            'last_edited_by' => $qa->id,
        ]);
        $curForm = EvaluationForm::create([
            'title' => 'Lecturer Performance Evaluation',
            'academic_year' => $curName,
            'unique_code' => 'EVAL-'.$curStart->format('Y').'-A',
            'start_time' => $today->copy()->subDays(7)->toDateTimeString(),
            'end_time' => $curEnd->copy()->subMonths(3)->toDateTimeString(),
            'control_type' => 'auto',
            'is_active' => false,
            'created_by' => $qa->id,
            'last_edited_by' => $qa->id,
        ]);
        $questionRows = [];
        foreach ([$prevForm, $curForm] as $form) {
            $order = 0;
            foreach ($likert as $text) {
                $order++;
                $questionRows[] = [
                    'form_id' => $form->id,
                    'question_text' => $text,
                    'question_order' => $order,
                    'rating_type' => 'scale_5',
                    'is_required' => true,
                    'options_json' => null,
                    'created_by' => $qa->id,
                    'last_edited_by' => $qa->id,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
            $order++;
            $questionRows[] = [
                'form_id' => $form->id,
                'question_text' => 'Provide any constructive feedback or comments for this lecturer.',
                'question_order' => $order,
                'rating_type' => 'text_long',
                'is_required' => false,
                'options_json' => null,
                'created_by' => $qa->id,
                'last_edited_by' => $qa->id,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('evaluation_questions', $questionRows);
        if (! app(EvaluationFormStatusService::class)->run()) {
            throw new \RuntimeException('EvaluationFormStatusService failed during seeding.');
        }

        $questionsByForm = [];
        foreach (EvaluationQuestion::query()->orderBy('question_order')->get()->all() as $q) {
            $questionsByForm[$q->form_id][] = $q;
        }
        $ratingPool = [5, 5, 5, 4, 4, 4, 4, 3, 3, 2];
        $commentPool = [
            'Explains difficult topics with great patience and clarity.',
            'Very punctual; slides are well organised and helpful.',
            'Marking was fair, though feedback arrived a week late.',
            'Approachable during consultation hours; solved my project blockers.',
            'Practical examples made the course enjoyable and relevant.',
            'Pace is fast in the second half; more revision would help.',
        ];
        // Teacher of the student's own program (mirrors auto-assignment).
        $programTeacherUserId = [];
        foreach ($programs as $program) {
            $firstCourse = Course::query()->where('program_id', $program->id)->orderBy('id')->first();
            $programTeacherUserId[$program->id] = $teacherById[$firstCourse->teacher_id]->user_id;
        }
        $evaluable = array_values(array_filter($freshStudents, fn ($s): bool => (bool) $s->approved));
        $responseRows = [];
        $detailRows = [];
        $respSeq = 0;
        foreach ([$prevForm, $curForm] as $fi => $form) {
            $isPrev = $fi === 0;
            foreach ($evaluable as $ei => $s) {
                $roll = ($ei * 7 + $form->id) % 10;
                // Previous (closed): submitted only. Current: ~70% submit,
                // ~10% draft, rest skip. Never 100%.
                if ($isPrev ? $roll > 5 : $roll > 7) {
                    continue;
                }
                $submitted = $isPrev || $roll <= 6;
                $respSeq++;
                $submittedAt = $isPrev
                    ? $this->dateIn($prevStart, $prevEnd, $ei + 5)->toDateTimeString()
                    : $this->dateIn($curStart, $today, $ei + 5)->toDateTimeString();
                $responseRows[] = [
                    'form_id' => $form->id,
                    'student_id' => $s->user_id,
                    'teacher_id' => $programTeacherUserId[$s->program_id],
                    'student_department_id' => $s->department_id,
                    'response_code' => 'RESP-'.$form->id.'-'.str_pad((string) $respSeq, 5, '0', STR_PAD_LEFT),
                    'status' => $submitted ? 'submitted' : 'draft',
                    'submitted_at' => $submitted ? $submittedAt : null,
                    'created_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('evaluation_responses', $responseRows);
        $responses = EvaluationResponse::query()->orderBy('id')->get()->all();
        foreach ($responses as $ri => $resp) {
            foreach ($questionsByForm[$resp->form_id] as $qi => $q) {
                if ($resp->status === 'draft' && (($ri + $qi) % 3) === 0) {
                    continue; // partial drafts: skipped questions
                }
                if ($q->rating_type === 'scale_5') {
                    $detailRows[] = [
                        'response_id' => $resp->id,
                        'question_id' => $q->id,
                        'question_text_snapshot' => $q->question_text,
                        'answer_value' => $ratingPool[($ri * 3 + $qi) % count($ratingPool)],
                        'answer_text' => null,
                        'created_at' => $now->toDateTimeString(),
                    ];
                } else {
                    // Comment box: answered by ~2 in 3 respondents.
                    if ((($ri + $qi) % 3) === 0) {
                        continue;
                    }
                    $detailRows[] = [
                        'response_id' => $resp->id,
                        'question_id' => $q->id,
                        'question_text_snapshot' => $q->question_text,
                        'answer_value' => null,
                        'answer_text' => $commentPool[($ri + $qi) % count($commentPool)],
                        'created_at' => $now->toDateTimeString(),
                    ];
                }
            }
        }
        $this->chunkInsert('response_details', $detailRows);

        // ==============================================================
        // 10. MEMOS — secretary drafts; HOD -> Dean/VP chains; PRO news
        // ==============================================================
        $memoFiles = [
            'memos/seed-fee-schedule.txt' => "Second instalment fee schedule per programme level.\nTuition balances are due before mid-semester examinations.\n",
            'memos/seed-matriculation-programme.txt' => "Matriculation ceremony programme outline.\nAll newly admitted students are expected to attend in academic dress.\n",
        ];
        foreach ($memoFiles as $path => $body) {
            Storage::disk('local')->put($path, $body);
        }

        $memoDefs = [
            [
                'title' => 'Payment of Second Instalment Fees - Deadline Reminder',
                'content' => 'All continuing students are reminded that the second instalment of fees for the academic year is due before the mid-semester examinations. Students with outstanding balances should visit the Finance Office.',
                'recipient_type' => 'faculty', 'recipient_entity_id' => $faculties[0]->id, 'recipient_role_id' => null,
                'status' => 'sent', 'signers' => [$hodByDept[$departments[0]->id]->id, $dean->id],
                'attach' => 'memos/seed-fee-schedule.txt', 'topic_day' => 12,
            ],
            [
                'title' => 'Matriculation Ceremony for Newly Admitted Students',
                'content' => 'The matriculation ceremony for newly admitted students comes off at the forecourt of the Administration Block. Deans, HODs and guardians are cordially invited.',
                'recipient_type' => 'faculty', 'recipient_entity_id' => $faculties[1]->id, 'recipient_role_id' => null,
                'status' => 'sent', 'signers' => [$hodByDept[$departments[2]->id]->id, $vp->id],
                'attach' => 'memos/seed-matriculation-programme.txt', 'topic_day' => 20,
            ],
            [
                'title' => 'End-of-Semester Examination Timetable',
                'content' => 'Heads of Department are to submit draft examination timetables for vetting before publication on student notice boards.',
                'recipient_type' => 'department', 'recipient_entity_id' => $departments[0]->id, 'recipient_role_id' => null,
                'status' => 'pending_signature', 'signers' => [$hodByDept[$departments[0]->id]->id],
                'attach' => null, 'topic_day' => 28,
            ],
            [
                'title' => 'Teaching Practice Postings - Second Year Trainees',
                'content' => 'Draft posting schedule for second-year teacher trainees to partner basic schools. Supervisors to confirm availability before dispatch.',
                'recipient_type' => 'department', 'recipient_entity_id' => $departments[1]->id, 'recipient_role_id' => null,
                'status' => 'draft', 'signers' => [$hodByDept[$departments[1]->id]->id, $dean->id],
                'attach' => null, 'topic_day' => 34,
            ],
            [
                'title' => 'Emergency Staff Meeting - Accreditation Visit',
                'content' => 'An emergency meeting of all teaching and non-teaching staff has been scheduled ahead of the accreditation panel visit. Attendance is compulsory.',
                'recipient_type' => 'department', 'recipient_entity_id' => $departments[3]->id, 'recipient_role_id' => null,
                'status' => 'sent', 'signers' => [$hodByDept[$departments[3]->id]->id, $vp->id],
                'attach' => null, 'topic_day' => 40,
            ],
        ];
        $memoDate = fn (int $day): string => $curStart->copy()->addDays($day)->toDateTimeString();
        foreach ($memoDefs as $mi => $md) {
            $memo = Memo::create([
                'title' => $md['title'],
                'content' => $md['content'],
                'sender_id' => $secretary->id,
                'sender_entity_type' => 'user',
                'sender_entity_id' => null,
                'recipient_type' => $md['recipient_type'],
                'recipient_entity_id' => $md['recipient_entity_id'],
                'recipient_role_id' => $md['recipient_role_id'],
                'confidentiality_level' => 'internal',
                'status' => $md['status'],
                'signing_user_id' => $md['status'] === 'pending_signature' ? $md['signers'][0] : null,
                'route_sequentially' => true,
                'cc_recipients' => null,
                'created_at' => $memoDate($md['topic_day']),
                'updated_at' => $memoDate($md['topic_day']),
            ]);
            $signerRows = [];
            foreach ($md['signers'] as $step => $signerId) {
                $signed = $md['status'] === 'sent' || ($md['status'] === 'pending_signature' && $step === 0 && $mi !== 2);
                if ($md['status'] === 'pending_signature') {
                    $signed = false; // awaiting the HOD: nothing signed yet
                }
                $signerRows[] = [
                    'memo_id' => $memo->id,
                    'user_id' => $signerId,
                    'step_number' => $step + 1,
                    'status' => $signed ? 'signed' : 'pending',
                    'signature_path' => null,
                    'remarks' => $signed ? 'Approved and signed.' : null,
                    'signed_at' => $signed ? $memoDate($md['topic_day'] + $step + 1) : null,
                    'created_at' => $memoDate($md['topic_day']),
                    'updated_at' => $memoDate($md['topic_day']),
                ];
                if ($signed) {
                    MemoTracking::create([
                        'memo_id' => $memo->id,
                        'from_entity_type' => 'user',
                        'from_entity_id' => $signerId,
                        'to_entity_type' => $md['recipient_type'],
                        'to_entity_id' => $md['recipient_entity_id'],
                        'forwarded_by' => $signerId,
                        'action' => 'signed',
                        'remarks' => 'Signed in demo seeding.',
                        'created_at' => $memoDate($md['topic_day'] + $step + 1),
                    ]);
                }
            }
            $this->chunkInsert('memo_signatories', $signerRows);
            if ($md['status'] === 'draft') {
                MemoTracking::create([
                    'memo_id' => $memo->id,
                    'from_entity_type' => 'user',
                    'from_entity_id' => $secretary->id,
                    'to_entity_type' => $md['recipient_type'],
                    'to_entity_id' => $md['recipient_entity_id'],
                    'forwarded_by' => $secretary->id,
                    'action' => 'saved',
                    'remarks' => 'Saved as draft.',
                    'created_at' => $memoDate($md['topic_day']),
                ]);
                continue;
            }
            MemoTracking::create([
                'memo_id' => $memo->id,
                'from_entity_type' => 'user',
                'from_entity_id' => $secretary->id,
                'to_entity_type' => $md['recipient_type'],
                'to_entity_id' => $md['recipient_entity_id'],
                'forwarded_by' => $secretary->id,
                'action' => 'sent',
                'remarks' => $md['status'] === 'sent' ? 'Memo fully signed and dispatched.' : 'Memo dispatched for signature.',
                'created_at' => $memoDate($md['topic_day']),
            ]);
            if ($md['attach'] !== null) {
                MemoAttachment::create([
                    'memo_id' => $memo->id,
                    'file_path' => $md['attach'],
                    'file_name' => basename($md['attach']),
                    'file_size' => Storage::disk('local')->size($md['attach']),
                    'created_at' => $memoDate($md['topic_day']),
                    'updated_at' => $memoDate($md['topic_day']),
                ]);
            }
            if ($md['status'] === 'sent') {
                // Read receipts for a deterministic slice of target recipients.
                $targets = $memo->resolveTargetRecipients()->sortBy('id')->values();
                $take = min(6, $targets->count());
                for ($r = 0; $r < $take; $r++) {
                    $viewed = ($r % 3) !== 2;
                    $acked = ($r % 3) === 0;
                    MemoReadReceipt::create([
                        'memo_id' => $memo->id,
                        'user_id' => $targets[$r]->id,
                        'viewed_at' => $viewed ? $memoDate($md['topic_day'] + 2) : null,
                        'acknowledged_at' => $acked ? $memoDate($md['topic_day'] + 3) : null,
                        'created_at' => $memoDate($md['topic_day'] + 1),
                        'updated_at' => $memoDate($md['topic_day'] + 1),
                    ]);
                    if ($acked) {
                        MemoTracking::create([
                            'memo_id' => $memo->id,
                            'from_entity_type' => 'user',
                            'from_entity_id' => $targets[$r]->id,
                            'to_entity_type' => 'user',
                            'to_entity_id' => $secretary->id,
                            'forwarded_by' => $targets[$r]->id,
                            'action' => 'acknowledged',
                            'remarks' => 'Receipt acknowledged.',
                            'created_at' => $memoDate($md['topic_day'] + 3),
                        ]);
                    }
                }
            }
        }

        // PRO college announcements: chainless, self-signed at creation.
        $announcementMemos = [
            ['SRC Week Celebration - Programme of Activities', 'The SRC week celebration opens with a float through town, followed by inter-hall games, a debate night and an awards dinner.', 'faculty', $faculties[0]->id],
            ['Staff Durbar with Management', 'All teaching and non-teaching staff are invited to a durbar with management to discuss welfare matters and the new academic calendar.', 'department', $departments[2]->id],
        ];
        foreach ($announcementMemos as $ai => $am) {
            $memo = Memo::create([
                'title' => $am[0],
                'content' => $am[1],
                'sender_id' => $pro->id,
                'sender_entity_type' => 'user',
                'sender_entity_id' => null,
                'recipient_type' => $am[2],
                'recipient_entity_id' => $am[3],
                'recipient_role_id' => null,
                'confidentiality_level' => 'public',
                'status' => 'sent',
                'signing_user_id' => null,
                'route_sequentially' => false,
                'cc_recipients' => null,
                'created_at' => $memoDate(45 + $ai),
                'updated_at' => $memoDate(45 + $ai),
            ]);
            MemoSignatory::create([
                'memo_id' => $memo->id,
                'user_id' => $pro->id,
                'step_number' => 1,
                'status' => 'signed',
                'signature_path' => null,
                'remarks' => 'Self-signed at creation.',
                'signed_at' => $memoDate(45 + $ai),
            ]);
            MemoTracking::create([
                'memo_id' => $memo->id,
                'from_entity_type' => 'user',
                'from_entity_id' => $pro->id,
                'to_entity_type' => $am[2],
                'to_entity_id' => $am[3],
                'forwarded_by' => $pro->id,
                'action' => 'sent',
                'remarks' => 'Memo dispatched.',
                'created_at' => $memoDate(45 + $ai),
            ]);
        }

        // ==============================================================
        // 11. OUTCOMES — discipline, clearance, graduation, leaves,
        //     expenditures, attendance files, transcripts, jobs
        // ==============================================================
        $allStudents = Student::query()->orderBy('id')->get()->all();

        // Discipline: serving (return_status=false) + served mix, any student.
        $offences = [
            ['Academic dishonesty during mid-semester examination.', 'Suspension for one semester with counselling.'],
            ['Destruction of library computer accessories.', 'Fine of GHS 500.00 and written apology.'],
            ['Violation of residential hall curfew rules.', 'Written warning and two weeks community service.'],
            ['Unauthorised entry into the faculty server room.', 'Suspension for two weeks and loss of laboratory privileges.'],
            ['Fighting in the dining hall during supper.', 'Suspension for three weeks and parental invitation.'],
            ['Forgery of a medical excuse chit.', 'Suspension for one semester and re-sit of affected papers.'],
        ];
        $discRows = [];
        for ($d = 0; $d < 6; $d++) {
            $s = $allStudents[($d * 23) % count($allStudents)];
            $served = ($d % 2) === 1;
            $sess = $d < 3 ? $prevSession : $curSession;
            $actionDate = $this->dateIn(Carbon::parse($sess->start_date), Carbon::parse($sess->end_date), $d + 2)->toDateString();
            $discRows[] = [
                'index_number' => $s->index_number,
                'fullname' => trim($s->firstname.' '.$s->lastname),
                'program_id' => $s->program_id,
                'academic_session_id' => $sess->id,
                'recorded_by' => $dean->id,
                'offense' => $offences[$d][0],
                'action_taken' => $offences[$d][1],
                'comments' => 'Student appeared before the disciplinary committee with a guardian.',
                'date_of_action' => $actionDate,
                'return_date' => $served ? Carbon::parse($actionDate)->addMonths(2)->toDateString() : Carbon::parse($actionDate)->addMonths(4)->toDateString(),
                'return_status' => $served,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('disciplinary_records', $discRows);

        // Clearance: final-year-of-program ONLY (dynamic max), current session.
        $clearKeys = array_keys(config('clearance.definitions', []));
        $finalists = array_values(array_filter($allStudents, function ($s): bool {
            $program = $s->program;
            if ($program === null) {
                return false;
            }

            return (int) $s->current_year === ((int) $program->program_length) * 100;
        }));
        // NOTE: StudentClearance has no $fillable (auto-fills session at
        // runtime), so rows go through the query builder, not ::create().
        $clearRows = [];
        foreach ($finalists as $fi => $s) {
            foreach ($clearKeys as $ki => $key) {
                $cleared = (($fi + $ki) % 5) < 3; // ~60% cleared
                $clearRows[] = [
                    'student_id' => $s->id,
                    'academic_session_id' => $curSession->id,
                    'department_key' => $key,
                    'status' => $cleared ? 'cleared' : 'pending',
                    'cleared_by' => $cleared ? $registrar->id : null,
                    'cleared_at' => $cleared ? $today->toDateTimeString() : null,
                    'notes' => null,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('student_clearances', $clearRows);

        // Graduation through the REAL service at each track's terminal level
        // (service enforces program max, so only true finalists graduate).
        // Mixed per track: CS + Accounting y4 and IT-diploma y2 graduate;
        // EE + Communication y4 and Business-diploma y2 stay pending.
        $graduateService = app(ProcessGraduationService::class);
        foreach ([$programs[0]->id, $programs[3]->id] as $pid) {
            $graduateService->run($curSession->id, '400', $pid, $today->toDateString(), $registrar->id);
        }
        $graduateService->run($curSession->id, '200', $programs[1]->id, $today->toDateString(), $registrar->id);

        // Leaves: pending / approved / rejected across both years.
        $teacherUserIds = array_map(fn ($t): int => $t->user_id, $teachers);
        $leaveDefs = [
            // [userIdx|email, typeIdx, startOffsetPrev/Cur, days, status, reviewerRole, emergency]
            ['teacher:0', 0, 'prev:40', 5, 'approved', 'owner', false, 'Annual family visit to Kumasi'],
            ['teacher:1', 0, 'cur:10', 3, 'pending', null, false, 'Outdoor naming ceremony arrangements'],
            ['teacher:5', 0, 'prev:120', 6, 'rejected', 'hod', false, 'Conference travel without cover plan'],
            [$secretary->email, 1, 'prev:60', 4, 'approved', 'hr', false, 'Rest and medical check-up'],
            [$accountant->email, 1, 'cur:5', 2, 'pending', null, true, 'Emergency dental procedure'],
            [$pro->email, 1, 'prev:200', 7, 'approved', 'hr', false, 'Annual leave'],
            ['teacher:9', 0, 'cur:20', 5, 'pending', null, false, 'Supervision field trip recovery'],
            [$registrar->email, 0, 'prev:90', 3, 'rejected', 'hr', false, 'Clash with matriculation planning week'],
        ];
        $leaveRows = [];
        foreach ($leaveDefs as $li => $ld) {
            $email = str_starts_with($ld[0], 'teacher:')
                ? $teacherUsers[(int) substr($ld[0], 8)]->email
                : $ld[0];
            $u = User::query()->where('email', $email)->firstOrFail();
            [$scope, $offset] = explode(':', $ld[2]);
            $base = $scope === 'prev' ? $prevStart : $curStart;
            $start = $base->copy()->addDays((int) $offset);
            $end = $start->copy()->addDays($ld[3] - 1);
            $reviewerId = null;
            if ($ld[4] === 'approved') {
                // Teaching staff final approval via owner override (no principal
                // user exists); non-teaching staff decided by HR.
                $reviewerId = $ld[5] === 'hr' ? $hr->id : $owner->id;
            } elseif ($ld[4] === 'rejected') {
                $reviewerId = $ld[5] === 'hr' ? $hr->id : $hodByDept[$departments[0]->id]->id;
            }
            $stage = $ld[4] === 'pending'
                ? ($u->type === 'teacher' ? 'pending_hod' : 'pending_registrar')
                : $ld[4];
            $leaveRows[] = [
                'user_id' => $u->id,
                'staff_leave_type_id' => $leaveTypes[$ld[1]]->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'requested_days' => $ld[3],
                'status' => $ld[4],
                'current_stage' => $stage,
                'reason' => $ld[7],
                'is_emergency' => $ld[6],
                'reviewer_id' => $reviewerId,
                'reviewed_at' => $reviewerId !== null ? $start->copy()->subDays(4)->toDateTimeString() : null,
                'rejection_reason' => $ld[4] === 'rejected' ? 'Cover arrangements were not satisfactory for the period requested.' : null,
                'academic_session_id' => $scope === 'prev' ? $prevSession->id : $curSession->id,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('leave_requests', $leaveRows);

        // Products, invoices (accountant posts), expenditures (vote-book).
        $productRows = [
            ['Semester Registration Kit', 'PROD-REG-KIT', 'Registrar', 15.00, 'Orientation and registration package'],
            ['Official Academic Transcript', 'PROD-TRANSCRIPT', 'Registrar', 50.00, 'Printed academic record sheet'],
            ['Graduation Gown Hire', 'PROD-GRAD-GOWN', 'Ceremony', 120.00, 'Rental of graduation cap and gown'],
        ];
        $prodRows = [];
        foreach ($productRows as $pr) {
            $prodRows[] = [
                'name' => $pr[0], 'sku' => $pr[1], 'category' => $pr[2], 'unit_price' => $pr[3],
                'description' => $pr[4], 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('products', $prodRows);
        $products = Product::query()->orderBy('id')->get()->all();

        $invRows = [
            ['INV-'.$prevStart->format('Y').'-0001', 'Apex Supplies Ltd', 'Orientation packages and transcript materials', 65.00, $prevStart->copy()->addDays(40)->toDateString(), $prevStart->copy()->addDays(75)->toDateString(), 'paid'],
            ['INV-'.$curStart->format('Y').'-0001', 'Ghana Gown Rentals', 'Rental gowns for the graduation ceremony', 240.00, $curStart->copy()->addDays(5)->toDateString(), $curStart->copy()->addDays(40)->toDateString(), 'partially_paid'],
            ['INV-'.$curStart->format('Y').'-0002', 'Danquah Printers', 'Examination answer booklets, second semester', 480.00, $curStart->copy()->addDays(8)->toDateString(), $curStart->copy()->addDays(45)->toDateString(), 'pending'],
        ];
        $invInsert = [];
        foreach ($invRows as $ir) {
            $invInsert[] = [
                'invoice_number' => $ir[0], 'vendor_name' => $ir[1], 'description' => $ir[2], 'amount' => $ir[3],
                'invoice_date' => $ir[4], 'due_date' => $ir[5], 'status' => $ir[6],
                'file_path' => null, 'created_by' => $accountant->id,
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('invoices', $invInsert);
        $invoices = Invoice::query()->orderBy('id')->get()->all();
        $this->chunkInsert('invoice_items', [
            ['invoice_id' => $invoices[0]->id, 'product_id' => $products[0]->id, 'quantity' => 1, 'unit_price' => 15.00, 'total_amount' => 15.00, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
            ['invoice_id' => $invoices[0]->id, 'product_id' => $products[1]->id, 'quantity' => 1, 'unit_price' => 50.00, 'total_amount' => 50.00, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
            ['invoice_id' => $invoices[1]->id, 'product_id' => $products[2]->id, 'quantity' => 2, 'unit_price' => 120.00, 'total_amount' => 240.00, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
            ['invoice_id' => $invoices[2]->id, 'product_id' => $products[0]->id, 'quantity' => 32, 'unit_price' => 15.00, 'total_amount' => 480.00, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
        ]);
        $expRows = [
            [$invoices[0]->id, 'EXP-'.$prevStart->format('Y').'-0001', 65.00, 'Bank Transfer', $prevStart->copy()->addDays(50)->toDateString(), 'Registrar Supplies', 'Settled invoice '.$invoices[0]->invoice_number],
            [$invoices[1]->id, 'EXP-'.$curStart->format('Y').'-0001', 100.00, 'Bank Transfer', $curStart->copy()->addDays(12)->toDateString(), 'Ceremony', 'Part payment of '.$invoices[1]->invoice_number],
            [null, 'EXP-'.$curStart->format('Y').'-0002', 150.00, 'Mobile Money', $curStart->copy()->addDays(15)->toDateString(), 'Office Supplies', 'Printer papers and cartridges'],
            [null, 'EXP-'.$prevStart->format('Y').'-0002', 500.00, 'Bank Transfer', $prevStart->copy()->addDays(150)->toDateString(), 'Maintenance', 'Server room air-conditioner repair'],
            [null, 'EXP-'.$curStart->format('Y').'-0003', 320.00, 'Bank Transfer', $curStart->copy()->addDays(18)->toDateString(), 'IT Infrastructure', 'Network switches for the new computer laboratory'],
        ];
        $expInsert = [];
        foreach ($expRows as $er) {
            $expInsert[] = [
                'invoice_id' => $er[0], 'expense_number' => $er[1], 'amount' => $er[2], 'payment_method' => $er[3],
                'payment_date' => $er[4], 'reference_number' => null, 'category' => $er[5],
                'proof_file_path' => null, 'notes' => $er[6], 'recorded_by' => $accountant->id,
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('expenditures', $expInsert);

        // Teacher attendance sheets (HOD batch): one CSV per teacher per year.
        $attRows = [];
        foreach ($teachers as $ti => $t) {
            $ownCourses = array_values(array_filter($courses, fn ($c): bool => $c->teacher_id === $t->id));
            if ($ownCourses === []) {
                continue;
            }
            $course = $ownCourses[$ti % count($ownCourses)];
            foreach ([$prevSession, $curSession] as $si => $sess) {
                $dir = 'teachers/attendance-sheets';
                $fname = 'seed-attendance-t'.$t->id.'-s'.$sess->id.'.csv';
                $csv = "index_number,fullname,present\n";
                $csv .= 'SAMPLE-001,Sample Student,Y'."\n";
                Storage::disk('college_uploads')->put($dir.'/'.$fname, $csv);
                $classDate = $si === 0
                    ? $this->dateIn($prevStart, $prevEnd, $ti + 9)->toDateString()
                    : $this->dateIn($curStart, $today, $ti + 9)->toDateString();
                $attRows[] = [
                    'teacher_id' => $t->id,
                    'course_id' => $course->id,
                    'academic_session_id' => $sess->id,
                    'semester_id' => null,
                    'recorded_by' => $hodByDept[$t->department_id]->id ?? $owner->id,
                    'class_date' => $classDate,
                    'file_path' => $dir.'/'.$fname,
                    'original_name' => 'attendance_'.$course->code.'_'.str_replace('-', '', $classDate).'.csv',
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('teacher_attendance_sheets', $attRows);

        // Course materials (lecturer authors, QA approves) + course announcements.
        $matRows = [];
        $annRows = [];
        $matCourses = array_slice($courses, 0, 8);
        foreach ($matCourses as $mi => $course) {
            $courseTeacher = $teacherById[$course->teacher_id];
            if ($mi < 6) {
                $matRows[] = [
                    'course_id' => $course->id,
                    'teacher_id' => $courseTeacher->id,
                    'academic_session_id' => $curSession->id,
                    'semester_id' => null,
                    'title' => 'Lecture Notes - '.$course->name,
                    'description' => 'Compiled slides and reading guide for '.$course->code.'.',
                    'file_path' => 'materials/seed_'.strtolower($course->code).'.pdf',
                    'file_type' => 'pdf',
                    'status' => 'approved',
                    'published' => true,
                    'approved_by' => $qa->id,
                    'approved_date' => $today->copy()->subDays(15)->toDateTimeString(),
                    'rejection_reason' => null,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
            if ($mi < 4) {
                $annRows[] = [
                    'course_id' => $course->id,
                    'academic_session_id' => $curSession->id,
                    'teacher_id' => $courseTeacher->id,
                    'title' => $mi === 0 ? 'Welcome to '.$course->code : 'Mid-semester arrangements for '.$course->code,
                    'body' => $mi === 0
                        ? 'Welcome to '.$course->name.'. Lectures hold as timetabled; come along with your registration slips.'
                        : 'Mid-semester quiz dates and revision classes for '.$course->code.' are posted on the department notice board.',
                    'status' => 'active',
                    'published' => true,
                    'approved_by' => $qa->id,
                    'approved_date' => $today->copy()->subDays(4)->toDateTimeString(),
                    'rejection_reason' => null,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('course_materials', $matRows);
        $this->chunkInsert('announcements', $annRows);

        // Transcripts: approved + rejected history (exams_officer), pending current-only.
        $eligible = array_values(array_filter($allStudents, function ($s): bool {
            if (! $s->approved) {
                return false;
            }
            $program = $s->program;
            if ($program === null) {
                return false;
            }

            return $s->graduated || ((int) $s->current_year) >= ((int) $program->program_length) * 100;
        }));
        $purposes = ['National Service posting documentation', 'Graduate studies application', 'Employment background check', 'Professional licensing documentation'];
        $trRows = [];
        foreach ([0, 1, 2, 3, 4, 5] as $k => $ei) {
            $s = $eligible[($ei * 7) % count($eligible)];
            if ($k < 2) {
                $trRows[] = [
                    'student_id' => $s->id, 'status' => 'approved', 'purpose' => $purposes[$k],
                    'remarks' => null, 'processed_by' => $exams->id,
                    'processed_at' => ($k === 0 ? $prevEnd->copy()->subDays(30) : $today->copy()->subDays(20))->toDateTimeString(),
                    'created_at' => ($k === 0 ? $prevEnd->copy()->subDays(34) : $today->copy()->subDays(24))->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            } elseif ($k < 4) {
                $trRows[] = [
                    'student_id' => $s->id, 'status' => 'rejected', 'purpose' => $purposes[$k],
                    'remarks' => 'Please clear outstanding tuition and library obligations first.',
                    'processed_by' => $exams->id,
                    'processed_at' => ($k === 2 ? $prevEnd->copy()->subDays(12) : $today->copy()->subDays(6))->toDateTimeString(),
                    'created_at' => ($k === 2 ? $prevEnd->copy()->subDays(16) : $today->copy()->subDays(10))->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            } else {
                $trRows[] = [
                    'student_id' => $s->id, 'status' => 'pending', 'purpose' => $purposes[$k % count($purposes)],
                    'remarks' => null, 'processed_by' => null, 'processed_at' => null,
                    'created_at' => $today->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('transcript_requests', $trRows);

        // Job alerts: college-relevant, expired + active mix.
        $jobRows = [
            ['National Service Scheme (NSS) Postings', 'job', 'National Service Secretariat', 'NSS placement list for final-year students has been released. Check your postings and print appointment letters.', 'Final-year clearance required.', $today->copy()->addMonths(3)->toDateString()],
            ['Teaching Practice Placement - Partner Schools', 'activity', 'Faculty of Applied Sciences', 'Second-year trainees report to partner basic schools for the teaching practice block.', 'Level 200 standing.', $today->copy()->addMonths(2)->toDateString()],
            ['Graduate Trainee - Rural & Community Bank', 'job', 'Akwapim Rural Bank', 'Graduate trainee intake for accounting and business graduates. Written aptitude test first.', 'BSc Accounting or Diploma in Business, second class minimum.', $today->copy()->addWeeks(6)->toDateString()],
            ['District Scholarship Secretariat Bursary', 'activity', 'Scholarship Secretariat', 'District-level bursary applications open for needy continuing students.', 'Ghanaian, level 200 and above.', $today->copy()->addMonths(4)->toDateString()],
            ['Campus Sanitation Volunteers', 'activity', 'Dean of Students Affairs', 'Volunteers needed for the campus clean-up exercise ahead of the matriculation ceremony.', 'Open to all levels.', $today->copy()->subDays(20)->toDateString()],
            ['Inter-Hall Games Officiating Crew', 'activity', 'SRC Sports Committee', 'Officiating and protocol crew call for the inter-hall games festival.', 'Open to all levels.', $today->copy()->subMonths(2)->toDateString()],
        ];
        $jobInsert = [];
        foreach ($jobRows as $jr) {
            $jobInsert[] = [
                'title' => $jr[0], 'type' => $jr[1], 'company_or_organizer' => $jr[2],
                'description' => $jr[3], 'requirements' => $jr[4], 'expiry_date' => $jr[5],
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('job_alerts', $jobInsert);

        // ==============================================================
        // 12. TIMETABLE — 5-day grids per program/level/session
        // ==============================================================
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $slots = [['08:00:00', '10:30:00'], ['11:00:00', '13:00:00'], ['13:30:00', '15:30:00']];
        $venues = ['Lecture Hall 1', 'Lecture Hall 2', 'Room B4', 'Main Laboratory', 'ICT Centre'];
        $ttRows = [];
        foreach ([$prevSession, $curSession] as $sess) {
            foreach ($programs as $program) {
                $maxLevel = ((int) $program->program_length) * 100;
                for ($lvl = 100; $lvl <= $maxLevel; $lvl += 100) {
                    $ttRows[] = [
                        'program_id' => $program->id,
                        'level' => $lvl,
                        'session_id' => $sess->id,
                        'created_by' => $owner->id,
                        'created_at' => $now->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ];
                }
            }
        }
        $this->chunkInsert('timetables', $ttRows);
        $timetables = Timetable::query()->get()->all();
        $classRows = [];
        foreach ($timetables as $tt) {
            $isPrev = (int) $tt->session_id === (int) $prevSession->id;
            $levelCourses = $coursesByProgramLevel[$tt->program_id][(string) $tt->level] ?? [];
            foreach (array_values($levelCourses) as $ci => $course) {
                // Previous-year grid slightly varied: shifted day/slot/venue.
                $day = $days[($ci + ($isPrev ? 2 : 0)) % count($days)];
                $slot = $slots[($ci + ($isPrev ? 1 : 0)) % count($slots)];
                $venue = $venues[($course->id + ($isPrev ? 3 : 0)) % count($venues)];
                $classRows[] = [
                    'timetable_id' => $tt->id,
                    'program_id' => $tt->program_id,
                    'course_id' => $course->id,
                    'teacher_id' => $course->teacher_id,
                    'day' => $day,
                    'start_time' => $slot[0],
                    'end_time' => $slot[1],
                    'venue' => $venue,
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ];
            }
        }
        $this->chunkInsert('timetable_classes', $classRows);

        // ==============================================================
        // 13. SETTINGS + ownership audit trail + impersonation + backup
        // ==============================================================
        $settingRows = [
            ['system_preferences', 'system_preferences.student_grading_redirect', '0', 'boolean', 'Redirect students to external grading software'],
            ['system_preferences', 'system_preferences.allow_student_self_registration', '1', 'boolean', 'Allow student self-registration'],
            ['system_preferences', 'system_preferences.enable_email_notifications', '1', 'boolean', 'Enable system email alerts'],
            ['system_preferences', 'system_preferences.memos_require_signature', '1', 'boolean', 'Memos require official signatures before dispatch'],
            ['system_preferences', 'system_preferences.memos_multiple_signatories', '1', 'boolean', 'Memos support multiple concurrent signatories'],
            ['system_preferences', 'system_preferences.show_detailed_bill_breakdown', '0', 'boolean', 'Show detailed itemized fee breakdown to students'],
            ['system_preferences', 'system_preferences.show_attendance_policy', '1', 'boolean', 'Show class attendance policy disclaimer to students'],
            ['system_preferences', 'system_preferences.min_attendance_threshold', '75', 'integer', 'Default minimum attendance percentage required for exams'],
            ['image_validation', 'image_validation.passport_bg_color_r', '255', 'integer', 'Passport background R'],
            ['image_validation', 'image_validation.passport_bg_color_g', '0', 'integer', 'Passport background G'],
            ['image_validation', 'image_validation.passport_bg_color_b', '0', 'integer', 'Passport background B'],
            ['image_validation', 'image_validation.passport_tolerance', '120', 'integer', 'Passport background tolerance'],
            ['finance', 'system_preferences.fee_billing_cycle', 'year', 'string', 'Billing cycle preference (year or semester)'],
            ['memos', 'system_preferences.strict_departmental_access', '0', 'boolean', 'Limit memo visibility to active departmental members only'],
            ['memos', 'system_preferences.thread_isolation_on_forward', '0', 'boolean', 'Isolate memo history snapshot upon forwarding to new departments'],
            ['leave', 'system_preferences.emergency_leave_enabled', '0', 'boolean', 'Allow submittals outside application windows for emergency cases'],
            ['leave', 'system_preferences.leave_submission_start', $curStart->copy()->addDays(30)->toDateString(), 'string', 'Staff leave request submission window start date'],
            ['leave', 'system_preferences.leave_submission_end', $curStart->copy()->addDays(60)->toDateString(), 'string', 'Staff leave request submission window end date'],
        ];
        $settingInsert = [];
        foreach ($settingRows as $sr) {
            $settingInsert[] = [
                'category' => $sr[0], 'setting_key' => $sr[1], 'setting_value' => $sr[2],
                'data_type' => $sr[3], 'description' => $sr[4], 'updated_by' => $owner->id,
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ];
        }
        $this->chunkInsert('settings', $settingInsert);

        // Impersonation history (owner only) + a system backup row.
        $teacherUser0 = $teacherUsers[0];
        $studentUser0 = $studentUsersByEmail['student@demo.com'];
        $this->chunkInsert('admin_impersonation_logs', [
            [
                'impersonator_user_id' => $owner->id,
                'impersonated_user_id' => $teacherUser0->id,
                'started_at' => $prevEnd->copy()->subDays(40)->toDateTimeString(),
                'ended_at' => $prevEnd->copy()->subDays(40)->addHours(2)->toDateTimeString(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DemoSeed/1.0',
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'impersonator_user_id' => $owner->id,
                'impersonated_user_id' => $studentUser0->id,
                'started_at' => $today->copy()->subDays(12)->toDateTimeString(),
                'ended_at' => $today->copy()->subDays(12)->addHour()->toDateTimeString(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DemoSeed/1.0',
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
        ]);
        DB::table('backups')->insert([
            'filename' => 'demo-backup-'.$today->format('Ymd').'.sql',
            'file_path' => 'backups/demo-backup-'.$today->format('Ymd').'.sql',
            'file_size' => 5242880,
            'created_by' => $owner->id,
            'created_at' => $now->toDateTimeString(),
        ]);

        // Audit trail under the users who actually moved each item.
        $audit = \App\Helpers\AuditHelper::class;
        Auth::login($accountant);
        $audit::log('invoice_created', 'Invoice '.$invoices[0]->invoice_number.' posted for '.$invoices[0]->vendor_name, $invoices[0]);
        $audit::log('invoice_paid', 'Invoice '.$invoices[0]->invoice_number.' settled in full', $invoices[0]);
        $audit::log('invoice_created', 'Invoice '.$invoices[1]->invoice_number.' posted for '.$invoices[1]->vendor_name, $invoices[1]);
        Auth::login($accountant);
        foreach (Expenditure::query()->orderBy('id')->get()->all() as $exp) {
            $audit::log('expenditure_recorded', 'Expenditure '.$exp->expense_number.' recorded', $exp);
        }
        Auth::login($secretary);
        $firstMemo = Memo::query()->orderBy('id')->first();
        $audit::log('memo_sent', 'Memo "'.$firstMemo->title.'" drafted and dispatched', $firstMemo);
        Auth::login($admissions);
        $firstIntake = $intakeStudents[0] ?? null;
        if ($firstIntake !== null) {
            $audit::log('student_approved', 'Admission approved for '.$firstIntake->lastname.' '.$firstIntake->firstname, $firstIntake);
        }
        // Leave trail: submissions under requesters, approvals under reviewers.
        foreach (LeaveRequest::query()->orderBy('id')->get()->all() as $lr) {
            $requester = User::query()->find($lr->user_id);
            if ($requester !== null) {
                Auth::login($requester);
                $audit::log('leave_submitted', 'Leave request submitted by '.$requester->name, $lr);
            }
            if ($lr->status === 'approved') {
                $reviewer = User::query()->find($lr->reviewer_id);
                if ($reviewer !== null) {
                    Auth::login($reviewer);
                    $audit::log('leave_approved', 'Leave request approved by '.$reviewer->name, $lr);
                }
            }
        }
        Auth::login($owner);
        $audit::log('settings_updated', 'System preferences verified for the demo year', null, ['category' => 'system_preferences']);
    }

    /** GPA + transcript-support rows for every student with results in-session. */
    private function seedAcademicInformation(AcademicSession $session): void
    {
        $now = now()->toDateTimeString();
        $studentIds = Result::query()
            ->where('academic_session_id', $session->id)
            ->select('student_id')
            ->distinct()
            ->pluck('student_id');
        $rows = [];
        foreach ($studentIds as $studentId) {
            $student = Student::query()->find($studentId);
            if ($student === null) {
                continue;
            }
            $results = Result::query()
                ->where('student_id', $studentId)
                ->where('academic_session_id', $session->id)
                ->get();
            if ($results->isEmpty()) {
                continue;
            }
            $gpa = round($results->avg('grade_points'), 2);
            $rows[] = [
                'student_id' => $studentId,
                'class_level' => $student->current_year,
                'section' => 'A',
                'academic_session' => $session->name,
                'program_id' => $student->program_id,
                'major_field' => $student->program?->name,
                'gpa' => $gpa,
                'result_id' => $results->first()->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->chunkInsert('academic_information', $rows);
    }

    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full));
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    private function pickGender(string $fullName): string
    {
        $first = explode(' ', trim($fullName))[0];

        return in_array($first, ['Ama', 'Abena', 'Akosua', 'Adwoa', 'Efya', 'Esi', 'Araba', 'Ewurama', 'Maame', 'Selasie', 'Dzifa', 'Kafui', 'Abla', 'Dede', 'Naa', 'Mamle'], true) ? 'female' : 'male';
    }
}
