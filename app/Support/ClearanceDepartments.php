<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Parity with legacy includes/clearance_departments.php (canonical keys + defaults).
 */
final class ClearanceDepartments
{
    /**
     * @return array<string, string> key => label
     */
    public static function definitions(): array
    {
        $catalog = config('clearance.definitions', []);
        $selected = self::configuredDepartmentKeys();
        if ($selected === []) {
            return $catalog;
        }

        $defs = [];
        foreach ($selected as $key) {
            if (isset($catalog[$key])) {
                $defs[$key] = $catalog[$key];
            }
        }

        return $defs !== [] ? $defs : $catalog;
    }

    /**
     * @return list<string>
     */
    public static function allowedKeys(): array
    {
        return array_keys(self::definitions());
    }

    public static function defaultStatusForDepartment(string $departmentKey): string
    {
        if (in_array($departmentKey, self::configuredNotRequiredKeys(), true)) {
            return 'not_required';
        }

        return 'pending';
    }

    public static function isAllowedKey(string $departmentKey): bool
    {
        return array_key_exists($departmentKey, self::definitions());
    }

    /**
     * @return list<string>
     */
    private static function configuredDepartmentKeys(): array
    {
        $raw = Setting::query()
            ->where('setting_key', 'clearance.department_keys')
            ->value('setting_value');

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($key) => is_string($key)));
    }

    /**
     * @return list<string>
     */
    private static function configuredNotRequiredKeys(): array
    {
        $raw = Setting::query()
            ->where('setting_key', 'clearance.default_not_required_keys')
            ->value('setting_value');

        if (! is_string($raw) || trim($raw) === '') {
            return config('clearance.default_not_required_keys', []);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return config('clearance.default_not_required_keys', []);
        }

        return array_values(array_filter($decoded, fn ($key) => is_string($key)));
    }
}
