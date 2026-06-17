<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'academic_session_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public static function activeSemesterId(): ?int
    {
        $active = self::where('is_active', true)->first();
        if ($active) {
            return (int) $active->id;
        }
        return self::orderByDesc('id')->value('id') ? (int) self::orderByDesc('id')->value('id') : null;
    }
}
