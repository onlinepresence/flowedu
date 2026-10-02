<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\School;
use App\Models\SchoolLicence;
use App\Services\SchoolLicenceService;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
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

    public bool $isLive = false;

    public string $notice = '';

    /** Activation modal switcher: redeem|import|offline (one form at a time). */
    public string $mode = 'redeem';

    public string $enrollCode = '';

    public string $elevationCode = '';

    public string $offlineCode = '';

    public string $importBlob = '';

    public string $enrollError = '';

    /** @var list<string> */
    public array $manualLines = [];

    public string $manualPath = '';

    public function mount(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

        $this->refreshLicenceState($licenceService, $enrollment);
    }

    /**
     * Reload every licence-derived property (used at mount and after any
     * activation-modal success so banners, toggles and grants update live).
     */
    public function refreshLicenceState(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null) {
            return;
        }

        $this->isLinked = $enrollment->isLinked($school);
        $this->isProvisional = ! $this->isLinked && (bool) $school->licence()->value('provisional');
        $this->isLive = $enrollment->isLicenceLive($school);

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
        $this->coreStates = [];
        $this->moduleStates = [];
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

        $linked = $enrollment->isLinked($school);
        $live = $enrollment->isLicenceLive($school);

        // Linked but inactive installs are core-frozen until reactivated.
        if ($linked && ! $live) {
            $this->addError('form', __('This licence is not active. Reactivate it to change settings.'));

            return;
        }

        $this->validate([
            'licence_start' => ['nullable', 'date'],
            'support_until' => ['nullable', 'date'],
            'external_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $fields = [
            'licence_start' => $this->licence_start ?: null,
            'support_until' => $this->support_until ?: null,
            'external_ref' => $this->external_ref === '' ? null : $this->external_ref,
        ];

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (! $feat['locked']) {
                $fields[$feat['db_column']] = (bool) ($this->coreStates[$key] ?? $feat['default']);
            }
        }

        if ($live) {
            // Only modules inside the plan may be switched; the rest are
            // never written here (the dropdown renders them disabled).
            $grants = $enrollment->centralModuleGrants($school);
            foreach (config('licence.modules', []) as $key => $feat) {
                if (($grants[$key] ?? false) === true) {
                    $fields[$feat['db_column']] = (bool) ($this->moduleStates[$key] ?? $feat['default']);
                }
            }
        } elseif (! $linked) {
            foreach (config('licence.modules', []) as $key => $feat) {
                $fields[$feat['db_column']] = (bool) ($this->moduleStates[$key] ?? $feat['default']);
            }
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

        // Capacity & reference are centrally managed (inputs stay disabled);
        // they are never written from this page.
        if ($linked) {
            $this->notice = __('Licence settings saved.');
            $this->refreshLicenceState($licenceService, $enrollment);

            return;
        }

        CollegeFlash::forNextRequestToo('status', __('Licence settings saved.'));
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    /**
     * Linked but inactive installs may still toggle CORE settings (saved
     * locally); modules stay untouched until reactivation.
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

    public function openActivationModal(): void
    {
        $this->resetSubmissionState();
        $this->mode = 'redeem';
        $this->dispatch('open-modal', 'licence-activation-modal');
    }

    /**
     * Modal exit (a): redeem an enrollment code. Stays on the page and
     * refreshes banners, toggles and grants in place.
     */
    public function redeemCode(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();
        $this->validate(['enrollCode' => ['required', 'string', 'max:255']]);

        $this->handleActivationResult($enrollment->redeem($this->enrollCode), $licenceService, $enrollment, __('Licence activated.'));
    }

    /**
     * Modal elevation: back up first, then redeem, keeping existing data.
     */
    public function activateWithExistingData(
        SchoolLicenceService $licenceService,
        \App\Services\ControlPlane\LicenceEnrollmentService $enrollment,
        \App\Services\Backup\DatabaseBackupService $backups,
    ): void {
        $this->resetSubmissionState();
        $this->validate(['elevationCode' => ['required', 'string', 'max:255']]);

        $backup = $backups->createBackup(auth()->user());
        if (! ($backup['ok'] ?? false)) {
            $this->enrollError = (string) ($backup['message'] ?? __('Backup failed, so activation stopped before touching anything.'));

            return;
        }

        $result = $enrollment->redeem($this->elevationCode);

        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Enrollment failed. Your data and backup are untouched.'));

            return;
        }

        $voided = \App\Support\EnvWriter::remove(['DEMO_KEY']);
        if (! ($voided['ok'] ?? false)) {
            Log::warning('controlplane.elevation.demo-key-void-failed', ['path' => $voided['path'] ?? null]);
        }
        if (session()->has('demo_key_accepted')) {
            session()->forget('demo_key_accepted');
        }

        $this->handleActivationResult($result, $licenceService, $enrollment, __('Activated with your existing data.'));
    }

    /**
     * Modal exit (b): continue offline with a provisional core-only licence.
     */
    public function continueOffline(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();

        $result = $enrollment->continueOffline(null, $this->offlineCode);

        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Could not continue offline.'));

            return;
        }

        $message = ! empty($result['pending'])
            ? __('Provisional licence issued. Your code will be redeemed automatically when online.')
            : __('Provisional licence issued.');

        $this->handleActivationResult($result, $licenceService, $enrollment, $message);
    }

    /**
     * Modal exit (c): import a signed licence file blob.
     */
    public function importLicence(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();
        $this->validate(['importBlob' => ['required', 'string', 'max:20000']]);

        $this->handleActivationResult($enrollment->importFile($this->importBlob), $licenceService, $enrollment, __('Licence file accepted.'));
    }

    /**
     * Shared modal landing: failures stay in the modal, successes refresh
     * the page state in place and close the modal.
     */
    private function handleActivationResult(
        array $result,
        SchoolLicenceService $licenceService,
        \App\Services\ControlPlane\LicenceEnrollmentService $enrollment,
        string $successMessage,
    ): void {
        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Activation failed.'));

            return;
        }

        // The service persists the UUID to .env (next request picks it up);
        // mirror it into runtime config so this same request recomputes
        // linked/live state truthfully instead of staying stale.
        $school = School::current();
        if ($school !== null) {
            $ref = $school->licence()->value('external_ref');
            if (is_string($ref) && trim($ref) !== '') {
                config(['controlplane.deployment_uuid' => trim($ref)]);
            }
        }

        if (! empty($result['manual_lines'])) {
            // Row is seeded; only the .env write failed — show exact lines.
            $this->manualLines = array_values($result['manual_lines']);
            $this->manualPath = (string) ($result['path'] ?? \base_path('.env'));
            $this->refreshLicenceState($licenceService, $enrollment);

            return;
        }

        $this->refreshLicenceState($licenceService, $enrollment);
        $this->reset(['enrollCode', 'elevationCode', 'offlineCode', 'importBlob']);
        $this->notice = $successMessage;
        $this->dispatch('close-modal', 'licence-activation-modal');
    }

    private function resetSubmissionState(): void
    {
        $this->enrollError = '';
        $this->manualLines = [];
        $this->manualPath = '';
        $this->resetErrorBag();
    }

    public function render(SchoolLicenceService $licenceService, \App\Services\ControlPlane\LicenceEnrollmentService $enrollment): View
    {
        $preview = $this->getPricingPreview($licenceService);

        $modulesCatalog = config('licence.modules', []);
        $grants = $this->isLinked ? $enrollment->centralModuleGrants() : [];

        // Live installs split modules into plan (toggleable) vs the rest
        // (hidden in a disabled dropdown). Inactive installs render neither —
        // the empty state takes the section instead.
        $grantedModules = [];
        $otherModules = [];
        if ($this->isLive) {
            foreach ($modulesCatalog as $key => $feat) {
                if (($grants[$key] ?? false) === true) {
                    $grantedModules[$key] = $feat;
                } else {
                    $otherModules[$key] = $feat;
                }
            }
        }

        return view('livewire.admin.settings.licence-settings-page', [
            'pricingPreview' => $preview,
            'coreCatalog' => config('licence.core_features', []),
            'modulesCatalog' => $modulesCatalog,
            'grantedModules' => $grantedModules,
            'otherModules' => $otherModules,
            'isLinked' => $this->isLinked,
            'isProvisional' => $this->isProvisional,
            'isLive' => $this->isLive,
            // Display truth only: central-grant badges on linked installs.
            // Saving stays permissive; the merge is untouched.
            'coreGrants' => $enrollment->centralCoreGrants(),
            'showGrantBadges' => $this->isLinked,
            'hasExistingData' => $enrollment->hasExistingData(),
        ])->layout('components.layouts.admin', [
            'title' => __('Licence settings'),
            'headerTitle' => __('Licence Settings'),
            'headerDescription' => __('View active features, modules, student limitations, and pricing details of your licence.'),
        ]);
    }
}
