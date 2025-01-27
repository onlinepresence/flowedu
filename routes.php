<?php
    // '/uri' => ['file' => '', 'middleware' => []],

    return [
        '/' => ['file' => 'pages/login.php', 'middleware' => ["check_school"]],
        '/shutdown' => ['file' => 'shutdown.php', 'middleware' => ['check_school']],
        '/register' => ['file' => 'pages/create-account.php'],
        '/logout' => ['file' => 'logout.php'],

        // admin routes
        '/admin-setup' => [
            'prefix' => '/admin-setup',
            'middleware' => ['auth'],
            'routes' => [
                '/personal' => ['file' => 'admin/setup/personal.php'],
                '/school' => ['file' => 'admin/setup/school.php'],
                '/programs' => ['file' => 'admin/setup/program.php'],
                '/halls' => ['file' => 'admin/setup/hall.php']
            ]
        ]
    ];

    