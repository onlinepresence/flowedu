<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maintenance redirect (legacy SERVER_DOWN + includes/session.php)
    |--------------------------------------------------------------------------
    |
    | When true, web requests are redirected to the /shutdown route (except /up).
    | Prefer php artisan down for framework maintenance; this mirrors legacy .env.
    |
    */
    'server_down' => filter_var(env('SERVER_DOWN', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Forced Demo Mode
    |--------------------------------------------------------------------------
    */
    'demo_mode' => filter_var(env('APP_DEMO', false), FILTER_VALIDATE_BOOLEAN),


    /*
    |--------------------------------------------------------------------------
    | First-time admin registration secret
    |--------------------------------------------------------------------------
    |
    | When no school record exists, guests are guided to register the first
    | admin account. This value must match the "system secret" field on that form.
    | Default matches legacy check_secret() behaviour (literal "system_secret").
    |
    */
    'system_registration_secret' => env('SYSTEM_REGISTRATION_SECRET', 'system_secret'),

    /*
    |--------------------------------------------------------------------------
    | Admin impersonation (replaces legacy SYSTEM_PASSWORD)
    |--------------------------------------------------------------------------
    |
    | No master password on login. Impersonation is implemented for owner and
    | system_admin roles (see docs/COLLEGE_OPERATIONS.md and admin_impersonation_logs).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Purge users when no school row exists (legacy check_school)
    |--------------------------------------------------------------------------
    |
    | When true, visiting a bootstrap-protected route with no school deletes all
    | users so the install can start clean. Disable if you need to preserve rows
    | during development.
    |
    */
    'bootstrap_purge_users' => env('COLLEGE_BOOTSTRAP_PURGE_USERS', true),

    /*
    |--------------------------------------------------------------------------
    | Web .env generator (legacy pages/generate_env.php)
    |--------------------------------------------------------------------------
    |
    | Disabled by default. Set COLLEGE_ALLOW_WEB_ENV_GENERATOR=true in local only
    | if you intentionally expose /env-generator.
    |
    */
    'allow_web_env_generator' => env('COLLEGE_ALLOW_WEB_ENV_GENERATOR', false),

    /*
    |--------------------------------------------------------------------------
    | Admin role permissions (legacy admin/pages/settings/roles.php)
    |--------------------------------------------------------------------------
    |
    | Keys are permission slugs stored in user_roles.permissions; labels are UI copy.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Ghana mobile prefixes (legacy includes/appMemory.php $phone_prefixes)
    |--------------------------------------------------------------------------
    |
    | Used by App\Rules\GhanaMobilePhone for parity with legacy phone validation.
    |
    */
    'ghana_phone_prefixes' => [
        '027', '057', '026', '056', '024',
        '025', '053', '054', '055', '059',
        '020', '050', '023',
    ],

    'admin_permissions' => [
        // Legacy / coarse slugs (see nav_coarse_permission_grants)
        'student_management' => 'Manage Students (CRUD)',
        'teacher_management' => 'Manage Teachers / lecturer admin',
        'course_management' => 'Manage Academic structure (faculties, departments, programs, sessions)',
        'view_dashboard_admin' => 'View Admin Dashboard',
        'view_financial_data' => 'View Financial Data',
        'approve_registrations' => 'Approve Student Registrations',
        'delete_user' => 'Delete Users',
        'view_profile' => 'View Own Profile',
        'nav_dashboard' => 'Nav: Dashboard',
        // Admin sidebar — Students
        'nav_students_index' => 'Nav: All Students',
        'nav_students_promotion' => 'Nav: Student Promotion',
        'nav_students_graduation' => 'Nav: Graduation Management',
        'nav_students_medical' => 'Nav: Medical Info',
        'nav_students_discipline' => 'Nav: Disciplinary Records',
        // Admin sidebar — Academic
        'nav_academic_faculty' => 'Nav: Faculties',
        'nav_academic_department' => 'Nav: Departments',
        'nav_academic_program' => 'Nav: Programs',
        'nav_academic_sessions' => 'Nav: Academic Sessions / Terms',
        'nav_academic_timetable' => 'Nav: Timetable',
        // Admin sidebar — Grading
        'nav_grading_points' => 'Nav: Grade Points',
        'nav_grading_enter' => 'Nav: Enter Results',
        'nav_grading_upload' => 'Nav: Upload Results',
        'nav_grading_approve' => 'Nav: Results Approval',
        'nav_grading_transcripts' => 'Nav: Transcripts',
        // Admin sidebar — Administration (staff)
        'nav_staff_home' => 'Nav: Admin Staff (home)',
        'nav_staff_non_teaching' => 'Nav: Non-Teaching Staff',
        'nav_staff_assignments' => 'Nav: Staff Assignments',
        'nav_staff_roles' => 'Nav: Staff Roles',
        // Admin sidebar — Teachers
        'nav_teachers_list' => 'Nav: All Teachers',
        'nav_teachers_assignments' => 'Nav: Teacher Assignments',
        'nav_teachers_roles' => 'Nav: Teacher Roles',
        'nav_teachers_evaluations' => 'Nav: Teacher Evaluations',
        'nav_teachers_materials' => 'Nav: Course Materials Review',
        'nav_teachers_announcements' => 'Nav: Teacher Announcements',
        'nav_practicum_assign' => 'Nav: Assign TP Trainees',
        'nav_practicum_report' => 'Nav: Teaching Practice Reports',
        // Admin sidebar — Finance
        'nav_finance_fees' => 'Nav: Fee Structure',
        'nav_finance_payments' => 'Nav: Payments',
        'nav_finance_outstanding' => 'Nav: Outstanding Fees',
        'nav_finance_scholarships' => 'Nav: Scholarships / Grants',
        // Admin sidebar — Reports
        'nav_reports_academic' => 'Nav: Academic Reports',
        'nav_reports_payments' => 'Nav: Payment Reports',
        'nav_reports_attendance' => 'Nav: Attendance Reports',
        // Admin sidebar — System settings
        'nav_settings_licence' => 'Nav: Licence & subscription',
        'nav_settings_roles' => 'Nav: Roles & Permissions',
        'nav_settings_image_validation' => 'Nav: Image Validation',
        'nav_settings_users' => 'Nav: User Accounts',
        'nav_settings_preferences' => 'Nav: System Preferences',
        'nav_settings_school' => 'Nav: School Profile',
        'nav_settings_backup' => 'Nav: Backup & Restore',
        'nav_settings_env' => 'Nav: System Variables (.env generator)',
        // Admin sidebar — Tools
        'nav_tools_passport' => 'Nav: Passport validator',
        // Admin sidebar — Memos
        'nav_memos' => 'Nav: Memos Inbox & Outbox',
        'create_memo' => 'Create and Edit Memos',
        'forward_memo' => 'Forward and route Memos',
        'sign_memo' => 'Sign and Approve Memos (HOD/Dean)',
        'self_sign_memo' => 'Self-sign official memos',
        'view_all_memos' => 'View all system Memos (Auditor/Principal)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Coarse permission → nav slugs
    |--------------------------------------------------------------------------
    |
    | Lets roles that only have legacy slugs still see the matching sidebar areas.
    |
    */
    'nav_coarse_permission_grants' => [
        'view_dashboard_admin' => [
            'nav_dashboard',
        ],
        'student_management' => [
            'nav_students_index',
            'nav_students_promotion',
            'nav_students_graduation',
            'nav_students_medical',
            'nav_students_discipline',
        ],
        'approve_registrations' => [
            'nav_students_index',
        ],
        'course_management' => [
            'nav_academic_faculty',
            'nav_academic_department',
            'nav_academic_program',
            'nav_academic_sessions',
            'nav_academic_timetable',
        ],
        'teacher_management' => [
            'nav_staff_home',
            'nav_staff_non_teaching',
            'nav_staff_assignments',
            'nav_staff_roles',
            'nav_teachers_list',
            'nav_teachers_assignments',
            'nav_teachers_roles',
            'nav_teachers_evaluations',
            'nav_teachers_materials',
            'nav_teachers_announcements',
            'nav_practicum_assign',
            'nav_practicum_report',
        ],
        'view_financial_data' => [
            'nav_finance_fees',
            'nav_finance_payments',
            'nav_finance_outstanding',
            'nav_finance_scholarships',
            'nav_reports_payments',
        ],
        'delete_user' => [
            'nav_settings_users',
            'nav_settings_preferences',
        ],
    ],

];
