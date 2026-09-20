<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\School;
use App\Models\SchoolLicence;
use App\Services\SchoolLicenceService;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LicenceSettingsPage extends Component
{
    public array $coreStates = [];

    public array $moduleStates = [];

    public string $max_active_students = '';

    public ?string $licence_start = null;

    public ?string $support_until = null;

    public string $external_ref = '';

    public bool $isLinked = false;

    public bool $isProvisional = false;

    public function mount(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

        $this->isLinked = $enrollment->isLinked($school);
        $this->isProvisional = ! $this->isLinked && (bool) $school->licence()->value('provisional');

        $licenceService->refresh();
        $row = $licenceService->getLicenceRow();

        $max = $row['max_active_students'] ?? null;
        $this->max_active_students = $max !== null && $max !== '' ? (string) $max : '';
        $this->licence_start = isset($row['licence_start']) && $row['licence_start'] !== null
            ? (string) $row['licence_start']
            : now()->toDateString();
        $this->support_until = isset($row['support_until']) && $row['support_until'] !== null
            ? (string) $row['support_until']
            : now()->addYear()->toDateString();
        $this->external_ref = (string) ($row['external_ref'] ?? '');

        // Load features state
        $states = $licenceService->allFeatureStates();
        foreach ($states['core'] as $key => $feat) {
            if (! $feat['locked']) {
                $this->coreStates[$key] = (bool) $feat['value'];
            }
        }
        foreach ($states['modules'] as $key => $feat) {
            $this->moduleStates[$key] = (bool) $feat['value'];
        }
    }

    /**
     * Pricing preview driven by the LIVE core_pricing math (the same
     * QuoteCalculationService the landing quotes use), so installers never
     * see numbers disagreeing with ops.
     */
    public function getPricingPreview(SchoolLicenceService $licenceService): array
    {
        $maxStudents = $this->max_active_students === '' ? 0 : (int) $this->max_active_students;

        $bandKey = match (true) {
            $maxStudents <= 500 => '1-500',
            $maxStudents <= 1000 => '501-1000',
            $maxStudents <= 2000 => '1001-2000',
            $maxStudents <= 3500 => '2001-3500',
            default => '3500+',
        };

        $modules = [];
        foreach ($this->moduleStates as $moduleKey => $enabled) {
            if ($enabled && isset(config('licence.modules', [])[$moduleKey])) {
                $modules[] = $moduleKey;
            }
        }

        $quote = \App\Services\QuoteCalculationService::calculate([
            'student_band' => $bandKey,
            'modules' => $modules,
            'hosting_setup' => 'none',
            'config_setup' => '0',
            'migration' => '0',
            'admin_training' => 0,
            'teacher_training' => 0,
            'onsite_training' => 0,
            'founding_client' => '0',
            'send_client_receipt' => '0',
        ]);

        if (! empty($quote['is_custom'])) {
            return [
                'band_label' => $quote['band_label'] ?? '3,500+ Students',
                'multiplier' => 0.0,
                'core_annual' => 0.0,
                'core_setup' => 0.0,
                'modules_annual' => 0.0,
                'modules_setup' => 0.0,
                'discount' => 0.0,
                'hosting' => 0.0,
                'total_annual' => 0.0,
                'total_setup' => 0.0,
                'grand_total' => 0.0,
                'active_modules_count' => count($modules),
                'total_modules_count' => count(config('licence.modules', [])),
                'is_custom' => true,
            ];
        }

        return [
            'band_label' => $quote['band_label'] ?? '',
            'multiplier' => (float) ($quote['multiplier'] ?? 1.0),
            'core_annual' => (float) ($quote['core_renewal_final'] ?? 0.0),
            'core_setup' => (float) ($quote['core_upfront_final'] ?? 0.0),
            'modules_annual' => (float) ($quote['modules_renew_final'] ?? 0.0),
            'modules_setup' => (float) ($quote['modules_onetime_final'] ?? 0.0),
            'discount' => (float) ($quote['bundle_discount_renew'] ?? 0.0),
            'hosting' => (float) ($quote['hosting_setup_fee'] ?? 0.0),
            'total_annual' => (float) ($quote['renew_total'] ?? 0.0),
            'total_setup' => (float) ($quote['upfront_total'] ?? 0.0),
            'grand_total' => (float) ($quote['renew_total'] ?? 0.0) + (float) ($quote['upfront_total'] ?? 0.0),
            'active_modules_count' => count($modules),
            'total_modules_count' => count(config('licence.modules', [])),
            'is_custom' => false,
        ];
    }

    public function save(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

        // Linked installs are read-only: terms come from ControlDesk.
        if ($enrollment->isLinked($school)) {
            $this->addError('form', __('This install is managed by ControlDesk. Licence terms cannot be changed here.'));

            return;
        }

        $this->validate([
            'max_active_students' => ['nullable', 'integer', 'min:0'],
            'licence_start' => ['nullable', 'date'],
            'support_until' => ['nullable', 'date'],
            'external_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $maxVal = $this->max_active_students === '' ? null : (int) $this->max_active_students;

        $fields = [
            'max_active_students' => $maxVal,
            'licence_start' => $this->licence_start ?: null,
            'support_until' => $this->support_until ?: null,
            'external_ref' => $this->external_ref === '' ? null : $this->external_ref,
        ];

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (! $feat['locked']) {
                $fields[$feat['db_column']] = (bool) ($this->coreStates[$key] ?? $feat['default']);
            }
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            $fields[$feat['db_column']] = (bool) ($this->moduleStates[$key] ?? $feat['default']);
        }

        $existing = $school->licence()->first();
        if ($existing !== null) {
            // Local edits never drop enrollment state.
            $fields['provisional'] = (bool) $existing->provisional;
            if ((bool) $existing->provisional) {
                $fields['external_ref'] = null;
            }
        }

        SchoolLicence::query()->updateOrCreate(
            ['school_id' => $school->id],
            $fields
        );

        $licenceService->refresh();

        CollegeFlash::forNextRequestToo('status', __('Licence settings saved.'));
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    /**
     * Linked installs may still toggle CORE settings (saved locally);
     * modules stay frozen to whatever ControlDesk granted.
     */
    public function saveCoreFeatures(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null || ! $enrollment->isLinked($school)) {
            $this->redirect(route('admin.settings.licence'), navigate: true);

            return;
        }

        $fields = [];
        foreach (config('licence.core_features', []) as $key => $feat) {
            if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                continue;
            }
            $fields[$feat['db_column']] = (bool) ($this->coreStates[$key] ?? $feat['default']);
        }

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], $fields);
        $licenceService->refresh();

        CollegeFlash::forNextRequestToo('status', __('Core settings saved.'));
    }

    public function render(SchoolLicenceService $licenceService): View
    {
        $preview = $this->getPricingPreview($licenceService);

        return view('livewire.admin.settings.licence-settings-page', [
            'pricingPreview' => $preview,
            'coreCatalog' => config('licence.core_features', []),
            'modulesCatalog' => config('licence.modules', []),
            'isLinked' => $this->isLinked,
            'isProvisional' => $this->isProvisional,
        ])->layout('components.layouts.admin', [
            'title' => __('Licence settings'),
            'headerTitle' => __('Licence Settings'),
            'headerDescription' => __('View active features, modules, student limitations, and pricing details of your licence.'),
        ]);
    }
}
