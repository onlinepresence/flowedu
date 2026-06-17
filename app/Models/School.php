<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'website',
        'description',
        'logo',
        'ready',
        'is_admit',
        'motto',
        'established_year',
        'principal_name',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
    ];

    protected function casts(): array
    {
        return [
            'ready' => 'boolean',
            'is_admit' => 'boolean',
        ];
    }

    /**
     * Single-school deployment: at most one row.
     */
    public static function current(): ?self
    {
        return static::query()->first();
    }

    public function licence(): HasOne
    {
        return $this->hasOne(SchoolLicence::class);
    }
}
