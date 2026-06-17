<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentGuardian extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'relationship',
        'address',
        'phone_number',
        'email',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
