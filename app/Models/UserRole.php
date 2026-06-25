<?php

namespace App\Models;

use App\Casts\PermissionsArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRole extends Model
{
    protected $fillable = [
        'role_name',
        'name',
        'display_name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => PermissionsArray::class,
    ];

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'type');
    }

    /**
     * Baseline roles (also used by {@see \Database\Seeders\AdminSystemSeeder}).
     * Required so {@see \App\Models\Admin}'s `type` FK can point at `owner` when the first admin is created.
     */
    public static function ensureSystemRoles(): void
    {
        $roles = [
            ['role_name' => 'owner', 'name' => 'owner', 'display_name' => 'Owner', 'permissions' => []],
            ['role_name' => 'system_admin', 'name' => 'system_admin', 'display_name' => 'System administrator', 'permissions' => []],
            [
                'role_name' => 'registrar',
                'name' => 'registrar',
                'display_name' => 'Registrar',
                'permissions' => [
                    'view_dashboard_admin', 'student_management', 'student_management_view', 'teacher_management_view', 'course_management_view', 'approve_registrations',
                    'nav_dashboard', 'nav_students_index', 'nav_students_promotion', 'nav_students_graduation',
                    'nav_students_medical', 'nav_students_discipline', 'nav_academic_faculty', 'nav_academic_department',
                    'nav_academic_program', 'nav_academic_sessions', 'nav_academic_timetable', 'nav_staff_home',
                    'nav_staff_non_teaching', 'nav_staff_assignments', 'nav_staff_roles', 'nav_teachers_list',
                    'nav_teachers_assignments', 'nav_teachers_roles', 'nav_memos', 'create_memo', 'forward_memo', 'manage_file_uploads'
                ],
            ],
            [
                'role_name' => 'hod',
                'name' => 'hod',
                'display_name' => 'Head of Department',
                'permissions' => [
                    'view_dashboard_admin', 'course_management', 'course_management_view', 'teacher_management', 'teacher_management_view',
                    'manage_staff_leaves', 'view_staff_leaves', 'nav_staff_leaves',
                    'nav_dashboard', 'nav_academic_faculty', 'nav_academic_department', 'nav_academic_program',
                    'nav_academic_sessions', 'nav_academic_timetable', 'nav_staff_home', 'nav_staff_non_teaching',
                    'nav_teachers_list', 'nav_teachers_assignments', 'nav_teachers_roles', 'nav_teachers_evaluations',
                    'nav_teachers_materials', 'nav_teachers_announcements', 'nav_memos', 'create_memo', 'forward_memo', 'sign_memo', 'self_sign_memo', 'manage_file_uploads'
                ],
            ],
            [
                'role_name' => 'principal',
                'name' => 'principal',
                'display_name' => 'Principal',
                'permissions' => [
                    'view_dashboard_admin', 'student_management', 'student_management_view', 'teacher_management', 'teacher_management_view',
                    'course_management', 'course_management_view', 'view_financial_data', 'manage_financial_data', 'approve_registrations', 'nav_dashboard',
                    'nav_students_index', 'nav_students_promotion', 'nav_students_graduation', 'nav_students_medical',
                    'nav_students_discipline', 'nav_academic_faculty', 'nav_academic_department', 'nav_academic_program',
                    'nav_academic_sessions', 'nav_academic_timetable', 'nav_grading_points', 'nav_grading_enter',
                    'nav_grading_upload', 'nav_grading_approve', 'nav_grading_transcripts', 'nav_staff_home',
                    'nav_staff_non_teaching', 'nav_staff_assignments', 'nav_staff_roles', 'nav_staff_leaves', 'view_staff_leaves', 'manage_staff_leaves',
                    'nav_teachers_list', 'nav_teachers_assignments', 'nav_teachers_roles', 'nav_teachers_evaluations',
                    'nav_teachers_materials', 'nav_teachers_announcements', 'nav_finance_fees', 'nav_finance_payments',
                    'nav_finance_outstanding', 'nav_finance_scholarships', 'nav_finance_invoices', 'nav_reports_academic', 'nav_reports_payments',
                    'nav_reports_attendance', 'nav_settings_licence', 'nav_settings_roles', 'nav_settings_image_validation',
                    'nav_settings_users', 'nav_settings_school', 'nav_settings_backup', 'nav_audit_logs', 'view_audit_logs', 'nav_tools_passport',
                    'nav_memos', 'create_memo', 'forward_memo', 'sign_memo', 'self_sign_memo', 'view_all_memos', 'manage_file_uploads'
                ],
            ],
            [
                'role_name' => 'vice_principal',
                'name' => 'vice_principal',
                'display_name' => 'Vice Principal',
                'permissions' => [
                    'view_dashboard_admin', 'student_management', 'student_management_view', 'teacher_management', 'teacher_management_view',
                    'course_management', 'course_management_view', 'approve_registrations', 'nav_dashboard', 'nav_students_index',
                    'nav_students_promotion', 'nav_students_graduation', 'nav_students_medical', 'nav_students_discipline',
                    'nav_academic_faculty', 'nav_academic_department', 'nav_academic_program', 'nav_academic_sessions',
                    'nav_academic_timetable', 'nav_grading_points', 'nav_grading_enter', 'nav_grading_upload',
                    'nav_grading_approve', 'nav_grading_transcripts', 'nav_staff_home', 'nav_staff_non_teaching',
                    'nav_staff_assignments', 'nav_staff_roles', 'nav_staff_leaves', 'view_staff_leaves', 'nav_teachers_list', 'nav_teachers_assignments',
                    'nav_teachers_roles', 'nav_teachers_evaluations', 'nav_teachers_materials', 'nav_teachers_announcements',
                    'nav_reports_academic', 'nav_reports_attendance', 'nav_memos', 'create_memo', 'forward_memo', 'sign_memo', 'self_sign_memo', 'manage_file_uploads'
                ],
            ],
            [
                'role_name' => 'finance_officer',
                'name' => 'finance_officer',
                'display_name' => 'Finance Officer',
                'permissions' => [
                    'view_dashboard_admin', 'view_financial_data', 'manage_financial_data', 'nav_dashboard',
                    'nav_finance_fees', 'nav_finance_payments', 'nav_finance_outstanding', 'nav_finance_scholarships',
                    'nav_finance_invoices', 'nav_reports_payments', 'nav_memos', 'create_memo', 'forward_memo'
                ],
            ],
            [
                'role_name' => 'dean_of_students',
                'name' => 'dean_of_students',
                'display_name' => 'Dean of Student Affairs',
                'permissions' => [
                    'view_dashboard_admin', 'student_management', 'student_management_view', 'nav_dashboard',
                    'nav_students_index', 'nav_students_medical', 'nav_students_discipline', 'nav_memos',
                    'create_memo', 'forward_memo', 'sign_memo', 'self_sign_memo'
                ],
            ],
            [
                'role_name' => 'librarian',
                'name' => 'librarian',
                'display_name' => 'College Librarian',
                'permissions' => [
                    'view_dashboard_admin', 'nav_dashboard', 'nav_memos', 'create_memo', 'forward_memo'
                ],
            ],
            [
                'role_name' => 'internal_auditor',
                'name' => 'internal_auditor',
                'display_name' => 'Internal Auditor',
                'permissions' => [
                    'view_dashboard_admin', 'view_financial_data', 'student_management_view', 'teacher_management_view',
                    'course_management_view', 'view_staff_leaves', 'nav_staff_leaves', 'nav_audit_logs', 'view_audit_logs',
                    'nav_dashboard', 'nav_finance_fees', 'nav_finance_payments', 'nav_finance_outstanding', 'nav_finance_scholarships',
                    'nav_reports_academic', 'nav_reports_payments', 'nav_reports_attendance', 'nav_memos',
                    'create_memo', 'forward_memo'
                ],
            ],
            [
                'role_name' => 'secretary',
                'name' => 'secretary',
                'display_name' => 'Secretary',
                'permissions' => [
                    'view_dashboard_admin', 'nav_dashboard', 'nav_memos', 'create_memo', 'forward_memo'
                ],
            ],
            [
                'role_name' => 'admissions_officer',
                'name' => 'admissions_officer',
                'display_name' => 'Admissions Officer',
                'permissions' => [
                    'view_dashboard_admin', 'approve_registrations', 'student_management_view', 'nav_dashboard', 'nav_students_index', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'exams_officer',
                'name' => 'exams_officer',
                'display_name' => 'Examinations Officer',
                'permissions' => [
                    'view_dashboard_admin', 'student_management_view', 'course_management_view', 'nav_dashboard', 'nav_grading_points', 'nav_grading_enter', 'nav_grading_upload', 'nav_grading_approve', 'nav_grading_transcripts', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'quality_assurance_officer',
                'name' => 'quality_assurance_officer',
                'display_name' => 'Quality Assurance Officer',
                'permissions' => [
                    'view_dashboard_admin', 'teacher_management_view', 'nav_dashboard', 'nav_teachers_evaluations', 'nav_teachers_materials', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'human_resource_manager',
                'name' => 'human_resource_manager',
                'display_name' => 'Human Resource Manager',
                'permissions' => [
                    'view_dashboard_admin', 'teacher_management', 'teacher_management_view', 'manage_staff_leaves', 'view_staff_leaves', 'nav_staff_leaves',
                    'nav_dashboard', 'nav_staff_home', 'nav_staff_non_teaching', 'nav_staff_assignments', 'nav_staff_roles', 'nav_teachers_list', 'nav_teachers_assignments', 'nav_teachers_roles', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'accountant',
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'permissions' => [
                    'view_dashboard_admin', 'view_financial_data', 'manage_financial_data', 'nav_dashboard', 'nav_finance_fees', 'nav_finance_payments', 'nav_finance_outstanding', 'nav_finance_scholarships', 'nav_finance_invoices', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'public_relations_officer',
                'name' => 'public_relations_officer',
                'display_name' => 'Public Relations Officer',
                'permissions' => [
                    'view_dashboard_admin', 'nav_dashboard', 'nav_teachers_announcements', 'nav_memos', 'create_memo'
                ],
            ],
            [
                'role_name' => 'procurement_officer',
                'name' => 'procurement_officer',
                'display_name' => 'Procurement Officer',
                'permissions' => [
                    'view_dashboard_admin', 'nav_dashboard', 'nav_memos', 'create_memo'
                ],
            ],
        ];

        foreach ($roles as $row) {
            $role = static::query()->firstOrNew(['name' => $row['name']]);
            $role->role_name = $row['role_name'];
            $role->display_name = $row['display_name'];
            
            // Re-seed system roles cleanly, filtering out obsolete items
            $obsolete = ['view_profile', 'delete_user', 'nav_settings_env'];
            $role->permissions = array_values(array_diff($row['permissions'], $obsolete));
            $role->save();
        }
    }
}
