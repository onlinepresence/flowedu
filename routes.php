<?php
    // '/uri' => ['file' => '', 'middleware' => []],

    return [
        '/' => ['file' => 'pages/login.php', 'middleware' => ["check_school"]],
        '/shutdown' => ['file' => 'shutdown.php', 'middleware' => ['check_school']],
        '/register' => ['file' => 'pages/create-account.php', 'middleware' => ['admission_is_open']],
        '/logout' => ['file' => 'logout.php'],
        '/env-generator' => ['file' => 'pages/generate_env.php'],

        // verification of email
        '/send-verification' => ['file' => 'pages/email/send-email-verification.php', 'middleware' => ['auth']],
        '/verify-email/{token}' => ['file' => 'pages/email/verify-email.php', 'middleware' => ['check_school']],

        // admin routes
        '/admin-setup' => [
            'prefix' => '/admin-setup',
            'middleware' => ['auth'],
            'routes' => [
                '/personal' => ['file' => 'admin/setup/personal.php'],
                '/school' => ['file' => 'admin/setup/school.php'],
                '/programs' => ['file' => 'admin/setup/program.php', 'middleware' => ['check_departments']],
                '/halls' => ['file' => 'admin/setup/hall.php'],
                '/departments' => ['file' => 'admin/setup/department.php'],
                '/faculties' => ['file' => 'admin/setup/faculty.php'],
                '/activate' => ['file' => 'admin/setup/activate.php']
            ]
        ],

        '/admin' => [
            'prefix' => '/admin',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/dashboard' => ['file' => 'admin/dashboard.php'],
                '/approve-student/{index_number}/{guardian}/{id}' => ['file' => 'admin/approve-student.php'],
                '/students' => ['file' => 'admin/pages/students/index.php'],
                '/profile' => ['file' => 'admin/setup/personal.php']
            ]
        ],

        '/admin/staff' => [
            'prefix' => '/admin/staff',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/' => ['file' => 'admin/pages/staff/index.php'],
                '/teachers' => ['file' => 'admin/pages/staff/teachers.php'],
                '/non-teaching' => ['file' => 'admin/pages/staff/non-teaching.php'],
                
                // Teacher assignments and roles
                '/assignments' => ['file' => 'admin/pages/staff/assignments.php'], // Keep for backward compatibility, redirects to teacher-assignments
                '/teacher-assignments' => ['file' => 'admin/pages/staff/teacher-assignments.php'],
                '/teacher-roles' => ['file' => 'admin/pages/staff/teacher-roles.php'],
                
                // Staff assignments and roles
                '/staff-assignments' => ['file' => 'admin/pages/staff/staff-assignments.php'],
                '/staff-roles' => ['file' => 'admin/pages/staff/staff-roles.php'],
                
                // Legacy routes (keep for backward compatibility)
                '/roles' => ['file' => 'admin/pages/staff/roles.php'], // Keep for backward compatibility, redirects to teacher-roles
                
                '/materials' => ['file' => 'admin/pages/staff/materials.php'],
                '/announcements' => ['file' => 'admin/pages/staff/announcements.php'],

                // evaluation routes
                '/evaluations' => ['file' => 'admin/pages/staff/evaluation/index.php', 'name' => 'admin.evaluations'],
                '/evaluation/demo/{form_code}' => ['file' => 'pages/preview-evaluation.php', 'name' => 'admin.evaluation.preview'],
                '/evaluation/{form_code}' => ['file' => 'admin/pages/staff/evaluation/manage.php'],
                '/evaluation/{form_code}/{tab}' => ['file' => 'admin/pages/staff/evaluation/manage.php', 'name' => 'admin.evaluation'],
            ]
        ],

        // admin academic routes
        '/admin/academic' => [
            'prefix' => '/admin/academic',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/faculty' => ['file' => 'admin/setup/faculty.php'],
                '/department' => ['file' => 'admin/setup/department.php'],
                '/program' => ['file' => 'admin/setup/program.php'],
                '/program/{program_id}' => ['file' => 'admin/pages/course.php', "name" => "program.classes"],
                '/program/{program_id}/{form_level}' => ['file' => 'admin/pages/course.php', "name" => "program.manage"],
                '/sessions' => ['file' => 'admin/pages/session.php'],
                '/timetable' => ['file' => 'admin/pages/academic/timetable.php'],
            ]
        ],

        '/admin/students' => [
            'prefix' => '/admin/students',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/promotion' => ['file' => 'admin/pages/students/promotion.php'],
                '/graduation' => ['file' => 'admin/pages/students/graduation.php'],
                '/medical' => ['file' => 'admin/pages/students/medical.php'],
                '/discipline' => ['file' => 'admin/pages/students/discipline.php'],
            ]
        ],

        '/admin/settings' => [
            'prefix' => '/admin/settings',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/roles' => ['file' => 'admin/pages/settings/roles.php'],
                '/school' => ['file' => 'admin/setup/school.php'],
                '/users' => ['file' => 'admin/pages/settings/users.php'],
                '/backup' => ['file' => 'admin/pages/settings/backup.php'],
            ]
        ],

        '/admin/grading' => [
            'prefix' => '/admin/grading',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/points' => ['file' => 'admin/pages/grading/points.php'],
                '/enter' => ['file' => 'admin/pages/grading/enter.php'],
                '/upload' => ['file' => 'admin/pages/grading/upload.php'],
                '/transcripts' => ['file' => 'admin/pages/grading/transcripts.php'],
                '/approve' => ['file' => 'admin/pages/grading/approve.php'],
            ]
        ],

        '/admin/finance' => [
            'prefix' => '/admin/finance',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/fees' => ['file' => 'admin/pages/finance/fees.php'],
                '/payments' => ['file' => 'admin/pages/finance/payments.php'],
                '/outstanding' => ['file' => 'admin/pages/finance/outstanding.php'],
                '/scholarships' => ['file' => 'admin/pages/finance/scholarships.php'],
            ]
        ],

        '/admin/reports' => [
            'prefix' => '/admin/reports',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/academic' => ['file' => 'admin/pages/reports/academic.php'],
                '/payments' => ['file' => 'admin/pages/reports/payments.php'],
                '/attendance' => ['file' => 'admin/pages/reports/attendance.php'],
            ]
        ],

        '/student-setup' => [
            'prefix' => '/student-setup',
            'middleware' => ['auth', 'check_school_status'],
            'routes' => [
                '/personal' => ['file' => 'student/setup/personal.php'],
                '/status' => ['file' => 'student/setup/activate.php'],
                '/guardian' => ['file' => 'student/setup/guardian.php'],
                '/delete' => ['file' => 'student/setup/delete-account.php']
            ]
        ],

        '/student' => [
            'prefix' => '/student',
            'middleware' => ['auth', 'check_school_status', 'student_ready'],
            'routes' => [
                '/dashboard' => ['file' => 'student/dashboard.php'],
                '/profile' => ['file' => 'student/profile.php'],
                '/evaluation' => ['file' => 'student/pages/evaluation.php'],
                '/evaluation/{tab}' => ['file' => 'student/pages/evaluation.php', 'name' => 'student.evaluation'],
                '/evaluation/perform/{code}' => ['file' => 'student/pages/perform-evaluation.php', 'name' => "student.evaluation.perform"],
                '/allowance' => ['file' => 'student/pages/fees/allowance.php'],
                '/fees' => ['file' => 'student/pages/fees/index.php'],
                '/payment-history' => ['file' => 'student/pages/fees/history.php'],
                '/courses' => ['file' => 'student/pages/courses.php'],
                '/timetable' => ['file' => 'student/pages/timetable.php'],
                '/results' => ['file' => 'student/pages/results.php'],
                '/clearance' => ['file' => 'student/pages/clearance.php'],
                '/transcript' => ['file' => 'student/pages/transcript.php'],
                '/attendance' => ['file' => 'student/pages/attendance.php'],
                '/medical' => ['file' => 'student/pages/medical.php'],
                '/discipline' => ['file' => 'student/pages/discipline.php'],
                '/job-alerts' => ['file' => 'student/pages/job-alerts.php']
            ]
        ],

        // teachers routes
        '/teacher/setup' => [
            'prefix' => '/teacher/setup',
            'middleware' => ['auth', 'valid_teacher_check', 'check_school_status'],
            'routes' => [
                '/' => ['file' => 'teacher/setup-personal.php'],
                // '/status' => ['file' => 'teacher/setup/activate.php'],
                // '/delete' => ['file' => 'teacher/setup/delete-account.php']
            ]
        ],

        '/teacher' => [
            'prefix' => '/teacher',
            'middleware' => ['auth', 'valid_teacher', 'check_school_status'],
            'routes' => [
                '/dashboard' => ['file' => 'teacher/dashboard.php'],
                '/profile' => ['file' => 'teacher/setup-personal.php'],
                
                // Courses section
                '/courses' => ['file' => 'teacher/pages/courses.php'],
                '/courses/materials' => ['file' => 'teacher/pages/materials.php'],
                '/timetable' => ['file' => 'teacher/pages/timetable.php'],
                
                // Students section
                '/students' => ['file' => 'teacher/pages/students.php'],
                '/attendance' => ['file' => 'teacher/pages/attendance.php'],
                '/performance' => ['file' => 'teacher/pages/performance.php'],
                
                // Assessments section
                '/results/upload' => ['file' => 'teacher/pages/results-upload.php'],
                '/grades' => ['file' => 'teacher/pages/grades.php'],
                
                // Communication section
                '/announcements' => ['file' => 'teacher/pages/announcements.php'],
                '/messages' => ['file' => 'teacher/pages/messages.php']
            ]
        ],

        '/tools' => [
            'prefix' => '/tools',
            'middleware' => ['auth'],
            'routes' => [
                '/passport-validator' => ['file' => 'test_passport_validation.php', 'name' => 'tools.passport_validator']
            ]
        ]

    ];

    