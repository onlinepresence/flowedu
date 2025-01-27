<?php
    // '/uri' => ['file' => '', 'middleware' => []],

    return [
        '/' => ['file' => 'pages/login.php', 'middleware' => ["check_school"]],
        '/shutdown' => ['file' => 'shutdown.php', 'middleware' => ['check_school']],
        '/register' => ['file' => 'pages/create-account.php'],
        '/logout' => ['file' => 'logout.php'],

        // admin routes
        '/admin' => [
            'prefix' => '/admin',
            'middleware' => ['auth'],
            'routes' => [
                '/personal' => ['file' => 'admin/setup/personal.php']
            ]
        ]
        // '/admin/personal' => ['file' => 'admin/setup/personal.php'],

        // '/dashboard' => ['file' => 'dashboard.php', 'middleware' => []],
    ];

    