<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\SchoolLicence;
use Illuminate\Support\Facades\Cache;

class SchoolLicenceService
{
    /**
     * In-request memo: sidebar + search filter call can() ~10-20x per page.
     * Singleton lives per request, so this turns N cache+DB hits into 1.
     */
    private ?array $licenceMemo = null;

    private ?int $licenceMemoSchoolId = null;

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

        if ($this->licenceMemo !== null && $this->licenceMemoSchoolId === $school->id) {
            return $this->licenceMemo;
        }

        $ttl = max(1, (int) config('licence.cache_ttl', 300));

        $row = Cache::remember(
            $this->cacheKey($school->id),
            $ttl,
            fn (): array => $this->loadOrCreateLicenceArray($school)
        );

        $this->licenceMemo = $row;
        $this->licenceMemoSchoolId = $school->id;

        return $row;
    }

    public function refresh(): void
    {
        $this->licenceMemo = null;
        $this->licenceMemoSchoolId = null;

        $school = School::current();
        if ($school !== null) {
            Cache::forget($this->cacheKey($school->id));
        }
    }

    public function forgetMemo(): void
    {
        $this->licenceMemo = null;
        $this->licenceMemoSchoolId = null;
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

    /**
     * Live per-module pricing, mirroring QuoteCalculationService: one-time =
     * base_price x band multiplier, renewal = renewal_base x multiplier.
     * Bands are '1-500' style (core_pricing); unknown bands fall back to 1.0.
     */
    public function modulePrice(string $moduleKey, string $band = '1-500'): array
    {
        $multiplier = (float) (config('licence.module_pricing.multipliers', [])[$band] ?? 1.0);
        $basePrice = (float) (config("licence.modules.{$moduleKey}.base_price", 0.0));
        $renewalBase = (float) (config("licence.modules.{$moduleKey}.renewal_base", 0.0));

        return [
            'annual_fee' => $renewalBase * $multiplier,
            'setup_fee' => $basePrice * $multiplier,
            'multiplier' => $multiplier,
            'band_label' => config('licence.core_pricing.'.$band.'.label', 'Standard'),
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
        // Fresh query, not $school->licence: School::current() is memoized per request,
        // so a cached null relation would otherwise cause a duplicate insert
        // (UNIQUE school_licences.school_id) when two calls happen in one request.
        $licence = $school->licence()->first();
        if ($licence === null) {
            $licence = $this->createDefaultLicenceRecord($school);
            // Re-read so concurrent firstOrCreate winners return the same row shape.
            $licence->refresh();
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

        // firstOrCreate: race-safe when two calls in one request both see no licence.
        return SchoolLicence::query()->firstOrCreate(
            ['school_id' => $school->id],
            $fields
        );
    }
}
