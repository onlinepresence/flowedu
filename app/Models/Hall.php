<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    protected $fillable = [
        'name',
        'master',
        'cost',
        'period',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
