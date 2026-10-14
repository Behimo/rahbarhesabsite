<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawEvolution extends Model
{
    protected $fillable = [
        'accounting_law_id',
        'event_date',
        'title',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function law(): BelongsTo
    {
        return $this->belongsTo(AccountingLaw::class, 'accounting_law_id');
    }
}
