<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Services\SchoolLicenceService;
use App\Support\CollegeFlash;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SystemPreferencesPage extends Component
{
    use DispatchesCollegeToasts;

    public bool $student_grading_redirect = false;

    public string $external_grading_url = '';

    public bool $allow_student_self_registration = true;

    public bool $enable_email_notifications = true;

    public bool $show_detailed_bill_breakdown = false;

    public bool $show_attendance_policy = true;

    public int $min_attendance_threshold = 75;

    public bool $hasTeacherToolsLicence = false;

    // New Modular preferences
    public string $finance_billing_cycle = 'semester';

    public bool $memo_department_isolation = false;

    public string $leave_approval_workflow = 'hod_and_hr';

    public function mount(SchoolLicenceService $licenceService): void
    {
        abort_unless($this->canManageSettings(), 403);

        $this->hasTeacherToolsLicence = $licenceService->can('teacher_tools');

        $settings = Setting::query()->pluck('setting_value', 'setting_key');

        $this->student_grading_redirect = (bool) ($settings['system_preferences.student_grading_redirect'] ?? false);
        $this->external_grading_url = (string) ($settings['system_preferences.external_grading_url'] ?? '');
        $this->allow_student_self_registration = (bool) ($settings['system_preferences.allow_student_self_registration'] ?? true);
        $this->enable_email_notifications = (bool) ($settings['system_preferences.enable_email_notifications'] ?? true);
        $this->show_detailed_bill_breakdown = (bool) ($settings['system_preferences.show_detailed_bill_breakdown'] ?? false);
        $this->show_attendance_policy = (bool) ($settings['system_preferences.show_attendance_policy'] ?? true);
        $this->min_attendance_threshold = (int) ($settings['system_preferences.min_attendance_threshold'] ?? 75);

        // Load new settings
        $this->finance_billing_cycle = (string) ($settings['finance_settings.billing_cycle'] ?? 'semester');
        $this->memo_department_isolation = (bool) ($settings['memo_settings.department_isolation'] ?? false);
        $this->leave_approval_workflow = (string) ($settings['leave_settings.approval_workflow'] ?? 'hod_and_hr');
    }

    public function saveSettings(): void
    {
        abort_unless($this->canManageSettings(), 403);

        $userId = auth()->id();

        // Enforce feature-gate: if they don't have teacher_tools licence, they cannot enable student_grading_redirect
        if (! $this->hasTeacherToolsLicence) {
            $this->student_grading_redirect = false;
        }

        $this->validate([
            'min_attendance_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'finance_billing_cycle' => ['required', 'string', 'in:semester,yearly'],
            'leave_approval_workflow' => ['required', 'string', 'in:hod_only,hod_and_hr'],
        ]);

        if ($this->student_grading_redirect) {
            $this->validate([
                'external_grading_url' => ['required', 'url', 'max:255'],
            ]);
        }

        $defs = [
            'system_preferences.student_grading_redirect' => [
                'val' => $this->student_grading_redirect ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Redirect students to external grading software',
                'cat' => 'system_preferences'
            ],
            'system_preferences.external_grading_url' => [
                'val' => $this->student_grading_redirect ? trim($this->external_grading_url) : '',
                'type' => 'string',
                'desc' => 'External grading portal URL',
                'cat' => 'system_preferences'
            ],
            'system_preferences.allow_student_self_registration' => [
                'val' => $this->allow_student_self_registration ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Allow student self-registration',
                'cat' => 'system_preferences'
            ],
            'system_preferences.enable_email_notifications' => [
                'val' => $this->enable_email_notifications ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Enable system email alerts',
                'cat' => 'system_preferences'
            ],
            'system_preferences.show_detailed_bill_breakdown' => [
                'val' => $this->show_detailed_bill_breakdown ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Show detailed itemized fee breakdown to students',
                'cat' => 'system_preferences'
            ],
            'system_preferences.show_attendance_policy' => [
                'val' => $this->show_attendance_policy ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Show class attendance policy disclaimer to students',
                'cat' => 'system_preferences'
            ],
            'system_preferences.min_attendance_threshold' => [
                'val' => (string) $this->min_attendance_threshold,
                'type' => 'integer',
                'desc' => 'Default minimum attendance percentage required for exams',
                'cat' => 'system_preferences'
            ],
            'finance_settings.billing_cycle' => [
                'val' => $this->finance_billing_cycle,
                'type' => 'string',
                'desc' => 'Academic fee billing frequency cycle',
                'cat' => 'finance_settings'
            ],
            'memo_settings.department_isolation' => [
                'val' => $this->memo_department_isolation ? '1' : '0',
                'type' => 'boolean',
                'desc' => 'Enforce strict departmental memo communication isolation',
                'cat' => 'memo_settings'
            ],
            'leave_settings.approval_workflow' => [
                'val' => $this->leave_approval_workflow,
                'type' => 'string',
                'desc' => 'Staff leave application review pathway strategy',
                'cat' => 'leave_settings'
            ]
        ];

        foreach ($defs as $key => $meta) {
            $row = Setting::query()->firstOrNew(['setting_key' => $key]);
            $row->forceFill([
                'setting_value' => $meta['val'],
                'category' => $meta['cat'],
                'data_type' => $meta['type'],
                'description' => $meta['desc'],
                'updated_by' => $userId,
            ])->save();
        }

        CollegeFlash::forNextRequestToo('status', __('System preferences saved successfully.'));
        $this->redirect(route('admin.settings.system-preferences'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.settings.system-preferences-page')
            ->layout('components.layouts.admin', [
                'title' => __('System Preferences'),
                'headerTitle' => __('System Preferences'),
                'headerDescription' => __('Manage global feature toggles, registration settings, and notification configurations for the entire campus application.'),
            ]);
    }

    private function canManageSettings(): bool
    {
        $actor = auth()->user();
        if ($actor === null || ($actor->type !== 'admin' && $actor->type !== 'staff')) {
            return false;
        }

        return app(SchoolLicenceService::class)->can('system_admin');
    }
}
