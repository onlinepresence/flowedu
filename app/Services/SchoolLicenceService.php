<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\SchoolLicence;
use Illuminate\Support\Facades\Cache;

class SchoolLicenceService
{
    public function isEnforcementEnabled(): bool
    {
        return (bool) config('licence.enforce', true);
    }

    public function studentCapMode(): string
    {
        return config('licence.student_cap_mode', 'block');
    }

    /**
     * @return array<string, mixed>
     */
    public function getLicenceRow(): array
    {
        $school = School::current();
        if ($school === null) {
            return $this->defaultLicenceArray();
        }

        $ttl = max(1, (int) config('licence.cache_ttl', 300));

        return Cache::remember(
            $this->cacheKey($school->id),
            $ttl,
            fn (): array => $this->loadOrCreateLicenceArray($school)
        );
    }

    public function refresh(): void
    {
        $school = School::current();
        if ($school !== null) {
            Cache::forget($this->cacheKey($school->id));
        }
    }

    public function can(string $feature): bool
    {
        if (! $this->isEnforcementEnabled()) {
            return true;
        }

        // Locked core features are always enabled
        $coreFeature = config('licence.core_features.'.$feature);
        if (is_array($coreFeature) && ! empty($coreFeature['locked'])) {
            return true;
        }

        // Find the mapped DB column
        $dbColumn = null;
        if (is_array($coreFeature) && isset($coreFeature['db_column'])) {
            $dbColumn = $coreFeature['db_column'];
        } else {
            $module = config('licence.modules.'.$feature);
            if (is_array($module) && isset($module['db_column'])) {
                $dbColumn = $module['db_column'];
            }
        }

        // Unknown feature key fails closed
        if ($dbColumn === null) {
            return false;
        }

        // TODO: In a future phase, this will check an external licensing server API instead of the local database.
        $row = $this->getLicenceRow();

        return (bool) ($row[$dbColumn] ?? false);
    }

    public function featureLabel(string $feature): string
    {
        if ($feature === '') {
            return 'This feature';
        }

        return config("licence.core_features.{$feature}.label")
            ?? config("licence.modules.{$feature}.label")
            ?? ucfirst(str_replace('_', ' ', $feature));
    }

    public function upgradeMessage(string $feature): string
    {
        $label = $this->featureLabel($feature);

        return "Upgrade required for {$label} — contact your administrator.";
    }

    public function maxActiveStudents(): ?int
    {
        $row = $this->getLicenceRow();
        $m = $row['max_active_students'] ?? null;
        if ($m === null || $m === '') {
            return null;
        }

        return (int) $m;
    }

    public function modulePrice(string $moduleKey, string $band = 'tier_1'): array
    {
        $multiplier = config("licence.student_pricing_bands.{$band}.multiplier", 1.0);
        $basePrice = config("licence.modules.{$moduleKey}.base_price", 3000.00);
        $annualFee = $basePrice * $multiplier;
        $setupFee = 500.00 * $multiplier;

        return [
            'annual_fee' => $annualFee,
            'setup_fee' => $setupFee,
            'multiplier' => $multiplier,
            'band_label' => config("licence.student_pricing_bands.{$band}.label", 'Standard'),
        ];
    }

    public function allFeatureStates(): array
    {
        $row = $this->getLicenceRow();

        $core = [];
        foreach (config('licence.core_features', []) as $key => $feat) {
            $core[$key] = [
                'label' => $feat['label'],
                'description' => $feat['description'],
                'locked' => $feat['locked'],
                'value' => $feat['locked'] ? true : (bool) ($row[$feat['db_column']] ?? $feat['default']),
            ];
        }

        $modules = [];
        foreach (config('licence.modules', []) as $key => $feat) {
            $modules[$key] = [
                'label' => $feat['label'],
                'description' => $feat['description'],
                'value' => (bool) ($row[$feat['db_column']] ?? $feat['default']),
                'base_price' => $feat['base_price'],
            ];
        }

        return [
            'core' => $core,
            'modules' => $modules,
        ];
    }

    protected function cacheKey(int $schoolId): string
    {
        return 'school_licence.'.$schoolId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultLicenceArray(?int $schoolId = null): array
    {
        $defaults = [
            'school_id' => $schoolId,
            'max_active_students' => null,
            'licence_start' => null,
            'licence_end' => null,
            'support_until' => null,
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ];

        $isTesting = app()->environment('testing');

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (! $feat['locked']) {
                $defaults[$feat['db_column']] = $isTesting ? true : $feat['default'];
            }
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            $defaults[$feat['db_column']] = $isTesting ? true : $feat['default'];
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadOrCreateLicenceArray(School $school): array
    {
        $licence = $school->licence;
        if ($licence === null) {
            $licence = $this->createDefaultLicenceRecord($school);
        }

        $data = [
            'school_id' => $school->id,
            'max_active_students' => $licence->max_active_students,
            'licence_start' => $licence->licence_start?->format('Y-m-d'),
            'licence_end' => $licence->licence_end?->format('Y-m-d'),
            'support_until' => $licence->support_until?->format('Y-m-d'),
            'notes' => $licence->notes,
            'external_ref' => $licence->external_ref,
            'licence_key' => $licence->licence_key,
        ];

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (! $feat['locked']) {
                $col = $feat['db_column'];
                $data[$col] = (bool) $licence->$col;
            }
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            $col = $feat['db_column'];
            $data[$col] = (bool) $licence->$col;
        }

        return $data;
    }

    protected function createDefaultLicenceRecord(School $school): SchoolLicence
    {
        $today = now()->toDateString();
        $fields = [
            'school_id' => $school->id,
            'max_active_students' => null,
            'licence_start' => $today,
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ];

        $isTesting = app()->environment('testing');

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (! $feat['locked']) {
                $fields[$feat['db_column']] = $isTesting ? true : $feat['default'];
            }
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            $fields[$feat['db_column']] = $isTesting ? true : $feat['default'];
        }

        return SchoolLicence::query()->create($fields);
    }
}
