<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseDetail extends Model
{
    protected $table = 'response_details';

    public const UPDATED_AT = null;

    protected $fillable = [
        'response_id',
        'question_id',
        'question_text_snapshot',
        'answer_value',
        'answer_text',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(EvaluationResponse::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }
}
