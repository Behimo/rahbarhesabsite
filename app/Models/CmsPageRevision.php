<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageRevision extends Model
{
    protected $fillable = [
        'page_id', 'admin_id', 'content', 'builder_content', 'note',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'builder_content' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(CmsAdmin::class, 'admin_id');
    }
}
