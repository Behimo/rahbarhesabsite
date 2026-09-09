<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsAuditLog extends Model
{
    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'payload', 'ip',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(CmsAdmin::class, 'admin_id');
    }

    public static function record(string $action, ?Model $subject = null, array $payload = []): void
    {
        static::query()->create([
            'admin_id' => auth('cms')->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
            'ip' => request()->ip(),
        ]);
    }
}
