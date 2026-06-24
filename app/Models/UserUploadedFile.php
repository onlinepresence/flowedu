<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUploadedFile extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(UserFileCategory::class, 'category_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
