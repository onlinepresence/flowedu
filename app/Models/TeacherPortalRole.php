<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherPortalRole extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'permissions',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
