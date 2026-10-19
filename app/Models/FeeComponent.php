<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeComponent extends Model
{
    /** @var list<string> */
    public const SYSTEM_FIELDS = [
        'tuition_fee',
        'library_fee',
        'lab_fee',
        'medical_fee',
        'sports_fee',
        'examination_fee',
        'registration_fee',
        'ict_fee',
        'id_card_fee',
        'facility_maintenance_fee',
        'utility_fee',
        'field_trip_fee',
        'internship_fee',
        'src_dues',
    ];

    protected $fillable = [
        'name',
        'field',
        'default_percentage',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'default_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class);
    }
}
