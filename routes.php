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
                '/approve-student/{indexnumber}/{guardian}/{id}' => ['file' => 'admin/approve-student.php'],
                '/students' => ['file' => 'admin/pages/students/index.php'],
                '/staff' => ['file' => 'admin/pages/staff/index.php'],
                '/staff/teachers' => ['file' => 'admin/pages/staff/teachers.php'],
                '/profile' => ['file' => 'admin/setup/personal.php']
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
                '/course' => ['file' => 'admin/pages/course.php'],
                '/sessions' => ['file' => 'admin/pages/session.php'],
            ]
        ],

        '/admin/students' => [
            'prefix' => '/admin/student',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                // '/' => ['file' => 'admin/pages/students/index.php'],
            ]
        ],

        '/admin/settings' => [
            'prefix' => '/admin/settings',
            'middleware' => ['auth', 'valid_admin', 'check_school_status'],
            'routes' => [
                '/roles' => ['file' => 'admin/pages/settings/roles.php'],
                '/school' => ['file' => 'admin/setup/school.php'],
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
                '/allowance' => ['file' => 'student/pages/fees/allowance.php'],
                '/fees' => ['file' => 'student/pages/fees/index.php'],
                '/payment-history' => ['file' => 'student/pages/fees/history.php']
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
                '/profile' => ['file' => 'teacher/setup-personal.php']
            ]
        ]


    ];

    