<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Reads legacy PHP-serialized base64 (admin/submit.php serialize_) or JSON; writes JSON arrays.
 */
class PermissionsArray implements CastsAttributes
{
    /**
     * @param  array<string, mixed>|string|null  $value
     * @return array<int|string, mixed>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        $json = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return [];
        }

        $unserialized = @unserialize($decoded, ['allowed_classes' => false]);
        if (($unserialized === false && $decoded !== 'b:0;') || ! is_array($unserialized)) {
            return [];
        }

        return $unserialized;
    }

    /**
     * @param  array<int|string, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        if (! is_array($value)) {
            return json_encode([], JSON_THROW_ON_ERROR);
        }

        return json_encode(array_values($value), JSON_THROW_ON_ERROR);
    }
}
