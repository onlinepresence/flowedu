<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeComponent extends Model
{
    protected $fillable = [
        'name',
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
