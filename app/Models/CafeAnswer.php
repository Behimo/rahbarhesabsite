<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeAnswer extends Model
{
    protected $fillable = [
        'cafe_question_id',
        'user_id',
        'body',
        'is_official',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CafeQuestion::class, 'cafe_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
