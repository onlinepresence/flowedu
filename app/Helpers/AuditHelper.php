<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\SystemAudit;
use Illuminate\Database\Eloquent\Model;

class AuditHelper
{
    /**
     * Log a system action.
     *
     * @param string $action
     * @param string $description
     * @param Model|null $auditable
     * @param array|null $metadata
     * @param bool $isFlagged
     * @return SystemAudit
     */
    public static function log(
        string $action,
        string $description,
        ?Model $auditable = null,
        ?array $metadata = null,
        bool $isFlagged = false
    ): SystemAudit {
        return SystemAudit::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'metadata' => $metadata,
            'is_flagged' => $isFlagged,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
