<?php

if (!function_exists('fetchData')) {
    require_once __DIR__ . '/database_functions.php';
}

/**
 * @param mixed $raw
 */
function settings_cast_value($raw, string $data_type): mixed
{
    switch ($data_type) {
        case 'integer':
            return (int) $raw;
        case 'boolean':
            return in_array((string) $raw, ['1', 'true', 'yes', 'on'], true)
                || $raw === true
                || $raw === 1;
        case 'json':
        case 'array':
            if ($raw === null || $raw === '') {
                return [];
            }
            $decoded = json_decode((string) $raw, true);

            return is_array($decoded) ? $decoded : [];
        case 'string':
        default:
            return (string) $raw;
    }
}

function settings_db_ready(): bool
{
    global $connect;

    if (!function_exists('fetchData') || !isset($connect) || !($connect instanceof mysqli)) {
        return false;
    }

    if ($connect->connect_error) {
        return false;
    }

    if (!function_exists('verifyTable')) {
        return false;
    }

    return verifyTable('settings');
}

function settings_invalidate_cache(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    unset($_SESSION['settings_values'], $_SESSION['settings_cache_loaded_at']);
}

/**
 * @return array<string, mixed>
 */
function settings_load_cache_from_db(): array
{
    if (!settings_db_ready()) {
        return [];
    }

    try {
        $rows = fetchData('*', 'settings', '', 0);
    } catch (Throwable $e) {
        return [];
    }

    if ($rows === false || $rows === null || is_string($rows)) {
        return [];
    }

    if (!is_array($rows)) {
        return [];
    }

    if (isset($rows['setting_key'])) {
        $rows = [$rows];
    }

    $map = [];
    foreach ($rows as $row) {
        if (empty($row['setting_key'])) {
            continue;
        }
        $type = $row['data_type'] ?? 'string';
        $map[$row['setting_key']] = settings_cast_value($row['setting_value'], $type);
    }

    return $map;
}

function settings_cache_get_map(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return settings_load_cache_from_db();
    }

    $loadedAt = $_SESSION['settings_cache_loaded_at'] ?? 0;
    if (!isset($_SESSION['settings_values']) || (time() - $loadedAt) > 300) {
        $_SESSION['settings_values'] = settings_load_cache_from_db();
        $_SESSION['settings_cache_loaded_at'] = time();
    }

    return $_SESSION['settings_values'] ?? [];
}

/**
 * @param mixed $default
 */
function get_setting(string $key, $default = null, ?string $data_type = null): mixed
{
    $map = settings_cache_get_map();

    if (!array_key_exists($key, $map)) {
        return $default;
    }

    if ($data_type !== null) {
        return settings_cast_value($map[$key], $data_type);
    }

    return $map[$key];
}

/**
 * @param mixed $value
 */
function set_setting(
    string $key,
    $value,
    ?string $category = null,
    ?string $data_type = null,
    ?string $description = null,
    ?int $updated_by = null
): bool {
    if (!settings_db_ready()) {
        return false;
    }

    $parts = explode('.', $key, 2);
    $category = $category ?? ($parts[0] ?? 'general');

    $row = fetchData('*', 'settings', "setting_key = '" . addslashes($key) . "'");
    $existingType = $row['data_type'] ?? $data_type ?? 'string';

    if ($data_type === null && $row) {
        $existingType = $row['data_type'];
    } elseif ($data_type !== null) {
        $existingType = $data_type;
    }

    $stored = settings_store_value($value, $existingType);

    $payload = [
        'setting_value' => $stored,
        'data_type' => $existingType,
        'updated_by' => $updated_by,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($description !== null) {
        $payload['description'] = $description;
    }

    if ($row) {
        $ok = update($row, $payload, 'settings', ['setting_key']) === true;
    } else {
        $insert = [
            'category' => $category,
            'setting_key' => $key,
            'setting_value' => $stored,
            'data_type' => $existingType,
            'description' => $description,
            'updated_by' => $updated_by,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $ok = data_insert('settings', $insert) === true;
    }

    if ($ok) {
        settings_invalidate_cache();
    }

    return $ok;
}

/**
 * @param mixed $value
 */
function settings_store_value($value, string $data_type): string
{
    switch ($data_type) {
        case 'integer':
            return (string) (int) $value;
        case 'boolean':
            return ($value === true || $value === 1 || $value === '1' || $value === 'on') ? '1' : '0';
        case 'json':
        case 'array':
            return json_encode($value);
        case 'string':
        default:
            return (string) $value;
    }
}

/**
 * @return array<string, mixed> Short keys (without category prefix) => typed value
 */
function get_settings_by_category(string $category): array
{
    if (!settings_db_ready()) {
        return [];
    }

    $prefix = $category . '.';
    $map = settings_cache_get_map();
    $out = [];

    foreach ($map as $fullKey => $val) {
        if (str_starts_with($fullKey, $prefix)) {
            $short = substr($fullKey, strlen($prefix));
            $out[$short] = $val;
        }
    }

    return $out;
}

/**
 * @return array<string, mixed>|null
 */
function get_setting_metadata(string $key): ?array
{
    if (!settings_db_ready()) {
        return null;
    }

    $row = fetchData('*', 'settings', "setting_key = '" . addslashes($key) . "'");
    if (!$row || empty($row['setting_key'])) {
        return null;
    }

    return $row;
}

/**
 * @param array<string, mixed> $settings Full dotted keys => value
 */
function bulk_update_settings(array $settings, ?int $updated_by = null): bool
{
    if (!settings_db_ready()) {
        return false;
    }

    $allOk = true;

    foreach ($settings as $key => $value) {
        $row = fetchData('*', 'settings', "setting_key = '" . addslashes($key) . "'");
        if (!$row) {
            $allOk = false;
            continue;
        }

        $stored = settings_store_value($value, $row['data_type']);
        $payload = [
            'setting_value' => $stored,
            'updated_by' => $updated_by,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $res = update(array_merge($row, $payload), $payload, 'settings', ['setting_key']);
        if ($res !== true) {
            $allOk = false;
        }
    }

    settings_invalidate_cache();

    return $allOk;
}
