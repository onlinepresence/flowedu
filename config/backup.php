<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MySQL client binaries
    |--------------------------------------------------------------------------
    |
    | Full paths recommended on Windows (e.g. Laragon):
    | MYSQLDUMP_PATH=C:\laragon\bin\mysql\mysql-8.x\bin\mysqldump.exe
    | MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.x\bin\mysql.exe
    |
    */

    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    'mysql_path' => env('MYSQL_PATH', 'mysql'),

    /**
     * Relative to the `local` disk root (storage/app/private).
     */
    'directory' => 'backups',

];
