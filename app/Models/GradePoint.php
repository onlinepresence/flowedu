<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradePoint extends Model
{
    protected $table = 'grade_points';

    protected $fillable = [
        'grade',
        'min_score',
        'max_score',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'float',
            'max_score' => 'float',
            'points' => 'float',
        ];
    }
}
