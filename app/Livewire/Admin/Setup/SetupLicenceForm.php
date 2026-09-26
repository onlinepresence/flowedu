<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Models\School;
use App\Models\SchoolLicence;
use App\Services\ControlPlane\LicenceEnrollmentService;
use App\Services\QuoteCalculationService;
use App\Services\SchoolLicenceService;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SetupLicenceForm extends Component
{
    public array $coreStates = [];

    public array $moduleStates = [];

    public string $max_active_students = '';

    public ?string $licence_start = null;

    public ?string $support_until = null;

    public string $external_ref = '';

    public string $enrollCode = '';

    public string $elevationCode = '';

    public string $offlineCode = '';

    public string $importBlob = '';

    public string $enrollError = '';

    /** @var list<string> */
    public array $manualLines = [];

    public string $manualPath = '';

    /** linked|provisional|pending|legacy */
    public string $choice = 'pending';

    public function mount(SchoolLicenceService $licenceService, LicenceEnrollmentService $enrollment): void
    {
        if (! session('admin_register')) {
            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

        $this->choice = $enrollment->choiceState($school);

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
     * Exit (a): redeem an enrollment code against ControlDesk.
     */
    public function redeemCode(LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();
        $this->validate(['enrollCode' => ['required', 'string', 'max:255']]);

        $result = $enrollment->redeem($this->enrollCode);

        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Enrollment failed.'));

            return;
        }

        if (! empty($result['manual_lines'])) {
            // Row is seeded; only the .env write failed — show exact lines.
            $this->manualLines = array_values($result['manual_lines']);
            $this->manualPath = (string) ($result['path'] ?? \base_path('.env'));
            $this->choice = $enrollment->choiceState();

            return;
        }

        CollegeFlash::forNextRequestToo('status', __('Licence enrolled. Continue with your setup.'));
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    /**
     * Trial-to-live elevation: keep existing data, activate in place.
     * Auto-backup via the Backup path FIRST, then redeem, then void any
     * local demo key. Backup failure aborts before anything is touched.
     */
    public function activateWithExistingData(
        LicenceEnrollmentService $enrollment,
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

        // One-way: void any local demo key so this install can never
        // present as demo again. Non-fatal if the file resists editing.
        $voided = \App\Support\EnvWriter::remove(['DEMO_KEY']);
        if (! ($voided['ok'] ?? false)) {
            Log::warning('controlplane.elevation.demo-key-void-failed', ['path' => $voided['path'] ?? null]);
        }
        if (session()->has('demo_key_accepted')) {
            session()->forget('demo_key_accepted');
        }

        if (! empty($result['manual_lines'])) {
            $this->manualLines = array_values($result['manual_lines']);
            $this->manualPath = (string) ($result['path'] ?? \base_path('.env'));
            $this->choice = $enrollment->choiceState();

            return;
        }

        CollegeFlash::forNextRequestToo('status', __('Activated with your existing data. Continue with your setup.'));
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    /**
     * Exit (b): continue offline with a provisional core-only licence.
     */
    public function continueOffline(LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();

        $result = $enrollment->continueOffline(null, $this->offlineCode);

        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Could not continue offline.'));

            return;
        }

        $message = ! empty($result['pending'])
            ? __('Provisional licence issued. Your code will be redeemed automatically when online.')
            : __('Provisional licence issued. Continue with your setup.');

        CollegeFlash::forNextRequestToo('status', $message);
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    /**
     * Exit (c): import a signed licence file blob.
     */
    public function importLicence(LicenceEnrollmentService $enrollment): void
    {
        $this->resetSubmissionState();
        $this->validate(['importBlob' => ['required', 'string', 'max:20000']]);

        $result = $enrollment->importFile($this->importBlob);

        if (! ($result['ok'] ?? false)) {
            $this->enrollError = (string) ($result['message'] ?? __('Import failed.'));

            return;
        }

        if (! empty($result['manual_lines'])) {
            $this->manualLines = array_values($result['manual_lines']);
            $this->manualPath = (string) ($result['path'] ?? \base_path('.env'));
            $this->choice = $enrollment->choiceState();

            return;
        }

        CollegeFlash::forNextRequestToo('status', __('Licence file accepted. Continue with your setup.'));
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    public function continueSetup(): void
    {
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    /**
     * Linked installs may still toggle CORE settings (saved locally);
     * modules stay frozen to whatever ControlDesk granted.
     */
    public function saveCoreFeatures(SchoolLicenceService $licenceService, LicenceEnrollmentService $enrollment): void
    {
        $school = School::current();
        if ($school === null || ! $enrollment->isLinked($school)) {
            $this->redirect(route('admin.setup.licence'), navigate: true);

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

    /**
     * Free-form save stays for provisional (badged) and legacy installs.
     * Linked installs are read-only; pending installs must pick an exit.
     */
    public function save(SchoolLicenceService $licenceService, LicenceEnrollmentService $enrollment): void
    {
        if (! session('admin_register')) {
            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

        $choice = $enrollment->choiceState($school);
        if ($choice === 'linked' || $choice === 'pending') {
            $this->redirect(route('admin.setup.licence'), navigate: true);

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

        // Never drop enrollment state through the local form. Provisional
        // rows keep their flag (and stay UUID-less until ControlDesk
        // confirms them); legacy rows behave exactly as before.
        $existing = $school->licence()->first();
        if ($existing !== null) {
            $fields['provisional'] = (bool) $existing->provisional;
        }
        if ($choice === 'provisional') {
            $fields['external_ref'] = null;
        }

        SchoolLicence::query()->updateOrCreate(
            ['school_id' => $school->id],
            $fields
        );

        $licenceService->refresh();

        $this->choice = $enrollment->choiceState($school);

        CollegeFlash::forNextRequestToo('status', __('Package saved. Continue with your setup.'));
        $this->redirect(route('admin.setup.faculties'), navigate: true);
    }

    /**
     * Pricing preview driven by the LIVE core_pricing math (the same
     * QuoteCalculationService the landing quotes use), so installers never
     * see numbers disagreeing with ops. Replaces the old tier-band math.
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

        $quote = QuoteCalculationService::calculate([
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

    public function render(SchoolLicenceService $licenceService, LicenceEnrollmentService $enrollment): View
    {
        $preview = $this->getPricingPreview($licenceService);
        $choice = $enrollment->choiceState();

        return view('livewire.admin.setup.setup-licence-form', [
            'pricingPreview' => $preview,
            'coreCatalog' => config('licence.core_features', []),
            'modulesCatalog' => config('licence.modules', []),
            'enrollmentChoice' => $choice,
            'hasExistingData' => $enrollment->hasExistingData(),
            'bundleRate' => (float) config('licence.bundle_discount', 0.12),
            // Display truth only: central-grant badges on linked installs.
            // Saving stays permissive; the merge is untouched.
            'coreGrants' => $enrollment->centralCoreGrants(),
            'showGrantBadges' => $choice === 'linked',
        ])->layout('components.layouts.admin', ['title' => __('Package & licence')]);
    }

    private function resetSubmissionState(): void
    {
        $this->enrollError = '';
        $this->manualLines = [];
        $this->manualPath = '';
        $this->resetErrorBag();
    }
}
