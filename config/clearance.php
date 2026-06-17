<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clearance department keys (legacy includes/clearance_departments.php)
    |--------------------------------------------------------------------------
    |
    | Keys are stored on student_clearances.department_key. Labels are student/admin UI copy.
    |
    */
    'definitions' => [
        'finance' => 'Finance Office',
        'library' => 'University Library',
        'academic_registry' => 'Academic Registry',
        'department' => 'Department Office',
        'faculty' => 'Faculty Office',
        'hall' => 'Hall / Hostel Management',
        'dean_students' => 'Dean of Students Affairs',
        'src' => 'SRC Office',
    ],

    /*
    |--------------------------------------------------------------------------
    | Departments that default to not_required for new rows
    |--------------------------------------------------------------------------
    */
    'default_not_required_keys' => [],

];
