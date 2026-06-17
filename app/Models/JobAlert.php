<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'company_or_organizer',
        'description',
        'requirements',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];
}
