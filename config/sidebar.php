<?php

return [
    'admin' => [
        'setup' => [
            ['label' => 'Personal Information', 'route' => 'admin.setup.personal', 'icon' => 'user'],
            ['label' => 'Setup School', 'route' => 'admin.setup.school', 'icon' => 'building-office-2'],
            ['label' => 'Package & licence', 'route' => 'admin.setup.licence', 'icon' => 'identification'],
            ['label' => 'Setup Faculties', 'route' => 'admin.setup.faculties', 'icon' => 'building-library'],
            ['label' => 'Setup Departments', 'route' => 'admin.setup.departments', 'icon' => 'briefcase'],
            ['label' => 'Setup Programs', 'route' => 'admin.setup.programs', 'icon' => 'book-open'],
            ['label' => 'Setup Halls', 'route' => 'admin.setup.halls', 'icon' => 'home-modern'],
            ['label' => 'Activate System', 'route' => 'admin.setup.activate', 'icon' => 'power'],
        ],
        'main' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2', 'permission' => 'nav_dashboard'],
            ['label' => 'Memos', 'route' => 'admin.memos.index', 'icon' => 'envelope', 'permission' => 'nav_memos'],
            [
                'label' => 'Students',
                'icon' => 'academic-cap',
                'children' => [
                    ['label' => 'All Students', 'route' => 'admin.students.index', 'permission' => 'nav_students_index'],
                    ['label' => 'Student Promotion', 'route' => 'admin.students.promotion', 'permission' => 'nav_students_promotion'],
                    ['label' => 'Graduation Management', 'route' => 'admin.students.graduation', 'permission' => 'nav_students_graduation'],
                    ['label' => 'Medical Info', 'route' => 'admin.students.medical', 'permission' => 'nav_students_medical'],
                    ['label' => 'Disciplinary Records', 'route' => 'admin.students.discipline', 'permission' => 'nav_students_discipline'],
                    ['label' => 'Jobs & Activities', 'route' => 'admin.students.jobs', 'permission' => 'nav_students_index'],
                ],
            ],
            [
                'label' => 'Academic',
                'icon' => 'building-office',
                'children' => [
                    ['label' => 'Faculties', 'route' => 'admin.academic.faculty', 'permission' => 'nav_academic_faculty'],
                    ['label' => 'Departments', 'route' => 'admin.academic.department', 'permission' => 'nav_academic_department'],
                    ['label' => 'Programs', 'route' => 'admin.academic.program', 'permission' => 'nav_academic_program'],
                    ['label' => 'Academic Sessions / Terms', 'route' => 'admin.academic.sessions', 'permission' => 'nav_academic_sessions'],
                    ['label' => 'Timetable', 'route' => 'admin.academic.timetable', 'permission' => 'nav_academic_timetable'],
                ],
            ],
            [
                'label' => 'Grading',
                'icon' => 'pencil-square',
                'children' => [
                    ['label' => 'Grade Points', 'route' => 'admin.grading.points', 'permission' => 'nav_grading_points'],
                    ['label' => 'Enter Results', 'route' => 'admin.grading.enter', 'permission' => 'nav_grading_enter'],
                    ['label' => 'Upload Results', 'route' => 'admin.grading.upload', 'permission' => 'nav_grading_upload'],
                    ['label' => 'Results Approval', 'route' => 'admin.grading.approve', 'permission' => 'nav_grading_approve'],
                    ['label' => 'Transcripts', 'route' => 'admin.grading.transcripts.index', 'permission' => 'nav_grading_transcripts'],
                ],
            ],
            [
                'label' => 'Administration',
                'icon' => 'user-group',
                'children' => [
                    ['label' => 'Overview', 'route' => 'admin.staff.index', 'permission' => 'nav_staff_home'],
                    ['label' => 'Administrators', 'route' => 'admin.staff.administrators', 'permission' => 'nav_staff_home'],
                    ['label' => 'Non-Teaching Staff', 'route' => 'admin.staff.non-teaching', 'permission' => 'nav_staff_non_teaching'],
                    ['label' => 'Staff Assignments', 'route' => 'admin.staff.staff-assignments', 'permission' => 'nav_staff_assignments'],
                    ['label' => 'Leave Management', 'route' => 'admin.staff.leaves', 'permission' => 'nav_staff_leaves'],
                ],
            ],
            [
                'label' => 'Teachers / Lecturers',
                'icon' => 'users',
                'children' => [
                    ['label' => 'All Teachers', 'route' => 'admin.staff.teachers', 'permission' => 'nav_teachers_list'],
                    ['label' => 'Teacher Assignments', 'route' => 'admin.staff.teacher-assignments', 'permission' => 'nav_teachers_assignments'],
                    ['label' => 'Teacher Roles', 'route' => 'admin.staff.teacher-roles', 'permission' => 'nav_teachers_roles'],
                    ['label' => 'Teacher Evaluations', 'route' => 'admin.evaluations', 'permission' => 'nav_teachers_evaluations'],
                    ['label' => 'Course Materials Review', 'route' => 'admin.staff.materials', 'permission' => 'nav_teachers_materials'],
                    ['label' => 'Teacher Announcements', 'route' => 'admin.staff.announcements', 'permission' => 'nav_teachers_announcements'],
                    ['label' => 'Assign TP Trainees', 'route' => 'admin.practicum.assign', 'permission' => 'nav_practicum_assign'],
                    ['label' => 'Teaching Practice Reports', 'route' => 'admin.practicum.reports', 'permission' => 'nav_practicum_report'],
                ],
            ],
            [
                'label' => 'Finance',
                'icon' => 'currency-dollar',
                'children' => [
                    ['label' => 'Fee Structure', 'route' => 'admin.finance.fees', 'permission' => 'nav_finance_fees'],
                    ['label' => 'Payments', 'route' => 'admin.finance.payments', 'permission' => 'nav_finance_payments'],
                    ['label' => 'Outstanding Fees', 'route' => 'admin.finance.outstanding', 'permission' => 'nav_finance_outstanding'],
                    ['label' => 'Scholarships / Grants', 'route' => 'admin.finance.scholarships', 'permission' => 'nav_finance_scholarships'],
                    ['label' => 'Allowances Management', 'route' => 'admin.finance.allowances', 'permission' => 'nav_finance_scholarships'],
                    ['label' => 'Invoices & Expenditures', 'route' => 'admin.finance.invoices', 'permission' => 'nav_finance_invoices'],
                ],
            ],
            [
                'label' => 'Reports',
                'icon' => 'chart-bar',
                'children' => [
                    ['label' => 'Academic Reports', 'route' => 'admin.reports.academic', 'permission' => 'nav_reports_academic'],
                    ['label' => 'Payment Reports', 'route' => 'admin.reports.payments', 'permission' => 'nav_reports_payments'],
                    ['label' => 'Attendance Reports', 'route' => 'admin.reports.attendance', 'permission' => 'nav_reports_attendance'],
                    ['label' => 'Enrollment Reports', 'route' => 'admin.reports.enrollment', 'permission' => 'nav_reports_academic'],
                    ['label' => 'Welfare Reports', 'route' => 'admin.reports.welfare', 'permission' => 'nav_reports_attendance'],
                ],
            ],
            [
                'label' => 'File Manager',
                'route' => 'admin.file-uploads',
                'icon' => 'folder',
                'permission' => 'manage_file_uploads',
            ],
            [
                'label' => 'System Settings',
                'icon' => 'cog-6-tooth',
                'children' => [
                    ['label' => 'Licence & subscription', 'route' => 'admin.settings.licence', 'permission' => 'nav_settings_licence'],
                    ['label' => 'Roles & Permissions', 'route' => 'admin.settings.roles', 'permission' => 'nav_settings_roles'],
                    ['label' => 'Image Validation', 'route' => 'admin.settings.image-validation', 'permission' => 'nav_settings_image_validation'],
                    ['label' => 'User Accounts', 'route' => 'admin.settings.users', 'permission' => 'nav_settings_users'],
                    ['label' => 'System Preferences', 'route' => 'admin.settings.system-preferences', 'permission' => 'nav_settings_preferences'],
                    ['label' => 'System Audit Logs', 'route' => 'admin.audit-logs', 'permission' => 'nav_settings_preferences'],
                    ['label' => 'School Profile', 'route' => 'admin.settings.school', 'permission' => 'nav_settings_school'],
                    ['label' => 'Backup & Restore', 'route' => 'admin.settings.backup', 'permission' => 'nav_settings_backup'],
                ],
            ],
            [
                'label' => 'Tools',
                'icon' => 'wrench-screwdriver',
                'children' => [
                    ['label' => 'Passport validator', 'route' => 'tools.passport-validator', 'permission' => 'nav_tools_passport'],
                ],
            ],
        ],
    ],
    'teacher' => [
        'setup' => [
            ['label' => 'Setup Profile', 'route' => 'teacher.setup', 'icon' => 'user'],
        ],
        'main' => [
            ['label' => 'My Dashboard', 'route' => 'teacher.dashboard', 'icon' => 'squares-2x2'],
            ['label' => 'My Profile', 'route' => 'teacher.profile', 'icon' => 'user'],
            [
                'label' => 'My Courses',
                'icon' => 'book-open',
                'permission' => 'courses',
                'children' => [
                    ['label' => 'Courses Assigned', 'route' => 'teacher.courses'],
                    ['label' => 'Course Materials', 'route' => 'teacher.courses.materials'],
                    ['label' => 'Class Timetable', 'route' => 'teacher.timetable'],
                ],
            ],
            [
                'label' => 'Students',
                'icon' => 'users',
                'permission' => 'students',
                'children' => [
                    ['label' => 'Student List', 'route' => 'teacher.students'],
                    ['label' => 'Attendance', 'route' => 'teacher.attendance'],
                    ['label' => 'Performance', 'route' => 'teacher.performance'],
                    ['label' => 'Supervision (TP)', 'route' => 'teacher.practicum', 'licence' => 'practicum'],
                ],
            ],
            [
                'label' => 'Assessments',
                'icon' => 'pencil-square',
                'permission' => 'assessments',
                'children' => [
                    ['label' => 'Enter Results', 'route' => 'teacher.results.enter'],
                    ['label' => 'Upload Results', 'route' => 'teacher.results.upload'],
                    ['label' => 'Grade Submissions', 'route' => 'teacher.grades'],
                ],
            ],
            [
                'label' => 'Communication',
                'icon' => 'envelope',
                'permission' => 'communication',
                'children' => [
                    ['label' => 'Announcements', 'route' => 'teacher.announcements'],
                    ['label' => 'Messages', 'route' => 'teacher.messages'],
                    ['label' => 'Memos', 'route' => 'teacher.memos.index'],
                    ['label' => 'Shared Lesson Plans', 'route' => 'teacher.lesson-plans'],
                ],
            ],
        ],
    ],
    'student' => [
        'setup' => [
            ['label' => 'Personal Information', 'route' => 'student.setup.personal', 'icon' => 'user'],
            ['label' => 'Parent/Guardian Information', 'route' => 'student.setup.guardian', 'icon' => 'shield-check'],
            ['label' => 'Admission Status', 'route' => 'student.setup.status', 'icon' => 'clipboard-document-check'],
            ['label' => 'Cancel Registration', 'route' => 'student.setup.delete', 'icon' => 'trash'],
        ],
        'main' => [
            ['label' => 'Dashboard', 'route' => 'student.dashboard', 'icon' => 'squares-2x2'],
            ['label' => 'My Profile', 'route' => 'student.profile', 'icon' => 'user'],
            [
                'label' => 'Academic',
                'icon' => 'book-open',
                'children' => [
                    ['label' => 'My Courses', 'route' => 'student.courses'],
                    ['label' => 'My Timetable', 'route' => 'student.timetable'],
                    ['label' => 'My Results', 'route' => 'student.results'],
                    ['label' => 'Clearance Request', 'route' => 'student.clearance'],
                    ['label' => 'My Transcript', 'route' => 'student.transcript'],
                    ['label' => 'Job Alerts & Activities', 'route' => 'student.job-alerts'],
                    ['label' => 'Teaching Practice', 'route' => 'student.practicum'],
                ],
            ],
            ['label' => 'Evaluation', 'href' => '/student/evaluation', 'icon' => 'clipboard-document-list'],
            [
                'label' => 'Fees & Payments',
                'icon' => 'currency-dollar',
                'children' => [
                    ['label' => 'Fee Details', 'route' => 'student.fees.index'],
                    ['label' => 'Payment History', 'route' => 'student.fees.history'],
                    ['label' => 'Scholarships', 'route' => 'student.scholarships'],
                    ['label' => 'My Allowances', 'route' => 'student.fees.allowance'],
                ],
            ],
            ['label' => 'Attendance', 'route' => 'student.attendance', 'icon' => 'calendar-days'],
            ['label' => 'Medical Info', 'route' => 'student.medical', 'icon' => 'heart'],
            ['label' => 'Disciplinary Records', 'route' => 'student.discipline', 'icon' => 'exclamation-triangle'],
            ['label' => 'Memos', 'route' => 'student.memos.index', 'icon' => 'envelope'],
        ],
    ],
];
