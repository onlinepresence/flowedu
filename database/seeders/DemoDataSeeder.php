<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Admin;
use App\Models\AdminType;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\Department;
use App\Models\DisciplinaryRecord;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\Faculty;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Hall;
use App\Models\MedicalHistory;
use App\Models\Memo;
use App\Models\MemoTracking;
use App\Models\NonTeachingStaff;
use App\Models\ParentGuardian;
use App\Models\Payment;
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
use App\Models\StaffAssignment;
use App\Models\StaffRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherRole;
use App\Models\TeacherAssignment;
use App\Models\TeacherCourse;
use App\Models\TeacherAttendanceSheet;
use App\Models\CourseMaterial;
use App\Models\User;
use App\Models\UserRole;
use App\Models\StaffLeaveType;
use App\Models\LeaveRequest;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Expenditure;
use App\Models\FeeComponent;
use App\Models\FeeStructureItem;
use App\Models\SystemAudit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset tables (handling sqlite & mysql cases)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Truncate all tables we will populate
        $tables = [
            'users', 'admins', 'teachers', 'students', 'schools', 'school_licences',
            'academic_sessions', 'semesters', 'halls', 'faculties', 'departments',
            'programs', 'courses', 'teacher_assignments', 'parent_guardians',
            'fee_structures', 'payments', 'grades', 'results', 'grade_points',
            'disciplinary_records', 'medical_histories', 'evaluation_forms',
            'evaluation_questions', 'evaluation_responses', 'response_details',
            'announcements', 'memos', 'memo_tracking', 'memo_attachments', 'notifications',
            'non_teaching_staff', 'staff_assignments', 'staff_roles', 'scholarships', 'scholarship_recipients',
            'result_slips', 'transcript_requests', 'timetables', 'timetable_classes', 'teacher_courses', 'settings',
            'memo_signatories', 'memo_read_receipts', 'fee_breakdown_requests', 'job_alerts',
            'products', 'invoices', 'invoice_items', 'expenditures', 'staff_leave_types', 'leave_requests', 'fee_components', 'fee_structure_items', 'system_audits'
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // 2. Ensure baseline roles and types are seeded
        AdminType::ensureDefaults();
        UserRole::ensureSystemRoles();

        // Create staff leave types
        $seniorStaffLeave = StaffLeaveType::create(['name' => 'Senior Staff', 'max_leave_days' => 30]);
        $juniorStaffLeave = StaffLeaveType::create(['name' => 'Junior Staff', 'max_leave_days' => 21]);
        $principalLeave = StaffLeaveType::create(['name' => 'Principal Officers', 'max_leave_days' => 42]);

        // Seed default fee components
        $componentsData = [
            ['name' => 'Tuition Fee', 'is_system' => true],
            ['name' => 'Library Fee', 'is_system' => true],
            ['name' => 'Lab Fee', 'is_system' => true],
            ['name' => 'Medical Fee', 'is_system' => true],
            ['name' => 'Sports Fee', 'is_system' => true],
            ['name' => 'Examination Fee', 'is_system' => true],
        ];
        $components = [];
        foreach ($componentsData as $cd) {
            $components[$cd['name']] = FeeComponent::create([
                'name' => $cd['name'],
                'default_percentage' => 0.00,
                'is_active' => true,
                'is_system' => $cd['is_system'],
            ]);
        }

        // 3. Seed School & License
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
            'principal_name' => 'Prof. Ernest K. Adei',
            'facebook_url' => 'https://facebook.com/apexpoly',
            'twitter_url' => 'https://twitter.com/apexpoly',
            'linkedin_url' => 'https://linkedin.com/school/apexpoly',
            'instagram_url' => 'https://instagram.com/apexpoly',
        ]);

        SchoolLicence::create([
            'school_id' => $school->id,
            'max_active_students' => 500,
            'licence_start' => now()->subMonths(3),
            'licence_end' => now()->addYear(),
            'support_until' => now()->addYear(),
            'notes' => 'Complete enterprise demo sandbox license.',
            'external_ref' => 'REF-SANDBOX-2026',
            'licence_key' => 'APEX-COMPLETE-DEMO-LICENSE-KEY',
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
        ]);

        // 4. Seed Academic Sessions & Semesters
        $previousSession = AcademicSession::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => false,
        ]);

        Semester::create([
            'academic_session_id' => $previousSession->id,
            'name' => 'First Semester',
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-31',
            'is_active' => false,
        ]);

        Semester::create([
            'academic_session_id' => $previousSession->id,
            'name' => 'Second Semester',
            'start_date' => '2025-02-01',
            'end_date' => '2025-06-30',
            'is_active' => false,
        ]);

        $session = AcademicSession::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        Semester::create([
            'academic_session_id' => $session->id,
            'name' => 'First Semester',
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
            'is_active' => false,
        ]);

        Semester::create([
            'academic_session_id' => $session->id,
            'name' => 'Second Semester',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // 5. Seed Halls
        $hallsData = [
            ['name' => 'Republic Hall', 'cost' => rand(400, 600), 'period' => 'per_semester'],
            ['name' => 'Queens Hall', 'cost' => rand(450, 650), 'period' => 'per_semester'],
            ['name' => 'Unity Hall', 'cost' => rand(700, 900), 'period' => 'per_year'],
            ['name' => 'Independence Hall', 'cost' => rand(700, 850), 'period' => 'per_year'],
        ];
        $hallIds = [];
        foreach ($hallsData as $h) {
            $createdHall = Hall::create([
                'name' => $h['name'],
                'master' => 'Prof. '.fake()->firstName('male').' '.fake()->lastName(),
                'cost' => $h['cost'],
                'period' => $h['period'],
            ]);
            $hallIds[] = $createdHall->id;
        }

        // 6. Seed Faculty & Departments & Programs
        $facultyData = [
            'Faculty of Engineering & Computing' => [
                'Department of Computer Science' => [
                    ['name' => 'BSc Computer Science', 'certificate' => 'BSc', 'cost' => 1800, 'program_length' => 4],
                    ['name' => 'Diploma in IT', 'certificate' => 'Diploma', 'cost' => 1200, 'program_length' => 2],
                ],
                'Department of Computer Engineering' => [
                    ['name' => 'BEng Computer Engineering', 'certificate' => 'BEng', 'cost' => 2200, 'program_length' => 4],
                ],
            ],
            'Faculty of Business & Humanities' => [
                'Department of Finance & Economics' => [
                    ['name' => 'BSc Business Administration', 'certificate' => 'BSc', 'cost' => 1500, 'program_length' => 4],
                ],
            ],
        ];

        $programsList = [];
        $departmentIds = [];
        foreach ($facultyData as $facName => $deps) {
            $faculty = Faculty::create(['name' => $facName]);
            foreach ($deps as $depName => $progs) {
                $dep = Department::create([
                    'name' => $depName,
                    'faculty_id' => $faculty->id,
                ]);
                $departmentIds[] = $dep->id;
                foreach ($progs as $p) {
                    $prog = Program::create([
                        'name' => $p['name'],
                        'department_id' => $dep->id,
                        'certificate' => $p['certificate'],
                        'cost' => $p['cost'],
                        'program_length' => $p['program_length'],
                    ]);
                    $programsList[] = $prog;
                }
            }
        }

        // 7. Seed Courses (CS101, CS202, etc.)
        $coursesList = [];
        $courseData = [
            // CS (Department 1 / Program 1)
            ['code' => 'CS101', 'name' => 'Introduction to Computing', 'program_id' => 1, 'course_semester' => '1', 'year_level' => '1'],
            ['code' => 'CS102', 'name' => 'Introduction to Programming', 'program_id' => 1, 'course_semester' => '2', 'year_level' => '1'],
            ['code' => 'CS201', 'name' => 'Data Structures & Algorithms', 'program_id' => 1, 'course_semester' => '1', 'year_level' => '2'],
            ['code' => 'CS202', 'name' => 'Database Management Systems', 'program_id' => 1, 'course_semester' => '2', 'year_level' => '2'],
            ['code' => 'CS301', 'name' => 'Software Engineering Principles', 'program_id' => 1, 'course_semester' => '1', 'year_level' => '3'],
            ['code' => 'CS401', 'name' => 'Artificial Intelligence', 'program_id' => 1, 'course_semester' => '1', 'year_level' => '4'],
            // BEng (Program 3)
            ['code' => 'CE101', 'name' => 'Applied Engineering Math', 'program_id' => 3, 'course_semester' => '1', 'year_level' => '1'],
            ['code' => 'CE201', 'name' => 'Digital Logic Design', 'program_id' => 3, 'course_semester' => '1', 'year_level' => '2'],
            // Business Admin (Program 4)
            ['code' => 'BA101', 'name' => 'Introduction to Management', 'program_id' => 4, 'course_semester' => '1', 'year_level' => '1'],
            ['code' => 'BA201', 'name' => 'Principles of Microeconomics', 'program_id' => 4, 'course_semester' => '1', 'year_level' => '2'],
        ];
        foreach ($courseData as $c) {
            $coursesList[] = Course::create($c);
        }

        // 8. Seed Grade Points
        $gpData = [
            ['min_score' => 80.0, 'max_score' => 100.0, 'points' => 4.0, 'grade' => 'A'],
            ['min_score' => 70.0, 'max_score' => 79.99, 'points' => 3.5, 'grade' => 'B+'],
            ['min_score' => 60.0, 'max_score' => 69.99, 'points' => 3.0, 'grade' => 'B'],
            ['min_score' => 50.0, 'max_score' => 59.99, 'points' => 2.5, 'grade' => 'C'],
            ['min_score' => 0.0,  'max_score' => 49.99, 'points' => 0.0, 'grade' => 'F'],
        ];
        foreach ($gpData as $gp) {
            GradePoint::create($gp);
        }

        // 9. Seed Demo Superadmin/Owner & Secretary & HOD
        $adminUser = User::create([
            'name' => 'Demo Admin',
            'username' => 'admin_demo',
            'email' => 'admin@demo.com',
            'email_verified_at' => now(),
            'type' => 'admin',
            'staff_leave_type_id' => $principalLeave->id,
            'password' => Hash::make('password'),
            'user_secret' => Str::random(16),
            'active' => true,
        ]);

        $ownerRole = UserRole::where('name', 'owner')->first();
        Admin::create([
            'user_id' => $adminUser->id,
            'lastname' => 'Owner',
            'othernames' => 'Demo Admin',
            'phone_number' => '+233 24 999 0001',
            'gender' => 'male',
            'position_title' => 'Chief Executive & Principal',
            'department_id' => 1,
            'faculty_id' => 1,
            'status' => 'active',
            'date_of_appointment' => '2020-01-15',
            'created_by' => $adminUser->id,
            'ghana_card' => 'GHA-789012345-6',
            'type' => $ownerRole?->id,
        ]);

        $secretaryUser = User::create([
            'name' => 'Jane Secretary',
            'username' => 'secretary_demo',
            'email' => 'secretary@demo.com',
            'email_verified_at' => now(),
            'type' => 'admin',
            'staff_leave_type_id' => $juniorStaffLeave->id,
            'password' => Hash::make('password'),
            'user_secret' => Str::random(16),
            'active' => true,
        ]);

        $secretaryRole = UserRole::where('name', 'secretary')->first();
        Admin::create([
            'user_id' => $secretaryUser->id,
            'lastname' => 'Secretary',
            'othernames' => 'Jane',
            'phone_number' => '+233 24 999 0002',
            'gender' => 'female',
            'position_title' => 'Department Secretary',
            'department_id' => 1,
            'faculty_id' => 1,
            'status' => 'active',
            'date_of_appointment' => '2022-02-01',
            'created_by' => $adminUser->id,
            'ghana_card' => 'GHA-123456789-0',
            'type' => $secretaryRole?->id,
        ]);

        $hodUser = User::create([
            'name' => 'Dr. Robert HOD',
            'username' => 'hod_demo',
            'email' => 'hod@demo.com',
            'email_verified_at' => now(),
            'type' => 'admin',
            'staff_leave_type_id' => $seniorStaffLeave->id,
            'password' => Hash::make('password'),
            'user_secret' => Str::random(16),
            'active' => true,
        ]);

        $hodRole = UserRole::where('name', 'hod')->first();
        Admin::create([
            'user_id' => $hodUser->id,
            'lastname' => 'HOD',
            'othernames' => 'Dr. Robert',
            'phone_number' => '+233 24 999 0003',
            'gender' => 'male',
            'position_title' => 'Head of Computer Science',
            'department_id' => 1,
            'faculty_id' => 1,
            'status' => 'active',
            'date_of_appointment' => '2021-03-10',
            'created_by' => $adminUser->id,
            'ghana_card' => 'GHA-234567890-1',
            'type' => $hodRole?->id,
        ]);

        // 10. Seed Teachers (8 to 15 total, including teacher@demo.com)
        $teacherCount = rand(8, 15);
        $teachersList = [];

        for ($i = 0; $i < $teacherCount; $i++) {
            $isStatic = ($i === 0);

            $email = $isStatic ? 'teacher@demo.com' : fake()->unique()->safeEmail();
            $first = $isStatic ? 'Sarah' : fake()->firstName();
            $last = $isStatic ? 'Appiah' : fake()->lastName();
            $gender = $isStatic ? 'female' : fake()->randomElement(['male', 'female']);
            $deptId = $isStatic ? 1 : fake()->randomElement($departmentIds);

            $rank = $isStatic ? 'Senior Lecturer' : fake()->randomElement(['Professor', 'Associate Professor', 'Senior Lecturer', 'Lecturer', 'Assistant Lecturer']);
            $qual = $isStatic ? 'PhD in Computer Science' : fake()->randomElement(['PhD in Computer Science', 'PhD in Engineering', 'PhD in Finance', 'MSc in IT', 'MBA']);

            $tUser = User::create([
                'name' => "{$first} {$last}",
                'username' => $isStatic ? 'tch_sarah_appiah' : ('tch_'.Str::slug("{$first}_{$last}", '_').'_'.rand(10, 99)),
                'email' => $email,
                'email_verified_at' => now(),
                'type' => 'teacher',
                'staff_leave_type_id' => $seniorStaffLeave->id,
                'password' => Hash::make('password'),
                'user_secret' => Str::random(16),
                'active' => true,
            ]);

            $teacherObj = Teacher::create([
                'user_id' => $tUser->id,
                'lastname' => $last,
                'othernames' => $first,
                'title' => $isStatic ? 'Dr.' : fake()->randomElement(['Dr.', 'Prof.', 'Mr.', 'Mrs.', 'Ms.']),
                'ghana_card' => 'GHA-'.rand(100000000, 999999999).'-'.rand(0, 9),
                'profile_pic' => 'images/auth/login-office.jpeg',
                'gender' => $gender,
                'date_of_birth' => now()->subYears(rand(30, 50))->format('Y-m-d'),
                'nationality' => 'Ghanaian',
                'contact_address' => 'Plot '.rand(10, 100).' Ring Road, Accra',
                'phone_number' => '+233 20 '.rand(1000000, 9999999),
                'staff_id' => 'TCH-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'department_id' => $deptId,
                'office_location' => 'Block '.fake()->randomElement(['A', 'B', 'C', 'D']).', Room '.rand(101, 305),
                'office_hours' => fake()->randomElement(['Mon/Wed 2:00 PM - 4:00 PM', 'Tue/Thu 10:00 AM - 12:00 PM', 'Fridays 1:00 PM - 3:00 PM']),
                'rank' => $rank,
                'qualification' => $qual,
                'specialization' => 'Academic instruction & Research',
                'orcid_id' => '0000-0002-'.rand(1000, 9999).'-'.rand(1000, 9999),
                'google_scholar_url' => 'https://scholar.google.com/citations?user='.Str::random(12),
                'employment_type' => 'Full-time',
                'years_experience' => rand(3, 25),
                'emergency_name' => fake()->name(),
                'emergency_phone' => '+233 24 '.rand(1000000, 9999999),
                'date_of_appointment' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                'is_onboarded' => 1,
            ]);
            $teachersList[] = $teacherObj;

            // Seed a default role assignment for the teacher
            $roleSlug = $isStatic ? 'lecturer' : fake()->randomElement(['lecturer', 'coordinator', 'tutor']);
            TeacherRole::create([
                'teacher_id' => $teacherObj->id,
                'role' => $roleSlug,
                'program_id' => $isStatic ? 1 : collect($programsList)->random()->id,
                'description' => 'Assigned during sandbox initialization.',
                'assigned_by' => $adminUser->id,
                'assigned_date' => now()->subMonths(3),
                'status' => 'active',
            ]);
        }

        // 11. Seed Teacher Assignments (Assign courses to teachers)
        foreach ($coursesList as $cIdx => $course) {
            $teacher = $teachersList[$cIdx % count($teachersList)];
            TeacherAssignment::create([
                'teacher_id' => $teacher->id,
                'program_id' => $course->program_id,
                'level' => (int) $course->year_level * 100,
                'course_id' => $course->id,
                'session_id' => $session->id,
                'assigned_by' => $adminUser->id,
                'assigned_date' => now()->subMonths(2),
            ]);

            // Ensure Course itself has teacher_id set
            $course->update(['teacher_id' => $teacher->id]);

            // Create TeacherCourse mapping
            TeacherCourse::create([
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'program_level' => (string) ((int) $course->year_level * 100),
            ]);
        }

        // 12. Seed Students (30 to 100 total, including student@demo.com)
        $studentCount = rand(30, 100);
        $studentsList = [];

        for ($i = 0; $i < $studentCount; $i++) {
            $isStatic = ($i === 0);

            $email = $isStatic ? 'student@demo.com' : fake()->unique()->safeEmail();
            $first = $isStatic ? 'John' : fake()->firstName();
            $last = $isStatic ? 'Doe' : fake()->lastName();
            $gender = $isStatic ? 'male' : fake()->randomElement(['male', 'female']);
            $prog = $isStatic ? $programsList[0] : fake()->randomElement($programsList);

            // Program length check for level
            if ($prog->program_length == 2) {
                $level = $isStatic ? '100' : fake()->randomElement(['100', '200']);
            } else {
                $level = $isStatic ? '100' : fake()->randomElement(['100', '200', '300', '400']);
            }

            $sUser = User::create([
                'name' => "{$first} {$last}",
                'username' => $isStatic ? 'std_john_doe' : ('std_'.Str::slug("{$first}_{$last}", '_').'_'.rand(100, 999)),
                'email' => $email,
                'email_verified_at' => now(),
                'type' => 'student',
                'password' => Hash::make('password'),
                'user_secret' => Str::random(16),
                'active' => true,
            ]);

            $student = Student::create([
                'user_id' => $sUser->id,
                'index_number' => $isStatic ? 'STD001' : ('STD'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)),
                'admission_index' => 'ADM-'.($i + 1000),
                'lastname' => $last,
                'firstname' => $first,
                'othernames' => null,
                'department_id' => $prog->department_id,
                'program_id' => $prog->id,
                'date_of_birth' => now()->subYears(rand(18, 25))->format('Y-m-d'),
                'gender' => $gender,
                'nationality' => fake()->randomElement(['Ghanaian', 'Ghanaian', 'Ghanaian', 'Nigerian', 'Liberian']),
                'religion' => fake()->randomElement(['Christian', 'Muslim', 'Christian', 'None']),
                'current_year' => $level,
                'contact_address' => 'Residential Area '.rand(1, 15).', Accra',
                'phone_number' => '+233 54 '.rand(1000000, 9999999),
                'admission_date' => '2025-09-01',
                'hall_id' => $hallIds[rand(0, 3)],
                'profile_pic' => 'images/auth/login-office.jpeg',
                'is_new' => false,
                'approved' => true,
            ]);
            $studentsList[] = $student;

            // Seed Parent Guardian
            ParentGuardian::create([
                'student_id' => $student->id,
                'name' => fake()->name('male').' '.$last,
                'relationship' => fake()->randomElement(['Father', 'Mother', 'Guardian']),
                'address' => $student->contact_address,
                'phone_number' => '+233 27 '.rand(1000000, 9999999),
                'email' => fake()->safeEmail(),
            ]);

            // Seed Medical History
            $currentYearInt = (int) $level;

            // If student is level 200+, they were here in 2024/2025
            if ($currentYearInt > 100) {
                MedicalHistory::create([
                    'student_id' => $student->id,
                    'academic_session_id' => $previousSession->id,
                    'medical_conditions' => rand(0, 4) === 0 ? fake()->randomElement(['Asthma (mild)', 'Mild allergy to dust', 'Slight seasonal allergies']) : 'None',
                    'allergies' => rand(0, 4) === 0 ? fake()->randomElement(['Peanuts', 'Lactose intolerance', 'Penicillin']) : 'None',
                    'medications' => rand(0, 4) === 0 ? fake()->randomElement(['Albuterol inhaler', 'Claritin']) : 'None',
                    'immunization_records' => 'COVID-19 (Fully Vaccinated), Yellow Fever',
                    'emergency_contacts' => fake()->name().' - +233 27 '.rand(1000000, 9999999),
                ]);
            }

            // Every student has a record for the current session (2025/2026)
            MedicalHistory::create([
                'student_id' => $student->id,
                'academic_session_id' => $session->id,
                'medical_conditions' => rand(0, 4) === 0 ? fake()->randomElement(['Asthma (mild)', 'Mild allergy to dust', 'Slight seasonal allergies']) : 'None',
                'allergies' => rand(0, 4) === 0 ? fake()->randomElement(['Peanuts', 'Lactose intolerance', 'Penicillin']) : 'None',
                'medications' => rand(0, 4) === 0 ? fake()->randomElement(['Albuterol inhaler', 'Claritin']) : 'None',
                'immunization_records' => 'COVID-19 (Fully Vaccinated), Yellow Fever',
                'emergency_contacts' => fake()->name().' - +233 27 '.rand(1000000, 9999999),
            ]);
        }

        // 12.1 Seed Non-Teaching Staff (3 to 6 total)
        $nonTeachingCount = rand(3, 6);
        $staffPositions = [
            ['pos' => 'Academic Registrar', 'role' => 'registrar', 'office' => 'Main Registry Room 102'],
            ['pos' => 'Finance Officer', 'role' => 'accountant', 'office' => 'Accounts Office Room 105'],
            ['pos' => 'IT Administrator', 'role' => 'it_support', 'office' => 'Server Room / IT Helpdesk'],
            ['pos' => 'Librarian', 'role' => 'librarian', 'office' => 'Campus Library Desk'],
            ['pos' => 'Human Resource Assistant', 'role' => 'hr', 'office' => 'HR Department Room 201'],
            ['pos' => 'Admissions Clerk', 'role' => 'admissions', 'office' => 'Admissions Office Room 101'],
        ];

        for ($i = 0; $i < $nonTeachingCount; $i++) {
            $posData = $staffPositions[$i % count($staffPositions)];
            $first = fake()->firstName();
            $last = fake()->lastName();
            $deptId = fake()->randomElement($departmentIds);

            $staffUser = User::create([
                'name' => "{$first} {$last}",
                'username' => 'stf_'.Str::slug("{$first}_{$last}", '_').'_'.rand(10, 99),
                'email' => fake()->unique()->safeEmail(),
                'email_verified_at' => now(),
                'type' => 'staff',
                'staff_leave_type_id' => $posData['role'] === 'registrar' ? $seniorStaffLeave->id : $juniorStaffLeave->id,
                'password' => Hash::make('password'),
                'user_secret' => Str::random(16),
                'active' => true,
            ]);

            NonTeachingStaff::create([
                'user_id' => $staffUser->id,
                'position' => $posData['pos'],
                'department_id' => $deptId,
                'phone_number' => '+233 20 '.rand(1000000, 9999999),
                'status' => 'active',
            ]);

            StaffAssignment::create([
                'staff_id' => $staffUser->id,
                'department_id' => $deptId,
                'office' => $posData['office'],
                'position_title' => $posData['pos'],
                'assignment_date' => now()->subMonths(6),
                'assigned_by' => $adminUser->id,
                'status' => 'active',
            ]);

            StaffRole::create([
                'staff_id' => $staffUser->id,
                'role' => $posData['role'],
                'department_id' => $deptId,
                'description' => 'Assigned to '.$posData['pos'].' duties.',
                'assigned_by' => $adminUser->id,
                'assigned_date' => now()->subMonths(6),
                'status' => 'active',
            ]);
        }

        // 12.2 Seed Scholarships
        $scholarships = [
            [
                'name' => 'Presidential Academic Merit Scholarship',
                'type' => 'scholarship',
                'amount' => 1200.00,
                'duration_semesters' => 8,
                'coverage_type' => 'tuition_only',
                'coverage_components' => null,
                'desc' => 'Awarded for outstanding academic performance covering tuition fees.',
            ],
            [
                'name' => 'STEM Innovation Grant',
                'type' => 'grant',
                'amount' => 1500.00,
                'duration_semesters' => 4,
                'coverage_type' => 'full',
                'coverage_components' => null,
                'desc' => 'For students pursuing computing and computer engineering studies.',
            ],
            [
                'name' => 'Apex Hostel Relief Bursary',
                'type' => 'scholarship',
                'amount' => 600.00,
                'duration_semesters' => 2,
                'coverage_type' => 'hostel_only',
                'coverage_components' => null,
                'desc' => 'Hostel assistance aid for deserving students.',
            ],
            [
                'name' => 'Monthly Student Allowance Scheme',
                'type' => 'scholarship',
                'amount' => 250.00,
                'duration_semesters' => 12,
                'coverage_type' => 'full',
                'coverage_components' => null,
                'desc' => 'Monthly welfare stipend disbursed to active students.',
            ],
        ];

        $scholarshipModels = [];
        foreach ($scholarships as $s) {
            $scholarshipModels[] = Scholarship::create([
                'name' => $s['name'],
                'type' => $s['type'],
                'amount' => $s['amount'],
                'duration_semesters' => $s['duration_semesters'],
                'expiry_date' => now()->addYears(2)->toDateString(),
                'coverage_type' => $s['coverage_type'],
                'coverage_components' => $s['coverage_components'],
                'description' => $s['desc'],
                'status' => 'active',
                'created_by' => $adminUser->id,
            ]);
        }

        $allowanceScheme = collect($scholarshipModels)->where('name', 'Monthly Student Allowance Scheme')->first();

        // Award to 10-15 random students
        $numAwards = rand(10, 15);
        $awardedStudents = collect($studentsList)->random($numAwards);
        foreach ($awardedStudents as $idx => $student) {
            $isLevel100 = ((int) $student->current_year === 100);
            $targetSession = $isLevel100 ? $session : (($idx % 2 === 0) ? $previousSession : $session);
            $schol = collect($scholarshipModels)->where('id', '!=', $allowanceScheme->id)->random();
            ScholarshipRecipient::create([
                'scholarship_id' => $schol->id,
                'student_id' => $student->id,
                'academic_session_id' => $targetSession->id,
                'amount_awarded' => $schol->amount,
                'award_date' => ($targetSession->id === $previousSession->id)
                     ? now()->subYear()->subMonths(rand(1, 4))->toDateString()
                     : now()->subMonths(rand(1, 4))->toDateString(),
                'status' => 'approved',
            ]);
        }

        // Seed recurring monthly allowances specifically for student@demo.com (index STD001 / Student ID 1)
        // and a few others to show bulk disbursement
        $mainStudent = collect($studentsList)->first();
        if ($mainStudent && $allowanceScheme) {
            // Month -3: Approved
            ScholarshipRecipient::create([
                'scholarship_id' => $allowanceScheme->id,
                'student_id' => $mainStudent->id,
                'academic_session_id' => $session->id,
                'amount_awarded' => 250.00,
                'award_date' => now()->subMonths(3)->toDateString(),
                'status' => 'approved',
            ]);
            // Month -2: Approved
            ScholarshipRecipient::create([
                'scholarship_id' => $allowanceScheme->id,
                'student_id' => $mainStudent->id,
                'academic_session_id' => $session->id,
                'amount_awarded' => 250.00,
                'award_date' => now()->subMonths(2)->toDateString(),
                'status' => 'approved',
            ]);
            // Month -1: Applied (Pending Approval)
            ScholarshipRecipient::create([
                'scholarship_id' => $allowanceScheme->id,
                'student_id' => $mainStudent->id,
                'academic_session_id' => $session->id,
                'amount_awarded' => 250.00,
                'award_date' => now()->subMonth()->toDateString(),
                'status' => 'applied',
            ]);
            // Current Month: Applied (Pending Approval)
            ScholarshipRecipient::create([
                'scholarship_id' => $allowanceScheme->id,
                'student_id' => $mainStudent->id,
                'academic_session_id' => $session->id,
                'amount_awarded' => 250.00,
                'award_date' => now()->toDateString(),
                'status' => 'applied',
            ]);
        }

        // 13. Seed Fee Structures & Payments
        $feeStructuresList = [];
        $sessionsToSeed = [$previousSession, $session];

        foreach ($sessionsToSeed as $targetSession) {
            foreach ($programsList as $prog) {
                for ($lvl = 100; $lvl <= 400; $lvl += 100) {
                    if ($lvl > 200 && $prog->program_length == 2) {
                        continue; // Skip level 300, 400 for 2-year diploma
                    }

                    $tuition = $prog->cost;
                    $lib = 50.00;
                    $lab = $prog->id == 3 ? 150.00 : 80.00;
                    $med = 40.00;
                    $sports = 30.00;
                    $exam = 60.00;
                    $total = $tuition + $lib + $lab + $med + $sports + $exam;

                    $fs = FeeStructure::create([
                        'program_id' => $prog->id,
                        'level' => $lvl,
                        'session_id' => $targetSession->id,
                        'semester_id' => null, // Yearly default
                        'tuition_fee' => $tuition,
                        'library_fee' => $lib,
                        'lab_fee' => $lab,
                        'medical_fee' => $med,
                        'sports_fee' => $sports,
                        'examination_fee' => $exam,
                        'total_amount' => $total,
                        'created_by' => $adminUser->id,
                    ]);
                    $feeStructuresList[] = $fs;

                    // Seed dynamic items
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Tuition Fee']->id,
                        'amount' => $tuition,
                    ]);
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Library Fee']->id,
                        'amount' => $lib,
                    ]);
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Lab Fee']->id,
                        'amount' => $lab,
                    ]);
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Medical Fee']->id,
                        'amount' => $med,
                    ]);
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Sports Fee']->id,
                        'amount' => $sports,
                    ]);
                    FeeStructureItem::create([
                        'fee_structure_id' => $fs->id,
                        'fee_component_id' => $components['Examination Fee']->id,
                        'amount' => $exam,
                    ]);
                }
            }
        }

        // Add Payments for students
        foreach ($studentsList as $idx => $student) {
            $currentYear = (int) $student->current_year;
            
            // Seed previous session payments for students in levels > 100
            if ($currentYear > 100) {
                $prevLevel = $currentYear - 100;
                $prevFeeStr = collect($feeStructuresList)
                    ->where('program_id', $student->program_id)
                    ->where('level', $prevLevel)
                    ->where('session_id', $previousSession->id)
                    ->first();

                if ($prevFeeStr) {
                    $payOption = $idx % 3;
                    if ($payOption === 0) {
                        // Fully paid previous year
                        Payment::create([
                            'student_id' => $student->id,
                            'fee_structure_id' => $prevFeeStr->id,
                            'amount_paid' => $prevFeeStr->total_amount,
                            'payment_method' => 'Bank Transfer',
                            'payment_date' => '2024-11-15',
                            'reference_number' => 'PREV-REF-'.rand(10000000, 99999999),
                            'status' => 'completed',
                            'received_by' => $adminUser->id,
                        ]);
                    } elseif ($payOption === 1) {
                        // Partially paid (owes 350.00 arrears)
                        Payment::create([
                            'student_id' => $student->id,
                            'fee_structure_id' => $prevFeeStr->id,
                            'amount_paid' => max(0.01, $prevFeeStr->total_amount - 350.00),
                            'payment_method' => 'Mobile Money',
                            'payment_date' => '2024-12-05',
                            'reference_number' => 'PREV-REF-'.rand(10000000, 99999999),
                            'status' => 'completed',
                            'received_by' => $adminUser->id,
                        ]);
                    }
                    // Option 2: unpaid previous year (owes full previous year)
                }
            }

            // Seed current session payments
            $feeStr = collect($feeStructuresList)
                ->where('program_id', $student->program_id)
                ->where('level', $currentYear)
                ->where('session_id', $session->id)
                ->first();

            if ($feeStr) {
                $paymentOption = $idx % 3;
                if ($paymentOption === 0) {
                    Payment::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStr->id,
                        'amount_paid' => $feeStr->total_amount,
                        'payment_method' => 'Bank Transfer',
                        'payment_date' => now()->subDays(rand(5, 30))->format('Y-m-d'),
                        'reference_number' => 'REF-'.rand(10000000, 99999999),
                        'status' => 'completed',
                        'received_by' => $adminUser->id,
                    ]);
                } elseif ($paymentOption === 1) {
                    Payment::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStr->id,
                        'amount_paid' => $feeStr->total_amount / 2,
                        'payment_method' => 'Mobile Money',
                        'payment_date' => now()->subDays(rand(5, 30))->format('Y-m-d'),
                        'reference_number' => 'REF-'.rand(10000000, 99999999),
                        'status' => 'completed',
                        'received_by' => $adminUser->id,
                    ]);
                }
            }
        }

        // Run Ledger sync using FeeCalculationService for all students
        $feeCalculationService = new \App\Services\Finance\FeeCalculationService();
        foreach ($studentsList as $student) {
            $feeCalculationService->syncFeePaymentLedger($student, $session);
        }

        // 14. Seed Results & Grades (Expanded and Relational)
        $gradePointsList = GradePoint::all();

        $getGradeDetails = function ($score) use ($gradePointsList) {
            foreach ($gradePointsList as $gp) {
                if ($score >= $gp->min_score && $score <= $gp->max_score) {
                    return [
                        'grade' => $gp->grade,
                        'grade_points' => $gp->points,
                    ];
                }
            }

            return ['grade' => 'F', 'grade_points' => 0.0];
        };

        // For all students, seed completed courses based on program and year levels (current and previous)
        foreach ($studentsList as $student) {
            $currentYearInt = intval($student->current_year ?? '100');
            $yearLevels = [];
            for ($y = 100; $y <= $currentYearInt; $y += 100) {
                $yearLevels[] = strval($y / 100);
            }

            $studentCourses = Course::where('program_id', $student->program_id)
                ->whereIn('year_level', $yearLevels)
                ->get();

            foreach ($studentCourses as $course) {
                // With an 80% probability, seed a result/grade
                if (rand(0, 10) < 8) {
                    $assignment = TeacherAssignment::where('course_id', $course->id)->first();
                    if ($assignment) {
                        $teacher = Teacher::find($assignment->teacher_id);
                    } else {
                        $teacher = collect($teachersList)->random();
                    }

                    $attendanceScore = floatval(rand(6, 10));
                    $midsemScore = floatval(rand(12, 20));
                    $projectScore = floatval(rand(6, 10));
                    $classScore = $attendanceScore + $midsemScore + $projectScore;
                    $examScore = floatval(rand(30, 60));
                    $totalScore = $classScore + $examScore;

                    $gradeDetails = $getGradeDetails($totalScore);

                    $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
                    $courseLevelStr = strval(intval($course->year_level ?? '1') * 100);
                    
                    $resultSessionId = ((int) $courseLevelStr < (int) $student->current_year) ? $previousSession->id : $session->id;
                    
                    $slip = ResultSlip::firstOrCreate([
                        'teacher_id' => $teacher->id,
                        'program_id' => $student->program_id,
                        'course_id' => $course->id,
                        'academic_session_id' => $resultSessionId,
                        'level' => $courseLevelStr,
                        'semester' => $semesterVal,
                    ], [
                        'status' => 'approved',
                        'approved_by' => $adminUser->id,
                        'approved_at' => now(),
                    ]);

                    Result::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'academic_session_id' => $resultSessionId,
                        'score' => $totalScore,
                        'grade' => $gradeDetails['grade'],
                        'grade_points' => $gradeDetails['grade_points'],
                        'entered_by' => $teacher->user_id,
                        'entered_date' => now()->subWeeks(rand(1, 3)),
                        'result_token' => 'RES-'.$course->code.'-'.Str::random(10),
                        'teacher_id' => $teacher->id,
                        'result_slip_id' => $slip->id,
                        'admin_amended' => false,
                    ]);

                    Grade::create([
                        'result_slip_id' => $slip->id,
                        'student_id' => $student->id,
                        'teacher_id' => $teacher->id,
                        'attendance_score' => $attendanceScore,
                        'midsem_score' => $midsemScore,
                        'project_score' => $projectScore,
                        'class_score' => $classScore,
                        'exam_score' => $examScore,
                    ]);
                }
            }
        }

        // 14.1 Seed Academic Information (GPA & Attendance)
        foreach ($studentsList as $student) {
            $sessions = Result::where('student_id', $student->id)
                ->select('academic_session_id')
                ->distinct()
                ->pluck('academic_session_id');

            foreach ($sessions as $sessionId) {
                $sessionModel = AcademicSession::find($sessionId);
                if (!$sessionModel) {
                    continue;
                }

                $studentResults = Result::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->get();

                if ($studentResults->isEmpty()) {
                    continue;
                }

                $totalPts = floatval($studentResults->sum('grade_points'));
                $count = $studentResults->count();
                $gpa = $count > 0 ? ($totalPts / $count) : 0.0;

                $classLevel = $student->current_year;
                if ($sessionId === $previousSession->id) {
                    $classLevel = (string) max(100, (int)$student->current_year - 100);
                }

                \App\Models\AcademicInformation::create([
                    'student_id' => $student->id,
                    'class_level' => $classLevel,
                    'section' => 'A',
                    'academic_session' => $sessionModel->name,
                    'program_id' => $student->program_id,
                    'major_field' => $student->program?->name,
                    'gpa' => $gpa,
                    'attendance_record' => rand(85, 120),
                    'result_id' => $studentResults->first()->id,
                ]);
            }
        }

        // 15. Seed Disciplinary Records (Randomized)
        $disciplinaryInfractions = [
            ['offense' => 'Academic dishonesty during mid-semester examination.', 'action' => 'Suspension for 1 semester.'],
            ['offense' => 'Destruction of campus library computer resources.', 'action' => 'Fine of GHS 500 and written apology.'],
            ['offense' => 'Violation of residential hall curfew rules.', 'action' => 'Written warning and community service.'],
            ['offense' => 'Unauthorized entry into faculty server room.', 'action' => 'Suspension for 2 weeks and loss of computer laboratory privileges.'],
        ];

        $numCases = rand(4, 8);
        $disciplinedStudents = collect($studentsList)->random($numCases);
        foreach ($disciplinedStudents as $idx => $student) {
            $isLevel100 = ((int) $student->current_year === 100);
            $targetSession = $isLevel100 ? $session : (($idx % 2 === 0) ? $previousSession : $session);
            $case = fake()->randomElement($disciplinaryInfractions);
            DisciplinaryRecord::create([
                'index_number' => $student->index_number,
                'fullname' => $student->firstname.' '.$student->lastname,
                'program_id' => $student->program_id,
                'academic_session_id' => $targetSession->id,
                'offense' => $case['offense'],
                'action_taken' => $case['action'],
                'comments' => 'Student was cooperative and expressed remorse during the disciplinary panel review.',
                'date_of_action' => ($targetSession->id === $previousSession->id) 
                    ? now()->subYear()->subDays(rand(10, 60))->format('Y-m-d')
                    : now()->subDays(rand(10, 60))->format('Y-m-d'),
                'return_date' => ($targetSession->id === $previousSession->id) 
                    ? now()->subYear()->addDays(rand(15, 45))->format('Y-m-d')
                    : now()->addDays(rand(15, 45))->format('Y-m-d'),
                'return_status' => ($targetSession->id === $previousSession->id) ? true : false,
            ]);
        }

        // 16. Seed Evaluation Forms & Questions & Responses
        $evalForm = EvaluationForm::create([
            'title' => 'First Semester Lecturer Performance Evaluation',
            'academic_year' => '2025/2026',
            'unique_code' => 'EVAL-2025-SEM1',
            'start_time' => now()->subDays(10),
            'end_time' => now()->addDays(20),
            'control_type' => 'manual',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        $evalQuestions = [
            [
                'text' => 'The lecturer explains the course concepts clearly and understandably.',
                'type' => 'scale_5',
            ],
            [
                'text' => 'The lecturer is punctual and regular for classes.',
                'type' => 'scale_5',
            ],
            [
                'text' => 'Rate the overall quality of course materials and slides provided on a scale from 1 to 10.',
                'type' => 'scale_10',
            ],
            [
                'text' => 'Would you recommend this lecturer to other students next semester?',
                'type' => 'boolean',
            ],
            [
                'text' => 'Which resource was most helpful for your learning in this course?',
                'type' => 'select_single',
                'options' => ['Lecture Slides', 'Reference Textbooks', 'Online Videos/Labs', 'Discussion Forums'],
            ],
            [
                'text' => 'Select all teaching methodologies that the lecturer regularly employed.',
                'type' => 'select_multiple',
                'options' => ['Interactive lectures', 'Group projects', 'Live coding demos', 'Case study analysis', 'Guest speakers'],
            ],
            [
                'text' => 'Provide any constructive feedback or comments for this lecturer.',
                'type' => 'text_long',
            ],
        ];

        $questionIds = [];
        foreach ($evalQuestions as $idx => $q) {
            $createdQ = EvaluationQuestion::create([
                'form_id' => $evalForm->id,
                'question_text' => $q['text'],
                'question_order' => $idx + 1,
                'rating_type' => $q['type'],
                'is_required' => in_array($q['type'], ['scale_5', 'scale_10', 'boolean', 'select_single'], true),
                'options_json' => isset($q['options']) ? $q['options'] : null,
                'created_by' => $adminUser->id,
            ]);
            $questionIds[] = $createdQ->id;
        }

        // Seed evaluation responses
        $evalCohortSize = rand(15, min(count($studentsList), 50));
        $evaluatingStudents = collect($studentsList)->random($evalCohortSize);

        $scale5Ratings = [5, 5, 5, 5, 5, 4, 4, 4, 4, 4, 3, 3, 2, 1];
        $scale10Ratings = [10, 10, 9, 9, 9, 8, 8, 8, 8, 7, 7, 6, 5, 4, 2];
        $commentsPool = [
            'Excellent instructor who explains complex topics with great clarity. Always willing to help.',
            'Great class, but the assignments were graded a bit late. The lecture slides are very helpful.',
            'The practical coding exercises were amazing! I learned a lot of industry-relevant skills.',
            'Lectures can be dry at times, but the professor is extremely knowledgeable and supportive.',
            'Very punctual and structured course. I highly recommend taking this class.',
            'The exams were quite tough and required deep understanding, but the review sessions helped.',
            'Could provide more real-world examples during lectures. Overall, a decent learning experience.',
            'Super energetic and interactive teaching style. Keeps the class fully engaged!',
            'Sometimes moves through slides too quickly. A bit more pace control would be appreciated.',
            'The group project was very challenging but taught me how to work effectively in a team.',
            'Always approachable during office hours. Great guidance on our final presentations.',
            'Feedback on assignments was detailed and constructive. Helped me improve my grades.',
            'The class was very engaging, though I wish there were fewer quizzes and more coding tasks.',
            'Outstanding lecturer. Explains concepts from multiple perspectives to ensure everyone understands.',
        ];

        foreach ($evaluatingStudents as $estd) {
            $randomTeacher = collect($teachersList)->random();

            $response = EvaluationResponse::create([
                'form_id' => $evalForm->id,
                'student_id' => $estd->user_id,
                'teacher_id' => $randomTeacher->user_id,
                'student_department_id' => $estd->department_id,
                'response_code' => 'RESP-'.Str::random(10),
                'status' => 'submitted',
                'submitted_at' => now()->subDays(rand(1, 5)),
            ]);

            foreach ($questionIds as $qId) {
                $qObj = EvaluationQuestion::find($qId);
                $answerValue = null;
                $answerText = null;

                if ($qObj->rating_type === 'scale_5') {
                    $answerValue = $scale5Ratings[array_rand($scale5Ratings)];
                } elseif ($qObj->rating_type === 'scale_10') {
                    $answerValue = $scale10Ratings[array_rand($scale10Ratings)];
                } elseif ($qObj->rating_type === 'boolean') {
                    $isYes = rand(0, 10) < 8;
                    $answerValue = $isYes ? 1 : 0;
                    $answerText = $isYes ? 'yes' : 'no';
                } elseif ($qObj->rating_type === 'select_single') {
                    $opts = $qObj->options_json;
                    $answerText = $opts[array_rand($opts)];
                } elseif ($qObj->rating_type === 'select_multiple') {
                    $opts = $qObj->options_json;
                    $numToPick = rand(1, min(3, count($opts)));
                    $picked = (array) array_rand(array_flip($opts), $numToPick);
                    $answerText = json_encode(array_values($picked));
                } elseif ($qObj->rating_type === 'text_long') {
                    $answerText = $commentsPool[array_rand($commentsPool)];
                }

                ResponseDetail::create([
                    'response_id' => $response->id,
                    'question_id' => $qId,
                    'question_text_snapshot' => $qObj->question_text,
                    'answer_value' => $answerValue,
                    'answer_text' => $answerText,
                ]);
            }
        }

        // 17. Seed Announcements
        $teacherSarah = $teachersList[0];
        if ($coursesList[0]) {
            Announcement::create([
                'course_id' => $coursesList[0]->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacherSarah->id,
                'title' => 'Welcome to the Course',
                'body' => 'This is a demo announcement to showcase course-level updates. You can edit, create, or delete announcements as needed.',
                'status' => 'active',
                'published' => true,
                'approved_by' => $adminUser->id,
                'approved_date' => now()->subDays(2),
            ]);
        }

        // 18. Seed Administrative Memos (5 to 10)
        $secretaryUser = User::where('username', 'secretary_demo')->first();
        $hodUser = User::where('username', 'hod_demo')->first();

        $memosData = [
            [
                'title' => 'First Semester Examination Protocol & Guidelines',
                'content' => 'All lecturers and academic invigilators are required to adhere to the strict examination guidelines. Question papers must be submitted to the HOD office by next week. Students must be vetted for ghana card or registration slip before entry.',
                'confidentiality' => 'internal',
                'recipient_type' => 'role',
                'recipient_role_id' => UserRole::where('name', 'teacher')->first()?->id,
                'status' => 'sent',
                'sender' => $secretaryUser,
                'signatories' => [
                    ['user_id' => $hodUser->id, 'status' => 'signed', 'remarks' => 'Approved from department perspective.'],
                    ['user_id' => $adminUser->id, 'status' => 'signed', 'remarks' => 'Final approval granted. Publish immediately.'],
                ],
            ],
            [
                'title' => 'Quarterly Academic Review and Performance Meeting',
                'content' => 'Notice is hereby given for the quarterly review meeting of all academic staff. We will discuss evaluation responses, student feedback, and curriculum modifications for the next semester. Attendance is compulsory.',
                'confidentiality' => 'internal',
                'recipient_type' => 'department',
                'recipient_entity_id' => 1,
                'status' => 'pending_signature',
                'sender' => $secretaryUser,
                'signatories' => [
                    ['user_id' => $hodUser->id, 'status' => 'pending', 'remarks' => null],
                ],
            ],
            [
                'title' => 'Proposed Development of Computing Lab Complex',
                'content' => 'The proposal for the expansion of the Computing and Computer Engineering Lab facilities has been approved. The budget estimate is attached for review. HODs must submit equipment requirements.',
                'confidentiality' => 'confidential',
                'recipient_type' => 'department',
                'recipient_entity_id' => 2,
                'status' => 'draft',
                'sender' => $secretaryUser,
                'signatories' => [],
            ],
            [
                'title' => 'STEM Research Grants Opportunities (2026)',
                'content' => 'The school board is offering local research grants for projects focusing on Applied Engineering and Artificial Intelligence. Proposals should be submitted through the department heads by the end of June.',
                'confidentiality' => 'public',
                'recipient_type' => 'faculty',
                'recipient_entity_id' => 1,
                'status' => 'sent',
                'sender' => $hodUser,
                'signatories' => [
                    ['user_id' => $hodUser->id, 'status' => 'signed', 'remarks' => 'Self-signed by HOD.'],
                ],
            ],
            [
                'title' => 'Annual Campus Security and Audit Notice',
                'content' => 'Please note that an audit of security credentials, card passes, and student clearance procedures is underway. Non-compliance must be reported to the registrar office immediately.',
                'confidentiality' => 'public',
                'recipient_type' => 'user',
                'recipient_entity_id' => $teachersList[0]->user_id,
                'status' => 'sent',
                'sender' => $secretaryUser,
                'signatories' => [
                    ['user_id' => $adminUser->id, 'status' => 'signed', 'remarks' => 'Approved by Principal.'],
                ],
            ],
        ];

        foreach ($memosData as $mData) {
            $sender = $mData['sender'] ?? $adminUser;

            $memo = Memo::create([
                'title' => $mData['title'],
                'content' => $mData['content'],
                'sender_id' => $sender->id,
                'sender_entity_type' => ($sender->type === 'teacher') ? 'department' : 'user',
                'sender_entity_id' => ($sender->type === 'teacher') ? $sender->teacher?->department_id : $sender->id,
                'recipient_type' => $mData['recipient_type'],
                'recipient_entity_id' => $mData['recipient_entity_id'] ?? null,
                'recipient_role_id' => $mData['recipient_role_id'] ?? null,
                'confidentiality_level' => $mData['confidentiality'],
                'status' => $mData['status'],
                'signing_user_id' => $adminUser->id,
            ]);

            // Seed signatories
            foreach ($mData['signatories'] as $sigData) {
                \App\Models\MemoSignatory::create([
                    'memo_id' => $memo->id,
                    'user_id' => $sigData['user_id'],
                    'status' => $sigData['status'],
                    'remarks' => $sigData['remarks'],
                    'signed_at' => ($sigData['status'] === 'signed') ? now()->subHours(rand(1, 10)) : null,
                ]);
            }

            // Seed Memo Tracking Logs
            MemoTracking::create([
                'memo_id' => $memo->id,
                'from_entity_type' => $memo->sender_entity_type,
                'from_entity_id' => $memo->sender_entity_id,
                'to_entity_type' => $memo->recipient_type,
                'to_entity_id' => $memo->recipient_entity_id,
                'forwarded_by' => $sender->id,
                'action' => $memo->status === 'sent' ? 'sent' : ($memo->status === 'pending_signature' ? 'returned' : 'saved'),
                'remarks' => 'Memo initialized in demo data seeding.',
            ]);

            if ($memo->status === 'sent') {
                $recipients = $memo->resolveTargetRecipients();
                foreach ($recipients as $recipient) {
                    $viewed = rand(0, 1);
                    $acknowledged = $viewed && rand(0, 1);
                    
                    \App\Models\MemoReadReceipt::create([
                        'memo_id' => $memo->id,
                        'user_id' => $recipient->id,
                        'viewed_at' => $viewed ? now()->subHours(rand(1, 24)) : null,
                        'acknowledged_at' => $acknowledged ? now()->subMinutes(rand(1, 59)) : null,
                    ]);
                }
            }
        }

        // 19. Seed Transcript Requests (for demo data testing)
        $level400Students = collect($studentsList)->filter(fn($s) => (int) $s->current_year >= 400)->values();
        if ($level400Students->isNotEmpty()) {
            // Seed a pending request
            \App\Models\TranscriptRequest::create([
                'student_id' => $level400Students[0]->id,
                'status' => 'pending',
                'purpose' => 'Graduate Studies Application at MIT',
            ]);

            // Seed a processed request
            if ($level400Students->count() > 1) {
                \App\Models\TranscriptRequest::create([
                    'student_id' => $level400Students[1]->id,
                    'status' => 'processed',
                    'purpose' => 'Employment Background Check',
                    'processed_by' => $adminUser->id,
                    'processed_at' => now()->subDays(2),
                ]);
            }

            // Seed a rejected request
            if ($level400Students->count() > 2) {
                \App\Models\TranscriptRequest::create([
                    'student_id' => $level400Students[2]->id,
                    'status' => 'rejected',
                    'purpose' => 'Personal Archives',
                    'remarks' => 'Please clear outstanding tuition and library fines.',
                    'processed_by' => $adminUser->id,
                    'processed_at' => now()->subDays(1),
                ]);
            }
        }

        // 20. Seed Timetable and Slots
        $csProgram = $programsList[0]; // BSc Computer Science
        $csLevel = 100;
        
        $timetable = \App\Models\Timetable::create([
            'program_id' => $csProgram->id,
            'level' => $csLevel,
            'session_id' => $session->id,
            'created_by' => $adminUser->id,
        ]);

        $csCourses = collect($coursesList)->where('program_id', $csProgram->id)->where('year_level', '1')->values();
        $teachers = collect($teachersList);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $dayIndex => $dayName) {
            $course = $csCourses[$dayIndex % count($csCourses)];
            $teacher = $teachers->first(fn($t) => $t->department_id === $csProgram->department_id) ?? $teachers->random();
            
            \App\Models\TimetableClass::create([
                'timetable_id' => $timetable->id,
                'program_id' => $csProgram->id,
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'day' => $dayName,
                'start_time' => '09:00:00',
                'end_time' => '11:30:00',
                'venue' => 'Room 204, IT Block',
            ]);

            if ($dayIndex % 2 === 0) {
                $otherCourse = $csCourses[($dayIndex + 1) % count($csCourses)];
                \App\Models\TimetableClass::create([
                    'timetable_id' => $timetable->id,
                    'program_id' => $csProgram->id,
                    'course_id' => $otherCourse->id,
                    'teacher_id' => $teacher->id,
                    'day' => $dayName,
                    'start_time' => '13:00:00',
                    'end_time' => '15:00:00',
                    'venue' => 'Main Lab 1',
                ]);
            }
        }

        // 21. Seed Course Materials & Teacher Attendance Sheets for realism
        $timetableClasses = \App\Models\TimetableClass::all();
        $semesters = Semester::all();

        foreach ($timetableClasses as $index => $tClass) {
            // Assign session and semester context
            $targetSemester = $semesters[$index % count($semesters)];
            $targetSession = $targetSemester->academicSession;

            if ($index % 2 === 0) {
                CourseMaterial::create([
                    'course_id' => $tClass->course_id,
                    'teacher_id' => $tClass->teacher_id,
                    'academic_session_id' => $targetSession->id,
                    'semester_id' => $targetSemester->id,
                    'title' => 'Lecture Note ' . (($index % 3) + 1) . ' - ' . $tClass->course?->name,
                    'description' => 'Comprehensive slides and reference materials for this weeks lecture topics.',
                    'file_path' => 'materials/demo_pdf_' . $index . '.pdf',
                    'file_type' => 'pdf',
                    'status' => 'approved',
                    'published' => true,
                    'approved_by' => $adminUser->id,
                    'approved_date' => now()->subDays(15),
                ]);
            }

            if ($index % 3 === 0) {
                TeacherAttendanceSheet::create([
                    'teacher_id' => $tClass->teacher_id,
                    'course_id' => $tClass->course_id,
                    'academic_session_id' => $targetSession->id,
                    'semester_id' => $targetSemester->id,
                    'class_date' => now()->subDays($index + 2)->format('Y-m-d'),
                    'file_path' => 'attendance/sheet_' . $index . '.xlsx',
                    'original_name' => 'attendance_' . strtolower($tClass->course?->code ?? '') . '_' . now()->subDays($index + 2)->format('Ymd') . '.xlsx',
                ]);
            }
        }

        // Seed default Settings
        $settings = [
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.student_grading_redirect',
                'setting_value' => '0',
                'data_type' => 'boolean',
                'description' => 'Redirect students to external grading software',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.allow_student_self_registration',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Allow student self-registration',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.enable_email_notifications',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Enable system email alerts',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.memos_require_signature',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Memos require official signatures before dispatch',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.memos_multiple_signatories',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Memos support multiple concurrent signatories',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.show_detailed_bill_breakdown',
                'setting_value' => '0',
                'data_type' => 'boolean',
                'description' => 'Show detailed itemized fee breakdown to students',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.show_attendance_policy',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Show class attendance policy disclaimer to students',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'system_preferences',
                'setting_key' => 'system_preferences.min_attendance_threshold',
                'setting_value' => '75',
                'data_type' => 'integer',
                'description' => 'Default minimum attendance percentage required for exams',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_r',
                'setting_value' => '255',
                'data_type' => 'integer',
                'description' => 'Passport background R',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_g',
                'setting_value' => '0',
                'data_type' => 'integer',
                'description' => 'Passport background G',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_b',
                'setting_value' => '0',
                'data_type' => 'integer',
                'description' => 'Passport background B',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_tolerance',
                'setting_value' => '120',
                'data_type' => 'integer',
                'description' => 'Passport background tolerance',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'finance',
                'setting_key' => 'system_preferences.fee_billing_cycle',
                'setting_value' => 'year',
                'data_type' => 'string',
                'description' => 'Billing cycle preference (year or semester)',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'memos',
                'setting_key' => 'system_preferences.strict_departmental_access',
                'setting_value' => '0',
                'data_type' => 'boolean',
                'description' => 'Limit memo visibility to active departmental members only',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'memos',
                'setting_key' => 'system_preferences.thread_isolation_on_forward',
                'setting_value' => '0',
                'data_type' => 'boolean',
                'description' => 'Isolate memo history snapshot upon forwarding to new departments',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'leave',
                'setting_key' => 'system_preferences.emergency_leave_enabled',
                'setting_value' => '0',
                'data_type' => 'boolean',
                'description' => 'Allow submittals outside application windows for emergency cases',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'leave',
                'setting_key' => 'system_preferences.leave_submission_start',
                'setting_value' => '2026-06-01',
                'data_type' => 'string',
                'description' => 'Staff leave request submission window start date',
                'updated_by' => $adminUser->id,
            ],
            [
                'category' => 'leave',
                'setting_key' => 'system_preferences.leave_submission_end',
                'setting_value' => '2026-07-31',
                'data_type' => 'string',
                'description' => 'Staff leave request submission window end date',
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        // Seed initial Job Board & Activities
        \App\Models\JobAlert::create([
            'title' => 'Software Engineer Intern',
            'type' => 'job',
            'company_or_organizer' => 'Google Ghana',
            'description' => '<p>Join the Android Platform team in Accra for a 6-month software engineering internship. Work on modern mobile applications and APIs.</p>',
            'requirements' => 'Proficiency in Java, Kotlin, or Go. Currently enrolled in Level 300/400 Computer Science program.',
            'expiry_date' => now()->addWeeks(3)->toDateString(),
        ]);

        \App\Models\JobAlert::create([
            'title' => 'Student Tutor - Calculus I',
            'type' => 'activity',
            'company_or_organizer' => 'Mathematics Department',
            'description' => '<p>Help first-year students with basic calculus concepts, limits, derivatives, and applications. Conduct bi-weekly review sessions.</p>',
            'requirements' => 'Grade A in Calculus I. Strong communication skills.',
            'expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        \App\Models\JobAlert::create([
            'title' => 'Part-Time Library Assistant',
            'type' => 'job',
            'company_or_organizer' => 'Apex Central Library',
            'description' => '<p>Assist in cataloging new arrivals, managing student book loans, and organizing reading rooms. 10 hours per week maximum.</p>',
            'requirements' => 'Good organizational skills. No experience needed.',
            'expiry_date' => now()->subDays(5)->toDateString(), // Expired (Recently Closed)
        ]);

        \App\Models\JobAlert::create([
            'title' => 'Annual Hackathon 2026',
            'type' => 'activity',
            'company_or_organizer' => 'Computer Science Club',
            'description' => '<p>Apex annual 48-hour hackathon. Build innovative solutions for local environmental and healthcare challenges.</p>',
            'requirements' => 'Open to all students. Registration required.',
            'expiry_date' => now()->subMonths(4)->toDateString(), // Archived
        ]);

        // Seed staff leave requests
        $mainTeacher = collect($teachersList)->first();
        if ($mainTeacher) {
            LeaveRequest::create([
                'user_id' => $mainTeacher->user_id,
                'staff_leave_type_id' => $seniorStaffLeave->id,
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-15',
                'requested_days' => 5,
                'reason' => 'Annual family vacation and rest',
                'status' => 'approved',
                'reviewer_id' => $hodUser->id,
                'reviewed_at' => '2026-06-05 10:00:00',
            ]);

            LeaveRequest::create([
                'user_id' => $mainTeacher->user_id,
                'staff_leave_type_id' => $seniorStaffLeave->id,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-07',
                'requested_days' => 6,
                'reason' => 'Dental appointment and recovery',
                'status' => 'pending',
            ]);
        }

        // Seed products, invoices and expenditures
        $prod1 = Product::create([
            'name' => 'Semester Registration Kit',
            'sku' => 'PROD-REG-KIT',
            'category' => 'Registrar',
            'unit_price' => 15.00,
            'description' => 'Standard orientation and registration package',
        ]);
        $prod2 = Product::create([
            'name' => 'Official Academic Transcript',
            'sku' => 'PROD-TRANSCRIPT',
            'category' => 'Registrar',
            'unit_price' => 50.00,
            'description' => 'Printed academic record sheet',
        ]);
        $prod3 = Product::create([
            'name' => 'Graduation Gown Hire',
            'sku' => 'PROD-GRAD-GOWN',
            'category' => 'Ceremony',
            'unit_price' => 120.00,
            'description' => 'Rental of graduation cap and gown',
        ]);

        $inv1 = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'vendor_name' => 'Apex Supplies Ltd',
            'description' => 'Invoice for orientation packages and transcript materials',
            'amount' => 65.00,
            'invoice_date' => '2026-06-01',
            'due_date' => '2026-07-15',
            'status' => 'paid',
            'created_by' => $adminUser->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'product_id' => $prod1->id,
            'quantity' => 1,
            'unit_price' => 15.00,
            'total_amount' => 15.00,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'product_id' => $prod2->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'total_amount' => 50.00,
        ]);

        $inv2 = Invoice::create([
            'invoice_number' => 'INV-2026-0002',
            'vendor_name' => 'Ghana Gown Rentals',
            'description' => 'Rental gowns for the 2026 graduation ceremony',
            'amount' => 120.00,
            'invoice_date' => '2026-06-05',
            'due_date' => '2026-07-20',
            'status' => 'unpaid',
            'created_by' => $adminUser->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'product_id' => $prod3->id,
            'quantity' => 1,
            'unit_price' => 120.00,
            'total_amount' => 120.00,
        ]);

        // Expenditure 1: Pay off invoice 1
        Expenditure::create([
            'invoice_id' => $inv1->id,
            'expense_number' => 'EXP-2026-0001',
            'amount' => 65.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-06-10',
            'category' => 'Registrar Supplies',
            'notes' => 'Settled invoice INV-2026-0001',
            'recorded_by' => $adminUser->id,
        ]);

        // Expenditure 2: Independent expense
        Expenditure::create([
            'invoice_id' => null,
            'expense_number' => 'EXP-2026-0002',
            'amount' => 150.00,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-20',
            'category' => 'Office Supplies',
            'notes' => 'Printer papers and cartridges',
            'recorded_by' => $adminUser->id,
        ]);

        // Expenditure 3: Independent expense
        Expenditure::create([
            'invoice_id' => null,
            'expense_number' => 'EXP-2026-0003',
            'amount' => 500.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-06-22',
            'category' => 'Maintenance',
            'notes' => 'Server AC repair',
            'recorded_by' => $adminUser->id,
        ]);

        // Seed system audit logs under respective users to showcase contextual timelines
        // Invoice logging under Demo Admin
        auth()->login($adminUser);
        \App\Helpers\AuditHelper::log(
            'invoice_created',
            "Invoice {$inv1->invoice_number} created for vendor Apex Supplies Ltd by Demo Admin",
            $inv1
        );
        \App\Helpers\AuditHelper::log(
            'invoice_paid',
            "Invoice {$inv1->invoice_number} marked as paid by Demo Admin",
            $inv1
        );
        \App\Helpers\AuditHelper::log(
            'invoice_created',
            "Invoice {$inv2->invoice_number} created for vendor Ghana Gown Rentals by Demo Admin",
            $inv2
        );

        // Leave request logging (request under teacher/staff, approval under reviewer/HOD)
        $leaveRequests = LeaveRequest::all();
        foreach ($leaveRequests as $lr) {
            $requester = User::find($lr->user_id);
            if ($requester) {
                auth()->login($requester);
                \App\Helpers\AuditHelper::log(
                    'leave_submitted',
                    "Leave request submitted by {$requester->name}",
                    $lr
                );
            }

            if ($lr->status === 'approved') {
                $reviewer = User::find($lr->reviewer_id ?? $hodUser->id);
                if ($reviewer) {
                    auth()->login($reviewer);
                    \App\Helpers\AuditHelper::log(
                        'leave_approved',
                        "Leave request approved by {$reviewer->name}",
                        $lr
                    );
                }
            }
        }

        // Expenditure logging under Demo Admin
        auth()->login($adminUser);
        $expenditures = Expenditure::all();
        foreach ($expenditures as $exp) {
            \App\Helpers\AuditHelper::log(
                'expenditure_recorded',
                "Expenditure {$exp->expense_number} recorded by Demo Admin",
                $exp
            );
        }

        // Settings change audit log under Demo Admin
        \App\Helpers\AuditHelper::log(
            'settings_updated',
            "System preferences updated and verified by Demo Admin",
            null,
            ['category' => 'system_preferences']
        );

        // Clear session after seeding
        auth()->logout();
    }
}
