<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\AdminType;
use App\Models\UserRole;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsUserRolesPage extends Component
{
    private const PROTECTED_ROLE_NAMES = ['owner', 'system_admin'];

    public function mount(): void
    {
        AdminType::ensureDefaults();
        UserRole::ensureSystemRoles();
    }

    public bool $showRoleModal = false;

    public bool $showDeleteModal = false;

    public bool $isEditing = false;

    public ?int $editingId = null;

    public string $roleFilter = 'all';

    public string $display_name = '';

    public string $role_name = '';

    public string $name = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public ?int $deleteRoleId = null;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->editingId = null;
        $this->showRoleModal = true;
    }

    public function openEdit(int $roleId): void
    {
        $role = UserRole::query()->findOrFail($roleId);
        $this->isEditing = true;
        $this->editingId = $role->id;
        $this->display_name = $role->display_name;
        $this->role_name = $role->role_name;
        $this->name = $role->name;
        $perms = $role->permissions;
        $this->selectedPermissions = is_array($perms)
            ? array_values(array_filter($perms, fn ($p) => is_string($p)))
            : [];
        $this->showRoleModal = true;
    }

    public function closeRoleModal(): void
    {
        $this->showRoleModal = false;
        $this->resetForm();
    }

    public function saveRole(): void
    {
        $permissionKeys = [];
        foreach (config('college.admin_permissions', []) as $category => $perms) {
            if (is_array($perms)) {
                $permissionKeys = array_merge($permissionKeys, array_keys($perms));
            } else {
                $permissionKeys[] = $category;
            }
        }
        $adminTypeNames = AdminType::query()->orderBy('name')->pluck('name')->all();

        if ($this->isEditing && $this->editingId !== null) {
            $role = UserRole::query()->findOrFail($this->editingId);
            $this->validate([
                'display_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('user_roles', 'display_name')->ignore($role->id),
                ],
                'selectedPermissions' => ['nullable', 'array'],
                'selectedPermissions.*' => ['string', Rule::in($permissionKeys)],
            ]);
            $role->update([
                'display_name' => $this->display_name,
                'permissions' => $this->selectedPermissions,
            ]);

            $this->closeRoleModal();
            CollegeFlash::forNextRequestToo('status', __('Role updated.'));
            $this->redirectAfterMutation();

            return;
        }

        $this->validate([
            'display_name' => ['required', 'string', 'max:255', 'unique:user_roles,display_name'],
            'role_name' => ['required', 'string', Rule::in($adminTypeNames)],
            'name' => ['nullable', 'string', 'max:255'],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['string', Rule::in($permissionKeys)],
        ]);

        $slug = $this->normalizedSystemName();

        UserRole::query()->create([
            'role_name' => $this->role_name,
            'name' => $slug,
            'display_name' => $this->display_name,
            'permissions' => $this->selectedPermissions,
        ]);

        $this->closeRoleModal();
        CollegeFlash::forNextRequestToo('status', __('Role created.'));
        $this->redirectAfterMutation();
    }

    public function confirmDelete(int $roleId): void
    {
        $role = UserRole::query()->findOrFail($roleId);
        if ($this->isProtectedName($role->name)) {
            $this->addError('delete', __('This system role cannot be deleted.'));

            return;
        }
        if ($role->admins()->exists()) {
            $this->addError('delete', __('Cannot delete a role that is assigned to one or more admins.'));

            return;
        }
        $this->deleteRoleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteRoleId = null;
    }

    public function deleteRole(): void
    {
        if ($this->deleteRoleId === null) {
            return;
        }
        $role = UserRole::query()->findOrFail($this->deleteRoleId);
        if ($this->isProtectedName($role->name) || $role->admins()->exists()) {
            $this->closeDeleteModal();

            return;
        }
        $role->delete();
        $this->closeDeleteModal();
        CollegeFlash::forNextRequestToo('status', __('Role deleted.'));
        $this->redirectAfterMutation();
    }

    public function syncRoles(): void
    {
        abort_unless(auth()->user()?->isAdminOwner(), 403);

        \Illuminate\Support\Facades\Artisan::call('college:sync-roles');

        CollegeFlash::forNextRequestToo('status', __('Roles & permissions synchronized.'));
        $this->redirectAfterMutation();
    }

    private function redirectAfterMutation(): void
    {
        $this->redirect(route('admin.settings.roles'), navigate: true);
    }

    public function render(): View
    {
        $query = UserRole::query()->orderBy('display_name')->orderBy('name');
        if ($this->roleFilter !== 'all') {
            $query->where('role_name', $this->roleFilter);
        }

        $roles = $query->get();
        $adminTypes = AdminType::query()->orderBy('display_name')->get();
        $permissionLabels = config('college.admin_permissions', []);

        $roleTypeLabels = AdminType::query()->pluck('display_name', 'name')->all();

        // Categorize standard checkbox permissions (exclude the 5 split permission pairs)
        $categories = [
            'General & Dashboard' => [
                'view_dashboard_admin' => $permissionLabels['view_dashboard_admin'] ?? 'View Admin Dashboard',
                'approve_registrations' => $permissionLabels['approve_registrations'] ?? 'Approve Student Registrations',
                'nav_dashboard' => $permissionLabels['nav_dashboard'] ?? 'Nav: Dashboard',
            ],
            'Students Module navigation' => [
                'nav_students_index' => $permissionLabels['nav_students_index'] ?? 'Nav: All Students',
                'nav_students_promotion' => $permissionLabels['nav_students_promotion'] ?? 'Nav: Student Promotion',
                'nav_students_graduation' => $permissionLabels['nav_students_graduation'] ?? 'Nav: Graduation Management',
                'nav_students_medical' => $permissionLabels['nav_students_medical'] ?? 'Nav: Medical Info',
                'nav_students_discipline' => $permissionLabels['nav_students_discipline'] ?? 'Nav: Disciplinary Records',
            ],
            'Academic & Timetabling navigation' => [
                'nav_academic_faculty' => $permissionLabels['nav_academic_faculty'] ?? 'Nav: Faculties',
                'nav_academic_department' => $permissionLabels['nav_academic_department'] ?? 'Nav: Departments',
                'nav_academic_program' => $permissionLabels['nav_academic_program'] ?? 'Nav: Programs',
                'nav_academic_sessions' => $permissionLabels['nav_academic_sessions'] ?? 'Nav: Academic Sessions / Terms',
                'nav_academic_timetable' => $permissionLabels['nav_academic_timetable'] ?? 'Nav: Timetable',
            ],
            'Grading & Results navigation' => [
                'nav_grading_points' => $permissionLabels['nav_grading_points'] ?? 'Nav: Grade Points',
                'nav_grading_enter' => $permissionLabels['nav_grading_enter'] ?? 'Nav: Enter Results',
                'nav_grading_upload' => $permissionLabels['nav_grading_upload'] ?? 'Nav: Upload Results',
                'nav_grading_approve' => $permissionLabels['nav_grading_approve'] ?? 'Nav: Results Approval',
                'nav_grading_transcripts' => $permissionLabels['nav_grading_transcripts'] ?? 'Nav: Transcripts',
            ],
            'Staff leave navigation' => [
                'nav_staff_leaves' => $permissionLabels['nav_staff_leaves'] ?? 'Nav: Staff Leave Requests',
            ],
            'Non-Teaching Staff navigation' => [
                'nav_staff_home' => $permissionLabels['nav_staff_home'] ?? 'Nav: Admin Staff (home)',
                'nav_staff_non_teaching' => $permissionLabels['nav_staff_non_teaching'] ?? 'Nav: Non-Teaching Staff',
                'nav_staff_assignments' => $permissionLabels['nav_staff_assignments'] ?? 'Nav: Staff Assignments',
                'nav_staff_roles' => $permissionLabels['nav_staff_roles'] ?? 'Nav: Staff Roles',
            ],
            'Teachers & Evaluations navigation' => [
                'nav_teachers_list' => $permissionLabels['nav_teachers_list'] ?? 'Nav: All Teachers',
                'nav_teachers_assignments' => $permissionLabels['nav_teachers_assignments'] ?? 'Nav: Teacher Assignments',
                'nav_teachers_roles' => $permissionLabels['nav_teachers_roles'] ?? 'Nav: Teacher Roles',
                'nav_teachers_evaluations' => $permissionLabels['nav_teachers_evaluations'] ?? 'Nav: Teacher Evaluations',
                'nav_teachers_materials' => $permissionLabels['nav_teachers_materials'] ?? 'Nav: Course Materials Review',
                'nav_teachers_announcements' => $permissionLabels['nav_teachers_announcements'] ?? 'Nav: Teacher Announcements',
                'nav_practicum_assign' => $permissionLabels['nav_practicum_assign'] ?? 'Nav: Assign TP Trainees',
                'nav_practicum_report' => $permissionLabels['nav_practicum_report'] ?? 'Nav: Teaching Practice Reports',
            ],
            'Finance & Billing navigation' => [
                'nav_finance_fees' => $permissionLabels['nav_finance_fees'] ?? 'Nav: Fee Structure',
                'nav_finance_payments' => $permissionLabels['nav_finance_payments'] ?? 'Nav: Payments',
                'nav_finance_outstanding' => $permissionLabels['nav_finance_outstanding'] ?? 'Nav: Outstanding Fees',
                'nav_finance_scholarships' => $permissionLabels['nav_finance_scholarships'] ?? 'Nav: Scholarships / Grants',
                'nav_finance_invoices' => $permissionLabels['nav_finance_invoices'] ?? 'Nav: Invoices & Expenditures',
            ],
            'Memos & File Uploads' => [
                'nav_memos' => $permissionLabels['nav_memos'] ?? 'Nav: Memos Inbox & Outbox',
                'create_memo' => $permissionLabels['create_memo'] ?? 'Create and Edit Memos',
                'forward_memo' => $permissionLabels['forward_memo'] ?? 'Forward and route Memos',
                'sign_memo' => $permissionLabels['sign_memo'] ?? 'Sign and Approve Memos (HOD/Dean)',
                'self_sign_memo' => $permissionLabels['self_sign_memo'] ?? 'Self-sign official memos',
                'view_all_memos' => $permissionLabels['view_all_memos'] ?? 'View all system Memos (Auditor/Principal)',
                'manage_file_uploads' => $permissionLabels['manage_file_uploads'] ?? 'Upload and Manage Files',
            ],
            'Reports & Tools' => [
                'nav_reports_academic' => $permissionLabels['nav_reports_academic'] ?? 'Nav: Academic Reports',
                'nav_reports_payments' => $permissionLabels['nav_reports_payments'] ?? 'Nav: Payment Reports',
                'nav_reports_attendance' => $permissionLabels['nav_reports_attendance'] ?? 'Nav: Attendance Reports',
                'nav_tools_passport' => $permissionLabels['nav_tools_passport'] ?? 'Nav: Passport validator',
            ],
            'Audit Trail Logs' => [
                'nav_audit_logs' => $permissionLabels['nav_audit_logs'] ?? 'Nav: System Audit Trail',
                'view_audit_logs' => $permissionLabels['view_audit_logs'] ?? 'View System Audit Logs',
            ],
            'Settings Configuration' => [
                'nav_settings_licence' => $permissionLabels['nav_settings_licence'] ?? 'Nav: Licence & subscription',
                'nav_settings_roles' => $permissionLabels['nav_settings_roles'] ?? 'Nav: Roles & Permissions',
                'nav_settings_image_validation' => $permissionLabels['nav_settings_image_validation'] ?? 'Nav: Image Validation',
                'nav_settings_users' => $permissionLabels['nav_settings_users'] ?? 'Nav: User Accounts',
                'nav_settings_preferences' => $permissionLabels['nav_settings_preferences'] ?? 'Nav: System Preferences',
                'nav_settings_school' => $permissionLabels['nav_settings_school'] ?? 'Nav: School Profile',
                'nav_settings_backup' => $permissionLabels['nav_settings_backup'] ?? 'Nav: Backup & Restore',
            ],
        ];

        return view('livewire.admin.settings.settings-user-roles-page', [
            'roles' => $roles,
            'adminTypes' => $adminTypes,
            'permissionLabels' => $permissionLabels,
            'roleTypeLabels' => $roleTypeLabels,
            'permissionCategories' => $categories,
        ])->layout('components.layouts.admin', [
            'title' => __('Roles'),
            'headerTitle' => __('Roles & Permissions'),
            'headerDescription' => __('Configure custom user roles and assign specific administrative action permissions.'),
        ]);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->display_name = '';
        $this->role_name = AdminType::query()->orderBy('name')->value('name') ?? '';
        $this->name = '';
        $this->selectedPermissions = [];
    }

    private function isProtectedName(string $name): bool
    {
        return in_array($name, self::PROTECTED_ROLE_NAMES, true);
    }

    private function normalizedSystemName(): string
    {
        $base = $this->name !== '' && trim($this->name) !== ''
            ? Str::slug(trim($this->name))
            : Str::slug($this->display_name);
        if ($base === '') {
            $base = 'role';
        }

        $candidate = $base;
        $i = 2;
        while (UserRole::query()->where('name', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
