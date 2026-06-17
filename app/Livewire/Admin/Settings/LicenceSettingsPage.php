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

    public function mount(SchoolLicenceService $licenceService): void
    {
        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

            return;
        }

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

    public function getPricingPreview(SchoolLicenceService $licenceService): array
    {
        $maxStudents = $this->max_active_students === '' ? 0 : (int) $this->max_active_students;

        // Find band
        $bandKey = 'tier_1';
        foreach (config('licence.student_pricing_bands', []) as $key => $band) {
            $min = $band['min'];
            $max = $band['max'];
            if ($maxStudents >= $min && ($max === null || $maxStudents <= $max)) {
                $bandKey = $key;
                break;
            }
        }

        $multiplier = config("licence.student_pricing_bands.{$bandKey}.multiplier", 1.0);
        $bandLabel = config("licence.student_pricing_bands.{$bandKey}.label", 'Standard');

        // Core pricing
        $coreBase = (float) config('licence.pricing.core.base_annual', 12000.00);
        $coreSetup = (float) config('licence.pricing.core.implementation_fee', 3500.00);
        $coreAnnual = $coreBase * $multiplier;

        // Modules pricing
        $modulesAnnual = 0.0;
        $modulesSetup = 0.0;
        $activeModulesCount = 0;
        $totalModulesCount = count($this->moduleStates);

        foreach ($this->moduleStates as $moduleKey => $enabled) {
            if ($enabled) {
                $activeModulesCount++;
                $pricing = $licenceService->modulePrice($moduleKey, $bandKey);
                $modulesAnnual += $pricing['annual_fee'];
                $modulesSetup += $pricing['setup_fee'];
            }
        }

        // Discount
        $discount = 0.0;
        if ($activeModulesCount === $totalModulesCount && $totalModulesCount > 0) {
            $rate = (float) config('licence.pricing.discounts.all_modules_rate', 0.20);
            $discount = $modulesAnnual * $rate;
        }

        $hosting = (float) config('licence.pricing.hosting.annual_fee', 1500.00);

        $totalAnnual = $coreAnnual + $modulesAnnual - $discount + $hosting;
        $totalSetup = $coreSetup + $modulesSetup;

        return [
            'band_label' => $bandLabel,
            'multiplier' => $multiplier,
            'core_annual' => $coreAnnual,
            'core_setup' => $coreSetup,
            'modules_annual' => $modulesAnnual,
            'modules_setup' => $modulesSetup,
            'discount' => $discount,
            'hosting' => $hosting,
            'total_annual' => $totalAnnual,
            'total_setup' => $totalSetup,
            'grand_total' => $totalAnnual + $totalSetup,
            'active_modules_count' => $activeModulesCount,
            'total_modules_count' => $totalModulesCount,
        ];
    }

    public function save(SchoolLicenceService $licenceService): void
    {
        $school = School::current();
        if ($school === null) {
            $this->redirect(route('admin.setup.school'), navigate: true);

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

        SchoolLicence::query()->updateOrCreate(
            ['school_id' => $school->id],
            $fields
        );

        $licenceService->refresh();

        CollegeFlash::forNextRequestToo('status', __('Licence settings saved.'));
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(SchoolLicenceService $licenceService): View
    {
        $preview = $this->getPricingPreview($licenceService);

        return view('livewire.admin.settings.licence-settings-page', [
            'pricingPreview' => $preview,
            'coreCatalog' => config('licence.core_features', []),
            'modulesCatalog' => config('licence.modules', []),
        ])->layout('components.layouts.admin', [
            'title' => __('Licence settings'),
            'headerTitle' => __('Licence Settings'),
            'headerDescription' => __('View active features, modules, student limitations, and pricing details of your licence.'),
        ]);
    }
}
