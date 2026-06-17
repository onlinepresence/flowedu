<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Merges {@see config('image_validation.passport')} with {@see Setting} rows
 * persisted from the admin image validation page (legacy keys {@code image_validation.passport_*}).
 */
final class PassportValidationConfigResolver
{
    /**
     * @return array<string, mixed>
     */
    public function passportConfig(): array
    {
        $base = config('image_validation.passport', []);
        if (! is_array($base)) {
            $base = [];
        }

        if (! Schema::hasTable('settings')) {
            return $this->mergeRows($base, collect());
        }

        $rows = Setting::query()
            ->where(function ($q): void {
                $q->where('category', 'image_validation')
                    ->orWhere('setting_key', 'like', 'image_validation.passport%');
            })
            ->get();

        return $this->mergeRows($base, $rows);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  Collection<int, Setting>  $rows
     * @return array<string, mixed>
     */
    private function mergeRows(array $base, Collection $rows): array
    {
        $out = $base;
        $bg = is_array($out['bg_color'] ?? null) ? $out['bg_color'] : [];
        $out['bg_color'] = [
            'r' => (int) ($bg['r'] ?? 255),
            'g' => (int) ($bg['g'] ?? 0),
            'b' => (int) ($bg['b'] ?? 0),
        ];

        foreach ($rows as $row) {
            $key = (string) $row->setting_key;
            if (! str_starts_with($key, 'image_validation.')) {
                continue;
            }
            $suffix = substr($key, strlen('image_validation.'));
            $value = $this->castSettingValue($row);

            match ($suffix) {
                'passport_bg_color_r' => $out['bg_color']['r'] = (int) $value,
                'passport_bg_color_g' => $out['bg_color']['g'] = (int) $value,
                'passport_bg_color_b' => $out['bg_color']['b'] = (int) $value,
                'passport_tolerance' => $out['bg_tolerance'] = (int) $value,
                'passport_min_width' => $out['min_width'] = (int) $value,
                'passport_min_height' => $out['min_height'] = (int) $value,
                'passport_match_percentage' => $out['edge_match_percent'] = (int) $value,
                'passport_skip_ratio' => $out['skip_ratio'] = (bool) $value,
                'passport_aspect_ratio' => $out['aspect_ratio'] = (string) $value,
                'passport_edge_sample_divisor' => $out['edge_sample_divisor'] = (int) $value,
                default => null,
            };
        }

        return $out;
    }

    private function castSettingValue(Setting $row): mixed
    {
        $raw = $row->setting_value;
        $type = $row->data_type ?? 'string';

        return match ($type) {
            'integer' => (int) $raw,
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            default => $raw,
        };
    }
}
