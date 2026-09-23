<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPostRevision extends Model
{
    protected $fillable = [
        'post_id', 'admin_id', 'snapshot', 'note',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsPost::class, 'post_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(CmsAdmin::class, 'admin_id');
    }
}
