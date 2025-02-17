<?php
    // '/uri' => ['file' => '', 'middleware' => []],

    return [
        '/' => ['file' => 'pages/login.php', 'middleware' => ["check_school"]],
        '/shutdown' => ['file' => 'shutdown.php', 'middleware' => ['check_school']],
        '/register' => ['file' => 'pages/create-account.php', 'middleware' => ['admission_is_open']],
        '/logout' => ['file' => 'logout.php'],

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
            'middleware' => ['auth', 'check_school_status'],
            'routes' => [
                '/dashboard' => ['file' => 'admin/dashboard.php'],
            ]
        ],

        '/student-setup' => [
            'prefix' => '/student-setup',
            'middleware' => ['auth', 'check_school_status'],
            'routes' => [
                '/personal' => ['file' => 'student/setup/personal.php'],
                '/status' => ['file' => 'student/setup/activate.php'],
                '/guardian' => ['file' => 'student/setup/guardian.php']
            ]
        ],

        '/student' => [
            'prefix' => '/student',
            'middleware' => ['auth', 'check_school_status'],
            'routes' => [
                '/dashboard' => ['file' => 'student/dashboard.php', 'middleware' => ['student_ready']],
            ]
        ],

    ];

    